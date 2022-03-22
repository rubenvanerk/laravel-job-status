<?php

namespace Imtigger\LaravelJobStatus;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use Imtigger\LaravelJobStatus\EventManagers\EventManager;

class LaravelJobStatusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPublishables();
        $this->bootListeners();
    }

    private function bootListeners()
    {
        /** @var EventManager $eventManager */
        $eventManager = app(config('job-status.event_manager'));

        // Add Event listeners
        app(QueueManager::class)->before(function (JobProcessing $event) use ($eventManager) {
            $eventManager->before($event);
        });
        app(QueueManager::class)->after(function (JobProcessed $event) use ($eventManager) {
            $eventManager->after($event);
        });
        app(QueueManager::class)->failing(function (JobFailed $event) use ($eventManager) {
            $eventManager->failing($event);
        });
        app(QueueManager::class)->exceptionOccurred(function (JobExceptionOccurred $event) use ($eventManager) {
            $eventManager->exceptionOccurred($event);
        });
    }

    protected function registerPublishables(): void
    {
        if (!class_exists('CreateMediaTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_job_statuses_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_job_statuses.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__ . '/../config/job-status.php' => config_path('job-status.php'),
        ], 'config');
    }
}
