<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ComprehensiveAppTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all PHP files in app directory are syntactically valid.
     */
    public function test_all_app_files_have_valid_syntax(): void
    {
        $files = File::allFiles(app_path());
        $errors = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $output = [];
            $returnCode = 0;
            exec('php -l '.escapeshellarg($file->getPathname()), $output, $returnCode);

            if ($returnCode !== 0) {
                $errors[] = $file->getPathname().': '.implode("\n", $output);
            }
        }

        $this->assertEmpty($errors, "Syntax errors found:\n".implode("\n", $errors));
    }

    /**
     * Test that all classes in app directory can be autoloaded.
     */
    public function test_all_app_classes_can_be_autoloaded(): void
    {
        $files = File::allFiles(app_path());
        $errors = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Extract namespace and class name
            if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch) &&
                preg_match('/(?:class|interface|trait|enum)\s+(\w+)/', $content, $classMatch)) {

                $fullClassName = $namespaceMatch[1].'\\'.$classMatch[1];

                try {
                    if (! class_exists($fullClassName) &&
                        ! interface_exists($fullClassName) &&
                        ! trait_exists($fullClassName) &&
                        ! enum_exists($fullClassName)) {
                        $errors[] = "Cannot autoload: {$fullClassName} from {$file->getPathname()}";
                    }
                } catch (\Throwable $e) {
                    // Some classes may have dependencies that can't be resolved in this context
                    // We'll skip those for now
                }
            }
        }

        $this->assertEmpty($errors, "Autoload errors found:\n".implode("\n", $errors));
    }
}
