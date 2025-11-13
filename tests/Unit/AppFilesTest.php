<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class AppFilesTest extends TestCase
{
    #[DataProvider('appPhpFilesProvider')]
    /**
     * @param  array<int, array{type: string, fqcn: string}>  $definitions
     */
    public function test_app_file_is_loadable(string $relativePath, array $definitions): void
    {
        if ($definitions !== []) {
            foreach ($definitions as $definition) {
                $exists = match ($definition['type']) {
                    'class' => class_exists($definition['fqcn']),
                    'interface' => interface_exists($definition['fqcn']),
                    'trait' => trait_exists($definition['fqcn']),
                    'enum' => function_exists('enum_exists')
                        ? enum_exists($definition['fqcn'])
                        : class_exists($definition['fqcn']),
                    default => false,
                };

                $this->assertTrue(
                    $exists,
                    sprintf(
                        'Failed asserting that %s %s (from %s) is autoloadable.',
                        $definition['type'],
                        $definition['fqcn'],
                        $relativePath
                    )
                );
            }
        } else {
            require_once self::appDirectory().DIRECTORY_SEPARATOR.$relativePath;

            $this->assertTrue(true, sprintf('Included %s successfully.', $relativePath));
        }
    }

    /**
     * @return iterable<int, array{string, array<int, class-string>}>
     */
    public static function appPhpFilesProvider(): iterable
    {
        $appDirectory = self::appDirectory();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($appDirectory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->getExtension() !== 'php') {
                continue;
            }

            $relativePath = ltrim(str_replace($appDirectory, '', $fileInfo->getPathname()), DIRECTORY_SEPARATOR);
            $definitions = self::determineDefinitions($fileInfo->getPathname());

            yield [$relativePath, $definitions];
        }
    }

    /**
     * @return array<int, array{type: string, fqcn: string}>
     */
    private static function determineDefinitions(string $absolutePath): array
    {
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            return [];
        }

        $namespace = '';

        if (preg_match('/^namespace\s+([^;{]+);/m', $contents, $namespaceMatch) === 1) {
            $namespace = trim($namespaceMatch[1]);
        }

        if (preg_match_all('/\b(class|interface|trait|enum)\s+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)/m', $contents, $matches, PREG_SET_ORDER) === 0) {
            return [];
        }

        $definitions = [];

        foreach ($matches as $match) {
            $definitions[] = [
                'type' => strtolower($match[1]),
                'fqcn' => ltrim(($namespace !== '' ? $namespace.'\\' : '').$match[2], '\\'),
            ];
        }

        return $definitions;
    }

    private static function appDirectory(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app';
    }
}
