<?php

namespace App\Console\Commands;

use App\Services\AccessibilityService;
use Illuminate\Console\Command;

class CheckColorContrast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accessibility:check-contrast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check color contrast ratios for WCAG AA compliance';

    public function __construct(
        protected AccessibilityService $accessibilityService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking color contrast ratios for WCAG AA compliance...');
        $this->newLine();

        // Define color combinations to check
        $colorCombinations = [
            // Light mode
            ['name' => 'Light Mode - Body Text', 'foreground' => '#111827', 'background' => '#FFFFFF', 'largeText' => false],
            ['name' => 'Light Mode - Headings', 'foreground' => '#111827', 'background' => '#FFFFFF', 'largeText' => true],
            ['name' => 'Light Mode - Links', 'foreground' => '#2563EB', 'background' => '#FFFFFF', 'largeText' => false],
            ['name' => 'Light Mode - Muted Text', 'foreground' => '#6B7280', 'background' => '#FFFFFF', 'largeText' => false],
            ['name' => 'Light Mode - Primary Button', 'foreground' => '#FFFFFF', 'background' => '#2563EB', 'largeText' => false],
            ['name' => 'Light Mode - Success', 'foreground' => '#065F46', 'background' => '#D1FAE5', 'largeText' => false],
            ['name' => 'Light Mode - Error', 'foreground' => '#991B1B', 'background' => '#FEE2E2', 'largeText' => false],
            ['name' => 'Light Mode - Warning', 'foreground' => '#92400E', 'background' => '#FEF3C7', 'largeText' => false],

            // Dark mode
            ['name' => 'Dark Mode - Body Text', 'foreground' => '#F9FAFB', 'background' => '#111827', 'largeText' => false],
            ['name' => 'Dark Mode - Headings', 'foreground' => '#FFFFFF', 'background' => '#111827', 'largeText' => true],
            ['name' => 'Dark Mode - Links', 'foreground' => '#60A5FA', 'background' => '#111827', 'largeText' => false],
            ['name' => 'Dark Mode - Muted Text', 'foreground' => '#9CA3AF', 'background' => '#111827', 'largeText' => false],
            ['name' => 'Dark Mode - Primary Button', 'foreground' => '#FFFFFF', 'background' => '#2563EB', 'largeText' => false],
            ['name' => 'Dark Mode - Success', 'foreground' => '#6EE7B7', 'background' => '#064E3B', 'largeText' => false],
            ['name' => 'Dark Mode - Error', 'foreground' => '#FCA5A5', 'background' => '#7F1D1D', 'largeText' => false],
            ['name' => 'Dark Mode - Warning', 'foreground' => '#FCD34D', 'background' => '#78350F', 'largeText' => false],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($colorCombinations as $combo) {
            $meetsRequirements = $this->accessibilityService->meetsContrastRequirements(
                $combo['foreground'],
                $combo['background'],
                $combo['largeText']
            );

            if ($meetsRequirements) {
                $this->components->info("✓ {$combo['name']}");
                $passed++;
            } else {
                $this->components->error("✗ {$combo['name']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Results: {$passed} passed, {$failed} failed");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('Some color combinations do not meet WCAG AA standards.');
            $this->warn('Please update the colors to ensure sufficient contrast.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All color combinations meet WCAG AA standards! ✓');

        return self::SUCCESS;
    }
}
