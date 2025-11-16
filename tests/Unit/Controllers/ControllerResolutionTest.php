<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ControllerResolutionTest extends TestCase
{
    #[DataProvider('controllerClassProvider')]
    public function test_controller_is_resolvable_via_container(string $controllerClass): void
    {
        $instance = app()->make($controllerClass);

        $this->assertInstanceOf(
            Controller::class,
            $instance,
            "{$controllerClass} should resolve through the service container."
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function controllerClassProvider(): array
    {
        $controllers = collect(self::phpFiles(self::controllersPath()))
            ->map(function (string $file) {
                $relativePath = Str::after($file, self::appPath().DIRECTORY_SEPARATOR);

                return 'App\\'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);
            })
            ->filter(function (string $class) {
                if (! class_exists($class)) {
                    return false;
                }

                if ($class === Controller::class) {
                    return false;
                }

                $reflection = new \ReflectionClass($class);

                if ($reflection->isAbstract()) {
                    return false;
                }

                return $reflection->isSubclassOf(Controller::class);
            })
            ->values();

        return $controllers
            ->map(fn (string $class) => [$class])
            ->all();
    }

    private static function controllersPath(): string
    {
        return self::appPath().DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';
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


