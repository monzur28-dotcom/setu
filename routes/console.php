<?php

use App\Models\BiodataDraft;
use App\Models\ClassifiedAd;
use App\Models\ConnectProfile;
use App\Models\Verification;
use App\Services\LandingPageService;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
| Retention is not a policy document — it is a cron job. Spec 21.4.
*/

// Identity documents are deleted 30 days after the decision. Only the
// decision, the last four characters and a hash survive.
Schedule::call(function () {
    Verification::whereNotNull('document_path')
        ->where('purge_after', '<', now())
        ->whereIn('status', ['APPROVED', 'REJECTED'])
        ->each(function (Verification $v) {
            Storage::disk('kyc')->delete($v->document_path);
            $v->update(['document_path' => null]);
        });
})->dailyAt('03:00')->name('purge-kyc-documents');

// A biodata draft holds a full personal record with no account behind it.
Schedule::call(fn () => BiodataDraft::whereNull('converted_user_id')
    ->where('created_at', '<', now()->subDays(config('setu.retention.biodata_draft_days')))
    ->delete())->dailyAt('03:15')->name('purge-biodata-drafts');

// Connect data is purged faster than the account default — someone leaving
// wants it gone.
Schedule::call(fn () => ConnectProfile::onlyTrashed()
    ->where('deleted_at', '<', now()->subDays(config('setu.retention.connect_purge_days')))
    ->forceDelete())->dailyAt('03:30')->name('purge-connect');

// Ads expire at 60 days, with a renewal reminder at day 53.
Schedule::call(fn () => ClassifiedAd::where('status', 'LIVE')
    ->where('expires_at', '<', now())->update(['status' => 'EXPIRED']))
    ->dailyAt('04:00')->name('expire-ads');

// Refresh landing-page counts and re-evaluate the noindex threshold.
Schedule::call(fn () => app(LandingPageService::class)->refreshAll())
    ->hourly()->name('refresh-landing-counts');
