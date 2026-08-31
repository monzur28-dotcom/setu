<?php

namespace App\Providers;

use App\Models\ClassifiedAd;
use App\Models\Photo;
use App\Models\Profile;
use App\Services\ConsentGate;
use App\Services\ContactMasker;
use App\Services\DeckGenerator;
use App\Services\LandingPageService;
use App\Services\MatchScore;
use App\Services\OtpService;
use App\Services\PaymentGateway;
use App\Services\SmsSender;
use App\Services\VisibilitySerializer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            VisibilitySerializer::class, ContactMasker::class, MatchScore::class,
            SmsSender::class, PaymentGateway::class, LandingPageService::class,
            DeckGenerator::class,
        ] as $singleton) {
            $this->app->singleton($singleton);
        }

        $this->app->singleton(OtpService::class,
            fn ($app) => new OtpService($app->make(SmsSender::class)));

        $this->app->singleton(ConsentGate::class,
            fn ($app) => new ConsentGate($app->make(VisibilitySerializer::class)));
    }

    public function boot(): void
    {
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        /*
         * A photo row whose uploader is not the profile owner must not exist.
         * MySQL check constraints cannot span tables, so the invariant is
         * enforced here as well as in the request layer.
         * Spec 27.2 P13 / 12.2 G6.
         */
        Photo::creating(function (Photo $photo) {
            $ownerId = Profile::whereKey($photo->profile_id)->value('user_id');

            if ($ownerId !== $photo->uploaded_by_user_id) {
                throw new \RuntimeException(
                    'Only the account holder may upload their own photographs. '
                    .'A guardian contributed at profile creation; after that the '
                    .'profile belongs to the candidate.'
                );
            }
        });

        // নো-মিডিয়া is enforced at the data layer, so nobody can forget it.
        ClassifiedAd::addGlobalScope('respect_no_media', function ($builder) {
            if (auth()->check() && auth()->user()->role === 'OPERATOR') {
                $builder->where('no_media_flag', false);
            }
        });
    }
}
