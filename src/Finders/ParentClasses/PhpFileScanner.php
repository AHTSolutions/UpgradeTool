<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\ParentClasses;

class PhpFileScanner
{
    /**
     * @param string $file
     * @return array
     */
    public function getDeclaredClasses(string $file): array
    {
        $classes = [];
        $namespaceParts = [];
        // phpcs:ignore
        $tokens = token_get_all(file_get_contents($file));
        $count = count($tokens);

        for ($tokenIterator = 0; $tokenIterator < $count; $tokenIterator++) {
            if ($tokens[$tokenIterator][0] == T_NAMESPACE) {
                $namespaceParts[] = $this->fetchNamespace($tokenIterator, $count, $tokens);
            }

            if (($tokens[$tokenIterator][0] == T_CLASS || $tokens[$tokenIterator][0] == T_INTERFACE)
                && $tokens[$tokenIterator - 1][0] != T_DOUBLE_COLON
            ) {
                $class = $this->fetchClass(join('', $namespaceParts), $tokenIterator, $count, $tokens);

                if ($class !== null && !in_array($class, $classes)) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * @param int $tokenIterator
     * @param int $count
     * @param array $tokens
     *
     * @return string
     */
    protected function fetchNamespace(int $tokenIterator, int $count, array $tokens): string
    {
        $namespaceParts = [];

        for ($tokenOffset = $tokenIterator + 1; $tokenOffset < $count; ++$tokenOffset) {
            if ($tokens[$tokenOffset][0] === T_STRING) {
                $namespaceParts[] = '\\';
                $namespaceParts[] = $tokens[$tokenOffset][1];
            } elseif ($tokens[$tokenOffset] === '{' || $tokens[$tokenOffset] === ';') {
                break;
            }
        }

        return join('', $namespaceParts);
    }

    /**
     * @param string $namespace
     * @param int $tokenIterator
     * @param int $count
     * @param array $tokens
     *
     * @return string|null
     */
    protected function fetchClass(string $namespace, int $tokenIterator, int $count, array $tokens): ?string
    {
        if (isset($tokens[$tokenIterator - 2]) && is_array($tokens[$tokenIterator - 2]) && $tokens[$tokenIterator - 2][0] === T_NEW) {
            return null;
        }

        for ($tokenOffset = $tokenIterator + 1; $tokenOffset < $count; ++$tokenOffset) {
            if ($tokens[$tokenOffset] !== '{') {
                continue;
            }

            return $namespace . '\\' . $tokens[$tokenIterator + 2][1];
        }

        return null;
    }
}
