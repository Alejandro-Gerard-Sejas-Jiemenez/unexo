<?php

namespace App\Providers;

use App\Services\Pagos\PaymentGatewayInterface;
use App\Services\Pagos\SimulatedPaymentGateway;
use App\Services\Pagos\StripePaymentGateway;
use App\Support\DocumentacionApi;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return config('services.payments.driver') === 'stripe'
                ? new StripePaymentGateway()
                : new SimulatedPaymentGateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()->withDocumentTransformers(DocumentacionApi::completar(...));
    }
}
