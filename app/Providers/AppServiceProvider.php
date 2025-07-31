<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\LegalCase;
use App\Models\Task;
use App\Policies\ClientPolicy;
use App\Policies\FirmMemberPolicy;
use App\Policies\LawFirmPolicy;
use App\Policies\LegalCasePolicy;
use App\Policies\TaskPolicy;
use App\Services\PythonApiService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PythonApiService::class, function ($app) {
            return new PythonApiService();
        });
    }

    protected $policies = [
        FirmMember::class => FirmMemberPolicy::class,
        LawFirm::class => LawFirmPolicy::class,
        LegalCase::class => LegalCasePolicy::class,
        Task::class => TaskPolicy::class,
        Client::class => ClientPolicy::class

    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
