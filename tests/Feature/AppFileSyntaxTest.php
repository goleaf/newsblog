<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppFileSyntaxTest extends TestCase
{
    #[Test]
    #[DataProvider('appPhpFiles')]
    public function test_app_php_file_has_valid_syntax(string $path): void
    {
        try {
            require_once $path;
        } catch (\Throwable $throwable) {
            $this->fail(
                sprintf(
                    'Including %s threw %s: %s',
                    str_replace(self::projectRoot().'/', '', $path),
                    $throwable::class,
                    $throwable->getMessage()
                )
            );
        }

        $this->assertTrue(true);
    }

    public static function appPhpFiles(): array
    {
        $basePath = self::projectRoot().'/app';

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = [$file->getPathname()];
            }
        }

        return $files;
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
