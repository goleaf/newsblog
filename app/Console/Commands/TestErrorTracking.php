<?php

namespace App\Console\Commands;

use App\Services\LoggingService;
use Illuminate\Console\Command;

class TestErrorTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error:test {--type=info : Type of test (info, warning, error, critical, exception)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test error tracking and logging functionality';

    /**
     * Execute the console command.
     */
    public function handle(LoggingService $loggingService): int
    {
        $type = $this->option('type');

        $this->info("Testing error tracking with type: {$type}");

        switch ($type) {
            case 'info':
                $loggingService->logBusinessEvent('test_event', [
                    'test_data' => 'This is a test business event',
                    'timestamp' => now()->toIso8601String(),
                ]);
                $this->info('✓ Business event logged');
                break;

            case 'warning':
                $loggingService->logSecurityEvent('test_security_event', [
                    'test_data' => 'This is a test security event',
                    'severity' => 'warning',
                ]);
                $this->info('✓ Security event logged');
                break;

            case 'error':
                $loggingService->logError('Test error message', null, [
                    'test_data' => 'This is a test error',
                ]);
                $this->info('✓ Error logged');
                break;

            case 'critical':
                $loggingService->logCritical('Test critical error', null, [
                    'test_data' => 'This is a test critical error',
                    'requires_immediate_attention' => true,
                ]);
                $this->info('✓ Critical error logged (should trigger Slack notification if configured)');
                break;

            case 'exception':
                try {
                    throw new \RuntimeException('Test exception for error tracking');
                } catch (\Exception $e) {
                    $loggingService->logError('Test exception caught', $e);

                    if (app()->bound('sentry')) {
                        app('sentry')->captureException($e);
                        $this->info('✓ Exception logged and sent to Sentry');
                    } else {
                        $this->info('✓ Exception logged (Sentry not configured)');
                    }
                }
                break;

            default:
                $this->error("Invalid type: {$type}");
                $this->info('Valid types: info, warning, error, critical, exception');

                return 1;
        }

        $this->newLine();
        $this->info('Check the following log files:');
        $this->line('  - storage/logs/business.log (for business events)');
        $this->line('  - storage/logs/security.log (for security events)');
        $this->line('  - storage/logs/errors.log (for errors)');
        $this->line('  - storage/logs/laravel.log (for critical errors)');

        if (app()->bound('sentry') && config('sentry.dsn')) {
            $this->line('  - Sentry dashboard (for exceptions)');
        }

        return 0;
    }
}
