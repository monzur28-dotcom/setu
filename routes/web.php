<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\CandidateConfirmController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Connect\ConnectController;
use App\Http\Controllers\Family\FamilyDashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Member\AccessRequestController;
use App\Http\Controllers\Member\BillingController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\FamilyController;
use App\Http\Controllers\Member\InterestController;
use App\Http\Controllers\Member\MailboxController;
use App\Http\Controllers\Member\PrivacyController;
use App\Http\Controllers\Member\ProfileEditController;
use App\Http\Controllers\Member\SearchController;
use App\Http\Controllers\Member\SettingsController;
use App\Http\Controllers\Member\VerificationController;
use App\Http\Controllers\Operator\CaseController;
use App\Http\Controllers\Public\BiodataController;
use App\Http\Controllers\Public\ClassifiedController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PublicProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC — indexable, no account required
|--------------------------------------------------------------------------
| Browsing without an account is the model, not a concession: a visitor must
| be able to see real profiles before giving anything.
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('public.search');
Route::get('/profile/{profileId}', [PublicProfileController::class, 'show'])->name('public.profile');

Route::get('/plans', [PageController::class, 'plans'])->name('plans');
Route::get('/safety', [PageController::class, 'safety'])->name('safety');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/success-stories', [PageController::class, 'stories'])->name('stories');
Route::get('/guides/{slug}', [PageController::class, 'guide'])->name('guide');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// The free biodata maker. No signup — that is the entire point.
Route::get('/marriage-biodata-maker', [BiodataController::class, 'create'])->name('biodata.create');
Route::post('/marriage-biodata-maker', [BiodataController::class, 'store'])->name('biodata.store');
Route::get('/biodata/{token}', [BiodataController::class, 'preview'])->name('biodata.preview');
Route::get('/biodata/{token}/create-profile', [BiodataController::class, 'convert'])->name('biodata.convert');

// Free public classifieds — the newspaper পাত্র-পাত্রী column.
Route::get('/classifieds', [ClassifiedController::class, 'index'])->name('classifieds.index');
Route::get('/classifieds/{slug}', [ClassifiedController::class, 'show'])->name('classifieds.show');

// Signed, short-lived media. Never a public bucket.
Route::get('/media/photo/{photo}/{variant?}', [MediaController::class, 'photo'])->name('media.photo');
Route::get('/media/cx/{photo}/{variant?}', [MediaController::class, 'connectPhoto'])->name('media.connect_photo');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showStep1'])->name('register');
    Route::post('/register', [RegisterController::class, 'storeStep1'])->name('register.store');
    Route::get('/register/verify', [RegisterController::class, 'showOtp'])->name('register.otp');
    Route::post('/register/verify', [RegisterController::class, 'verifyOtp'])->name('register.otp.verify');

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    Route::post('/login/code', [LoginController::class, 'requestCode'])->name('login.code.request');
    Route::get('/login/code', [LoginController::class, 'showCode'])->name('login.code');
    Route::post('/login/code/verify', [LoginController::class, 'verifyCode'])->name('login.code.verify');
});

