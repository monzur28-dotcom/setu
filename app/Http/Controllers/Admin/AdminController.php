<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlockedWord;
use App\Models\ClassifiedAd;
use App\Models\HeroSlide;
use App\Models\LandingPage;
use App\Models\ModerationItem;
use App\Models\OperatorAccessLog;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\Report;
use App\Models\SiteSetting;
use App\Models\SuccessFee;
use App\Models\User;
use App\Services\LandingPageService;
use App\Services\ProfileReview;
use App\Services\WordFilter;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'photoQueue' => ModerationItem::where('status', 'QUEUED')->where('entity_type', 'PHOTO')->count(),
            'profileQueue' => ModerationItem::where('status', 'QUEUED')->where('entity_type', 'PROFILE')->count(),
            'awaitingFirstReview' => Profile::where('moderation_status', 'PENDING')->count(),
            'adQueue'    => ModerationItem::where('status', 'QUEUED')->where('entity_type', 'AD')->count(),
            'reports'    => Report::whereIn('status', ['OPEN', 'REVIEWING'])->count(),
            'critical'   => Report::where('status', 'OPEN')->where('priority', 'CRITICAL')->count(),
            // Must be zero. Any other number is an incident. Spec 25.3.
            'consentViolations' => OperatorAccessLog::where('fields_returned', 'PRIVATE')
                ->where('consent_present', false)->count(),
            'members'    => User::where('status', 'ACTIVE')->count(),
        ]);
    }

    /** Mode-scoped: Connect content is reviewed under Connect policy. */
    public function moderation(Request $request, ProfileReview $review)
    {
        $mode = $request->query('mode', 'MATRIMONIAL');

        $items = ModerationItem::where('status', 'QUEUED')->where('mode', $mode)
            ->orderBy('priority')->limit(50)->get();

        // A moderator cannot decide on an id. Load the text each PROFILE
        // item is about, with the pending copy where one is waiting.
        $profiles = Profile::with(['user', 'family', 'preference'])
            ->whereIn('id', $items->where('entity_type', 'PROFILE')->pluck('entity_id'))
            ->get()->keyBy('id');

        return view('admin.moderation', [
            'mode'     => $mode,
            'items'    => $items,
            'profiles' => $profiles,
            'review'   => $review,
        ]);
    }

    public function decide(Request $request, ModerationItem $item, ProfileReview $review)
    {
        $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason'   => ['nullable', 'string', 'max:80'],
        ]);

        $approved = $request->input('decision') === 'approve';

        match ($item->entity_type) {
            'PHOTO' => Photo::whereKey($item->entity_id)->update([
                'status'           => $approved ? 'APPROVED' : 'REJECTED',
                'rejection_reason' => $approved ? null : $request->input('reason'),
                'moderated_by'     => $request->user()->id,
                'moderated_at'     => now(),
            ]),
            'AD' => ClassifiedAd::whereKey($item->entity_id)->update([
                'status'       => $approved ? 'LIVE' : 'REMOVED',
                'moderated_by' => $request->user()->id,
            ]),
            // Nothing else in the application may set moderation_status;
            // ProfileReview owns the transition and the pending-copy swap.
            'PROFILE' => $this->decideProfile($request, $item, $review, $approved),
            default => null,
        };

        $item->update(['status' => 'DONE']);

        AuditLog::write($request->user(), 'moderation_decision', [
            'entity_type' => $item->entity_type, 'entity_id' => $item->entity_id,
            'after' => ['approved' => $approved, 'reason' => $request->input('reason')],
        ]);

        return back();
    }

    private function decideProfile(Request $request, ModerationItem $item, ProfileReview $review, bool $approved): void
    {
        $profile = Profile::find($item->entity_id);

        if (! $profile) {
            return;
        }

        $approved
            ? $review->approve($profile, $request->user())
            : $review->reject($profile, $request->user(), $request->input('reason') ?: __('admin.no_reason_given'));
    }

    /**
     * How solid the two doorway cards are. Clamped in the model rather than
     * only here, so the range holds however the value is written.
     */
    public function showAppearance()
    {
        return view('admin.appearance', [
            'pairs'   => config('themes.pairs'),
            'weights' => config('themes.weights'),
            'pair'    => Theme::pairKey(),
            'brand'   => Theme::brand(),
            'gold'    => Theme::gold(),
            'size'    => SiteSetting::number('base_font_px'),
            'headW'   => SiteSetting::number('heading_weight'),
            'bodyW'   => SiteSetting::number('body_weight'),
            'tint'    => SiteSetting::number('door_tint'),
            'align'   => SiteSetting::get('door_align', 'left'),
            'tagSize' => SiteSetting::number('door_tag_size'),
            'headSize' => SiteSetting::number('door_head_size'),
            'bodySize' => SiteSetting::number('door_body_size'),
            'tagColor' => Theme::doorColour('door_tag_color', '#7c6a6e'),
            'ctaColor' => Theme::doorColour('door_cta_color', '#63121f'),
            'ctaDating' => Theme::doorColour('door_cta_dating_color', '#1b5249'),
            'globeW'    => SiteSetting::number('globe_width'),
            'blur'    => SiteSetting::number('door_blur'),
            'slide'   => HeroSlide::where('is_active', true)->orderBy('sort_order')->first(),
        ]);
    }

    public function appearance(Request $request)
    {
        $data = $request->validate([
            'door_tint'      => ['required', 'integer', 'min:0', 'max:100'],
            'door_blur'      => ['required', 'integer', 'min:0', 'max:30'],
            'base_font_px'   => ['required', 'integer', 'min:13', 'max:19'],
            // Weights are keys of the offered list, not free numbers.
            'heading_weight' => ['required', Rule::in(array_keys(config('themes.weights.head')))],
            'body_weight'    => ['required', Rule::in(array_keys(config('themes.weights.body')))],
            'font_pair'      => ['required', Rule::in(array_keys(config('themes.pairs')))],
            // Hex only. This value ends up inside a stylesheet.
            'brand_color'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'gold_color'     => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],

            // The doorway cards' own text.
            'door_align'            => ['required', Rule::in(['left', 'center', 'right'])],
            'door_tag_size'         => ['required', 'integer', 'min:9',  'max:18'],
            'door_head_size'        => ['required', 'integer', 'min:16', 'max:44'],
            'door_body_size'        => ['required', 'integer', 'min:11', 'max:20'],
            'globe_width'           => ['required', 'integer', 'min:220', 'max:680'],
            'door_tag_color'        => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'door_cta_color'        => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'door_cta_dating_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        foreach ($data as $key => $value) {
            SiteSetting::put($key, $value, $request->user());
        }

        AuditLog::write($request->user(), 'appearance_updated', [
            'entity_type' => 'SITE_SETTING', 'after' => $data,
        ]);

        return back()->with('status', __('admin.appearance_saved'));
    }
    /**
     * The pre-publication word list. Editing it never changes a decision
     * already made — it changes which profiles the queue puts first from
     * the next submission onward.
     */
    public function words()
    {
        return view('admin.words', [
            'words' => BlockedWord::with('creator')->orderBy('word')->get(),
        ]);
    }

    public function addWord(Request $request, WordFilter $filter)
    {
        $data = $request->validate([
            'word'   => ['required', 'string', 'max:60'],
            'locale' => ['required', 'in:*,bn,en'],
            'note'   => ['nullable', 'string', 'max:120'],
        ]);

        BlockedWord::updateOrCreate(
            ['word' => trim($data['word']), 'locale' => $data['locale']],
            ['note' => $data['note'] ?? null, 'created_by' => $request->user()->id],
        );

        $filter->forget();

        AuditLog::write($request->user(), 'blocked_word_added', [
            'entity_type' => 'BLOCKED_WORD', 'after' => $data,
        ]);

        return back()->with('status', __('admin.word_added'));
    }

    public function removeWord(Request $request, BlockedWord $word, WordFilter $filter)
    {
        $before = $word->only(['word', 'locale']);
        $word->delete();
        $filter->forget();

        AuditLog::write($request->user(), 'blocked_word_removed', [
            'entity_type' => 'BLOCKED_WORD', 'before' => $before,
        ]);

        return back()->with('status', __('admin.word_removed'));
    }
    /**
     * The front-page slideshow. Marketing art, owned by whoever runs the
     * site — which is the entire point of putting it behind a screen
     * instead of a constant in a Blade template.
     */
    public function hero()
    {
        return view('admin.hero', [
            'slides'   => HeroSlide::orderBy('product')->orderBy('sort_order')->orderBy('id')->get(),
            'interval' => config('setu.hero.interval_ms'),
            'tint'     => SiteSetting::number('door_tint'),
            'blur'     => SiteSetting::number('door_blur'),
        ]);
    }

    public function addSlide(Request $request)
    {
        $data = $request->validate([
            'image'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:120'],
            'product' => ['required', 'in:BOTH,MATRIMONIAL,CONNECT'],
        ]);

        HeroSlide::create([
            'path'        => $request->file('image')->store('', 'hero'),
            'caption'     => $data['caption'] ?? null,
            'product'     => $data['product'],
            'sort_order'  => (int) HeroSlide::max('sort_order') + 1,
            'is_active'   => true,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::write($request->user(), 'hero_slide_added', ['entity_type' => 'HERO_SLIDE']);

        return back()->with('status', __('admin.hero_added'));
    }

    public function updateSlide(Request $request, HeroSlide $slide)
    {
        $slide->update($request->validate([
            'caption'    => ['nullable', 'string', 'max:120'],
            'product'    => ['required', 'in:BOTH,MATRIMONIAL,CONNECT'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active'  => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', __('admin.hero_updated'));
    }

    public function removeSlide(Request $request, HeroSlide $slide)
    {
        Storage::disk('hero')->delete($slide->path);
        $slide->delete();

        AuditLog::write($request->user(), 'hero_slide_removed', ['entity_type' => 'HERO_SLIDE']);

        return back()->with('status', __('admin.hero_removed'));
    }
    public function successFees()
    {
        return view('admin.success-fees', [
            'fees' => SuccessFee::with('case')->latest()->get(),
        ]);
    }

    /** Two-person confirmation, enforced in the model. */
    public function confirmFee(Request $request, SuccessFee $fee)
    {
        $fee->confirm($request->user());

        return back()->with('status', __('admin.fee_confirmed'));
    }

    public function seo(LandingPageService $service)
    {
        return view('admin.seo', ['pages' => LandingPage::orderBy('slug')->get()]);
    }

    public function refreshSeo(LandingPageService $service)
    {
        $n = $service->refreshAll();

        return back()->with('status', __('admin.seo_refreshed', ['count' => $n]));
    }
}
