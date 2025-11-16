<?php

namespace Tests\Unit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModelResolutionTest extends TestCase
{
    #[DataProvider('modelClassProvider')]
    public function test_model_can_be_instantiated(string $modelClass): void
    {
        $this->assertInstanceOf(
            Model::class,
            new $modelClass,
            "{$modelClass} should be instantiable."
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function modelClassProvider(): array
    {
        $models = collect(self::phpFiles(self::modelsPath()))
            ->map(function (string $file) {
                $relativePath = Str::after($file, self::appPath().DIRECTORY_SEPARATOR);

                return 'App\\'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);
            })
            ->filter(function (string $class) {
                if (! class_exists($class)) {
                    return false;
                }

                $reflection = new \ReflectionClass($class);

                if ($reflection->isAbstract()) {
                    return false;
                }

                return $reflection->isSubclassOf(Model::class);
            })
            ->values();

        return $models
            ->map(fn (string $class) => [$class])
            ->all();
    }

    private static function modelsPath(): string
    {
        return self::appPath().DIRECTORY_SEPARATOR.'Models';
    }

    private static function appPath(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'app';
    }

    /**
     * @return array<int, string>
     */
    private static function phpFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}