// The candidate consent gate. Reachable without a session, by SMS link.
Route::get('/confirm/{token}', [CandidateConfirmController::class, 'show'])->name('candidate.confirm');
Route::post('/confirm/{token}', [CandidateConfirmController::class, 'confirm'])->name('candidate.confirm.store');
Route::post('/confirm/{token}/reject', [CandidateConfirmController::class, 'reject'])->name('candidate.reject');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| MEMBER — matrimonial
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/register/details', [RegisterController::class, 'showStep2'])->name('register.step2');
    Route::post('/register/details', [RegisterController::class, 'storeStep2'])->name('register.step2.store');

    Route::get('/me', [DashboardController::class, 'index'])->name('member.dashboard');

    Route::get('/me/search', [SearchController::class, 'index'])->name('member.search');
    Route::post('/me/search/save', [SearchController::class, 'save'])->name('member.search.save');

    Route::get('/me/privacy', [PrivacyController::class, 'edit'])->name('member.privacy');
    Route::patch('/me/privacy', [PrivacyController::class, 'update'])->name('member.privacy.update');
    Route::patch('/me/privacy/indexing', [PrivacyController::class, 'indexing'])->name('member.privacy.indexing');
    Route::post('/me/privacy/hide', [PrivacyController::class, 'hide'])->name('member.privacy.hide');

    Route::get('/me/profile/preview', [ProfileEditController::class, 'preview'])->name('member.profile.preview');
    Route::get('/me/profile/{tab?}', [ProfileEditController::class, 'edit'])->name('member.profile.edit');
    Route::patch('/me/profile/{tab}', [ProfileEditController::class, 'update'])->name('member.profile.update');
    Route::post('/me/photos', [ProfileEditController::class, 'uploadPhoto'])->name('member.photo.store');
    Route::delete('/me/photos/{photo}', [ProfileEditController::class, 'deletePhoto'])->name('member.photo.destroy');

    Route::get('/interests', [InterestController::class, 'index'])->name('member.interests');
    Route::post('/interests/{profileId}', [InterestController::class, 'store'])->name('member.interest.send');
    Route::patch('/interests/{interest}', [InterestController::class, 'respond'])->name('member.interest.respond');

    Route::get('/access-requests', [AccessRequestController::class, 'index'])->name('member.access');
    Route::post('/access-requests/{profileId}', [AccessRequestController::class, 'store'])->name('member.access.request');
    Route::patch('/access-requests/{accessRequest}', [AccessRequestController::class, 'respond'])->name('member.access.respond');

    Route::get('/mailbox', [MailboxController::class, 'index'])->name('member.mailbox');
    Route::get('/mailbox/{thread}', [MailboxController::class, 'show'])->name('member.mailbox.show');
    Route::post('/mailbox/{thread}', [MailboxController::class, 'send'])->name('member.mailbox.send');
    Route::post('/mailbox/{thread}/offer-contact', [MailboxController::class, 'offerContact'])->name('member.contact.offer');
    Route::post('/mailbox/{thread}/accept-contact', [MailboxController::class, 'acceptContact'])->name('member.contact.accept');

    Route::get('/family', [FamilyController::class, 'index'])->name('member.family');
    Route::post('/family/invite', [FamilyController::class, 'invite'])->name('member.family.invite');
    Route::patch('/family/{link}/level', [FamilyController::class, 'setLevel'])->name('member.family.level');
    Route::delete('/family/{link}', [FamilyController::class, 'revoke'])->name('member.family.revoke');

    Route::get('/verification', [VerificationController::class, 'index'])->name('member.verification');
    Route::post('/verification', [VerificationController::class, 'store'])->name('member.verification.store');

    Route::get('/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/checkout/{plan}', [BillingController::class, 'initiate'])->name('billing.initiate');
    Route::get('/billing/callback/{transaction}', [BillingController::class, 'callback'])->name('billing.callback');
    Route::get('/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');

    Route::get('/settings', [SettingsController::class, 'index'])->name('member.settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('member.settings.update');
    Route::post('/settings/pause', [SettingsController::class, 'pause'])->name('member.pause');
    Route::post('/settings/resume', [SettingsController::class, 'resume'])->name('member.resume');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('member.destroy');

    Route::post('/classifieds', [ClassifiedController::class, 'store'])->name('classifieds.store');
    Route::get('/classifieds/place/new', [ClassifiedController::class, 'create'])->name('classifieds.create');
});

Route::post('/webhooks/{provider}', [BillingController::class, 'webhook'])->name('billing.webhook');

/*
|--------------------------------------------------------------------------
| FAMILY DASHBOARD — guardian identity
|--------------------------------------------------------------------------
| No route in this group can reach a mailbox, an interest decision, a profile
| write, or anything under /connect. Spec 12.2.
*/
Route::get('/guardian/accept/{token}', [FamilyDashboardController::class, 'accept'])->name('guardian.accept');

Route::middleware(['auth', 'guardian'])->prefix('family-dash')->name('family.')->group(function () {
    Route::get('/', [FamilyDashboardController::class, 'index'])->name('dashboard');
    Route::get('/introductions', [FamilyDashboardController::class, 'introductions'])->name('introductions');
    Route::post('/notes', [FamilyDashboardController::class, 'storeNote'])->name('note');
    Route::post('/contact-family', [FamilyDashboardController::class, 'requestFamilyContact'])->name('contact');
});

