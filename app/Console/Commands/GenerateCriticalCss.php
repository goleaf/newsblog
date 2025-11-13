<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateCriticalCss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:generate-critical-css';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate critical CSS for above-the-fold content';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating critical CSS...');

        // Check if build directory exists
        $buildPath = public_path('build');
        if (! is_dir($buildPath)) {
            $this->error('Build directory not found. Please run "npm run build" first.');

            return self::FAILURE;
        }

        // Find the main CSS file
        $cssFiles = glob($buildPath.'/assets/*.css');
        if (empty($cssFiles)) {
            $this->error('No CSS files found in build directory.');

            return self::FAILURE;
        }

        $mainCssFile = $cssFiles[0];
        $this->info('Found CSS file: '.basename($mainCssFile));

        // Read the CSS content
        $cssContent = file_get_contents($mainCssFile);

        // Extract critical CSS (simplified approach - extracts base styles)
        $criticalCss = $this->extractCriticalCss($cssContent);

        // Save critical CSS
        $criticalPath = config('performance.critical_css.path');
        file_put_contents($criticalPath, $criticalCss);

        $size = strlen($criticalCss);
        $maxSize = config('performance.critical_css.max_size', 14336);

        $this->info('Critical CSS generated successfully!');
        $this->info("Size: {$size} bytes (max: {$maxSize} bytes)");

        if ($size > $maxSize) {
            $this->warn("Warning: Critical CSS exceeds recommended size of {$maxSize} bytes.");
        }

        return self::SUCCESS;
    }

    /**
     * Extract critical CSS from full CSS content.
     * This is a simplified approach - for production, consider using tools like:
     * - critical (npm package)
     * - penthouse
     * - critters
     */
    private function extractCriticalCss(string $cssContent): string
    {
        $critical = [];

        // Extract CSS reset and base styles
        if (preg_match('/\/\*\s*Base\s*\*\/(.*?)\/\*\s*Components\s*\*\//s', $cssContent, $matches)) {
            $critical[] = $matches[1];
        }

        // Extract Tailwind base layer
        if (preg_match('/@layer\s+base\s*{(.*?)}/s', $cssContent, $matches)) {
            $critical[] = $matches[1];
        }

        // Extract critical utility classes (common layout classes)
        $criticalClasses = [
            'container', 'mx-auto', 'px-', 'py-', 'flex', 'grid',
            'text-', 'bg-', 'dark:', 'hidden', 'block', 'inline',
            'w-full', 'h-', 'max-w-', 'min-h-', 'overflow-',
        ];

        foreach ($criticalClasses as $class) {
            if (preg_match_all('/\.'.preg_quote($class, '/').'[^{]*{[^}]*}/s', $cssContent, $matches)) {
                $critical = array_merge($critical, $matches[0]);
            }
        }

        // Combine and minify
        $criticalCss = implode("\n", $critical);

        // Basic minification
        $criticalCss = preg_replace('/\s+/', ' ', $criticalCss);
        $criticalCss = preg_replace('/\s*([{}:;,])\s*/', '$1', $criticalCss);
        $criticalCss = preg_replace('/;}/', '}', $criticalCss);

        return trim($criticalCss);
    }
}
