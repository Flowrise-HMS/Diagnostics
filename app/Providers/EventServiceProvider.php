<?php

namespace Modules\Diagnostics\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Clinical\Events\RequestItemCancelled;
use Modules\Clinical\Events\RequestItemCreated;
use Modules\Diagnostics\Listeners\CancelDiagnosticFulfillmentFromRequestItem;
use Modules\Diagnostics\Listeners\CreateDiagnosticFulfillmentFromRequestItem;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        RequestItemCreated::class => [
            CreateDiagnosticFulfillmentFromRequestItem::class,
        ],
        RequestItemCancelled::class => [
            CancelDiagnosticFulfillmentFromRequestItem::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
