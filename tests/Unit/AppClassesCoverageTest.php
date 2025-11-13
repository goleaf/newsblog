<?php

namespace Tests\Unit;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class AppClassesCoverageTest extends TestCase
{
    /**
     * @return array<string, array{type: string, fqcn: string}>
     */
    public static function appClassProvider(): array
    {
        $classes = [];

        $appPath = dirname(__DIR__, 2).'/app';

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in($appPath)->name('*.php') as $file) {

            $parsed = self::parsePhpFileForDefinitions($file->getRealPath());

            foreach ($parsed as $definition) {
                $classes[$definition['fqcn']] = $definition;
            }
        }

        return array_map(
            static fn (array $definition): array => [$definition['type'], $definition['fqcn']],
            $classes
        );
    }

    /**
     * @dataProvider appClassProvider
     */
    public function test_app_php_files_are_autoloadable(string $type, string $fqcn): void
    {
        $exists = match ($type) {
            'class', 'enum' => class_exists($fqcn),
            'interface' => interface_exists($fqcn),
            'trait' => trait_exists($fqcn),
            default => false,
        };

        $this->assertTrue($exists, "Failed asserting that {$type} {$fqcn} is autoloadable.");
    }

    public function test_global_helper_functions_are_available(): void
    {
        $this->assertTrue(function_exists('setting'), 'Global helper setting() must be available.');
        $this->assertTrue(function_exists('settings'), 'Global helper settings() must be available.');
    }

    /**
     * @return array<int, array{type: string, fqcn: string}>
     */
    private static function parsePhpFileForDefinitions(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        $tokens = token_get_all($contents);

        $namespace = '';
        $definitions = [];
        $captureNamespace = false;

        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                $captureNamespace = true;

                continue;
            }

            if ($captureNamespace) {
                if (is_array($token)) {
                    $namespace .= $token[1];
                }

                if ($token === ';' || $token === '{') {
                    $namespace = trim($namespace);
                    $namespace = rtrim($namespace, '{');
                    $captureNamespace = false;
                }

                continue;
            }

            if (! is_array($token)) {
                continue;
            }

            $tokenId = $token[0];

            if (in_array($tokenId, [T_CLASS, T_INTERFACE, T_TRAIT], true) || (defined('T_ENUM') && $tokenId === T_ENUM)) {
                $type = match (true) {
                    $tokenId === T_INTERFACE => 'interface',
                    $tokenId === T_TRAIT => 'trait',
                    defined('T_ENUM') && $tokenId === T_ENUM => 'enum',
                    default => 'class',
                };

                $previousToken = $tokens[$index - 1] ?? null;
                if (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
                    continue;
                }

                // Skip anonymous classes.
                $nextIndex = $index + 1;
                while ($nextIndex < $tokenCount && is_array($tokens[$nextIndex]) && in_array($tokens[$nextIndex][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $nextIndex++;
                }

                $nextToken = $tokens[$nextIndex] ?? null;

                if (! is_array($nextToken) || $nextToken[0] !== T_STRING) {
                    continue;
                }

                $name = $nextToken[1];

                $definitions[] = [
                    'type' => $type,
                    'fqcn' => ltrim($namespace.'\\'.$name, '\\'),
                ];
            }
        }

        return $definitions;
    }
}