/*
|--------------------------------------------------------------------------
| OPERATOR — the ghotok console
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/cases', [CaseController::class, 'index'])->name('cases');
    Route::get('/cases/{case}', [CaseController::class, 'show'])->name('case');
    Route::get('/cases/{case}/search', [CaseController::class, 'search'])->name('search');
    Route::get('/cases/{case}/candidate/{profile}', [CaseController::class, 'candidate'])->name('candidate');
    Route::post('/cases/{case}/shortlist/{profile}', [CaseController::class, 'shortlist'])->name('shortlist');
    Route::post('/cases/{case}/contact', [CaseController::class, 'logContact'])->name('contact');
    Route::post('/cases/{case}/outcome', [CaseController::class, 'recordOutcome'])->name('outcome');
});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/moderation', [AdminController::class, 'moderation'])->name('moderation');
    Route::post('/moderation/{item}', [AdminController::class, 'decide'])->name('moderation.decide');
    Route::get('/success-fees', [AdminController::class, 'successFees'])->name('fees');
    Route::post('/success-fees/{fee}/confirm', [AdminController::class, 'confirmFee'])->name('fees.confirm');
    // The front-page slideshow, owned by whoever runs the site.
    Route::get('/hero', [AdminController::class, 'hero'])->name('hero');
    Route::post('/hero', [AdminController::class, 'addSlide'])->name('hero.add');
    Route::patch('/hero/{slide}', [AdminController::class, 'updateSlide'])->name('hero.update');
    Route::delete('/hero/{slide}', [AdminController::class, 'removeSlide'])->name('hero.remove');
    // The pre-publication word list. Admin-owned data; the behaviour it
    // drives lives in WordFilter.
    Route::get('/words', [AdminController::class, 'words'])->name('words');
    Route::post('/words', [AdminController::class, 'addWord'])->name('words.add');
    Route::delete('/words/{word}', [AdminController::class, 'removeWord'])->name('words.remove');

    Route::get('/seo', [AdminController::class, 'seo'])->name('seo');
    Route::post('/seo/refresh', [AdminController::class, 'refreshSeo'])->name('seo.refresh');
});

/*
|==========================================================================
| CONNECT — behind the wall
|==========================================================================
| A SINGLE route prefix, so the separation is auditable: one route guard,
| one sitemap exclusion, one noindex rule, one permission check. Scattering
| these through the member area would make the wall impossible to verify.
|
| The `connect` middleware refuses operators and guardians with a 404 —
| not a 403, because even confirming the route exists is more than they are
| entitled to know. Wall rules W3, W4, W5.
*/
Route::middleware(['auth', 'connect'])->prefix('connect')->name('connect.')->group(function () {
    Route::get('/start', [ConnectController::class, 'start'])->name('start');
    Route::post('/start', [ConnectController::class, 'enable'])->name('enable');

    Route::get('/profile', [ConnectController::class, 'editProfile'])->name('profile.edit');
    Route::patch('/profile', [ConnectController::class, 'updateProfile'])->name('profile.update');

    Route::get('/', [ConnectController::class, 'deck'])->name('deck');
    Route::post('/act/{candidate}', [ConnectController::class, 'act'])->name('act');

    Route::get('/matches', [ConnectController::class, 'matches'])->name('matches');
    Route::get('/chat/{match}', [ConnectController::class, 'chat'])->name('chat');
    Route::post('/chat/{match}', [ConnectController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/{match}/unmatch', [ConnectController::class, 'unmatch'])->name('unmatch');
    Route::post('/block/{candidate}', [ConnectController::class, 'block'])->name('block');

    Route::get('/plans', [ConnectController::class, 'plans'])->name('plans');
    Route::get('/settings', [ConnectController::class, 'settings'])->name('settings');
    Route::delete('/profile', [ConnectController::class, 'destroy'])->name('destroy');
});

/*
| Landing pages last: this catch-all must not shadow anything above.
*/
Route::get('/{slug}', [LandingController::class, 'show'])
    ->where('slug', '[a-z0-9\-\/]+')
    ->name('landing');
