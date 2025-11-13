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

        $tokens = token_get_all($contents);
        $namespace = '';
        $definitions = [];
        $tokenCount = count($tokens);
        $captureNamespace = false;

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                $captureNamespace = true;

                continue;
            }

            if ($captureNamespace) {
                if (is_array($token) && in_array($token[0], self::namespaceTokenTypes(), true)) {
                    $namespace .= $token[1];
                } elseif ($token === ';' || $token === '{') {
                    $captureNamespace = false;
                    $namespace = trim($namespace);
                }

                continue;
            }

            if (! is_array($token)) {
                continue;
            }

            $tokenId = $token[0];

            if (! in_array($tokenId, self::classLikeTokenTypes(), true)) {
                continue;
            }

            $previousToken = self::previousSignificantToken($tokens, $index);

            if (self::isStaticResolutionToken($previousToken) || self::isAnonymousClassToken($previousToken)) {
                continue;
            }

            $nameToken = self::nextSignificantToken($tokens, $index);

            if (! is_array($nameToken) || $nameToken[0] !== T_STRING) {
                continue;
            }

            $definitions[] = [
                'type' => self::classLikeTypeFromToken($tokenId),
                'fqcn' => ltrim(($namespace !== '' ? $namespace.'\\' : '').$nameToken[1], '\\'),
            ];
        }

        return $definitions;
    }

    private static function appDirectory(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app';
    }

    /**
     * @param  array<int, array{0:int, 1:string, 2:int}|string>  $tokens
     * @return array{0:int, 1:string, 2:int}|string|null
     */
    private static function previousSignificantToken(array $tokens, int $startIndex): array|string|null
    {
        for ($index = $startIndex - 1; $index >= 0; $index--) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], self::ignorableTokenTypes(), true)) {
                continue;
            }

            if (! is_array($token) && trim((string) $token) === '') {
                continue;
            }

            return $token;
        }

        return null;
    }

    /**
     * @param  array<int, array{0:int, 1:string, 2:int}|string>  $tokens
     * @return array{0:int, 1:string, 2:int}|null
     */
    private static function nextSignificantToken(array $tokens, int $startIndex): ?array
    {
        $tokenCount = count($tokens);

        for ($index = $startIndex + 1; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], self::ignorableTokenTypes(), true)) {
                continue;
            }

            if (! is_array($token)) {
                if ($token === '{' || $token === '(') {
                    return null;
                }

                if (trim((string) $token) === '') {
                    continue;
                }

                return null;
            }

            return $token;
        }

        return null;
    }

    /**
     * @param  array{0:int, 1:string, 2:int}|string|null  $token
     */
    private static function isStaticResolutionToken(array|string|null $token): bool
    {
        if ($token === null) {
            return false;
        }

        if (is_array($token)) {
            return $token[0] === T_DOUBLE_COLON;
        }

        return $token === '::';
    }

    /**
     * @param  array{0:int, 1:string, 2:int}|string|null  $token
     */
    private static function isAnonymousClassToken(array|string|null $token): bool
    {
        if ($token === null) {
            return false;
        }

        if (is_array($token)) {
            return $token[0] === T_NEW;
        }

        return false;
    }

    /**
     * @return array<int, int>
     */
    private static function namespaceTokenTypes(): array
    {
        $tokens = [T_STRING, T_NS_SEPARATOR];

        if (defined('T_NAME_QUALIFIED')) {
            $tokens[] = constant('T_NAME_QUALIFIED');
        }

        if (defined('T_NAME_FULLY_QUALIFIED')) {
            $tokens[] = constant('T_NAME_FULLY_QUALIFIED');
        }

        return $tokens;
    }

    /**
     * @return array<int, int>
     */
    private static function classLikeTokenTypes(): array
    {
        $tokens = [T_CLASS, T_INTERFACE, T_TRAIT];

        if (defined('T_ENUM')) {
            $tokens[] = T_ENUM;
        }

        return $tokens;
    }

    /**
     * @return array<int, int>
     */
    private static function ignorableTokenTypes(): array
    {
        $tokens = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

        if (defined('T_ATTRIBUTE')) {
            $tokens[] = T_ATTRIBUTE;
        }

        return $tokens;
    }

    private static function classLikeTypeFromToken(int $tokenId): string
    {
        return match (true) {
            $tokenId === T_INTERFACE => 'interface',
            $tokenId === T_TRAIT => 'trait',
            defined('T_ENUM') && $tokenId === T_ENUM => 'enum',
            default => 'class',
        };
    }
}
