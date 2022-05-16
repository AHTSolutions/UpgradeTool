<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess\Processors\ExtendedMethods;

class DataExtractor
{
    /**
     * @param string $filePath
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    public function extractMethodCode(string $filePath, \ReflectionMethod $method): string
    {
        $tokens = $this->getCodeTokens($filePath);
        $methodStartPosition = $this->getMethodStartPosition($method, $tokens);

        if ($methodStartPosition !== null) {
            $endPosition = $this->getMethodBodyEndPosition($methodStartPosition, $tokens);

            if ($endPosition !== null && $endPosition > $methodStartPosition) {
                return $this->getContentFromTokens($methodStartPosition, $endPosition, $tokens);
            }
        }

        return '';
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    protected function getCodeTokens(string $filePath): array
    {
        if (file_exists($filePath)) {
            return token_get_all(file_get_contents($filePath));
        }

        return [];
    }

    /**
     * @param \ReflectionMethod $method
     * @param array $tokens
     *
     * @return int|null
     */
    protected function getMethodStartPosition(\ReflectionMethod $method, array $tokens): ?int
    {
        $cnt = count($tokens);

        for ($iter = 0; $iter < $cnt; ++$iter) {
            $token = $tokens[$iter];

            if (is_array($token) && $token[0] == T_FUNCTION) {
                $position = $this->checkMethodName(
                    $iter,
                    $tokens,
                    $method->getName(),
                    $this->getMethodTokenType($method)
                );

                if ($position !== null) {
                    return $position;
                }
            }
        }

        return null;
    }

    /**
     * @param int $startPosition
     * @param array $tokens
     *
     * @return int|null
     */
    protected function getMethodBodyEndPosition(int $startPosition, array $tokens): ?int
    {
        for ($braceIter = $startPosition; $braceIter <= $startPosition + 100; ++$braceIter) {
            $token = $tokens[$braceIter];

            if (is_string($token) && $token == '{') {
                break;
            }
        }

        if ($braceIter == $startPosition + 100) {
            return null;
        }
        $braceCounter = 1;

        while ($braceCounter != 0 && isset($tokens[$braceIter+1])) {
            $token = $tokens[++$braceIter];

            if (is_string($token) && $token == '{') {
                $braceCounter++;
            }

            if (is_string($token) && $token == '}') {
                $braceCounter--;
            }
        }

        return $braceIter !== count($tokens) - 1 ? $braceIter : null;
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return int
     */
    protected function getMethodTokenType(\ReflectionMethod $method): int
    {
        if ($method->isPublic()) {
            return T_PUBLIC;
        } elseif ($method->isProtected()) {
            return T_PROTECTED;
        }

        return T_PRIVATE;
    }

    /**
     * @param int $iter
     * @param array $tokens
     * @param string $methodName
     * @param int $methodType
     *
     * @return int|null
     */
    protected function checkMethodName(int $iter, array $tokens, string $methodName, int $methodType): ?int
    {
        for ($nameIter = $iter; $nameIter <= $iter + 10; $nameIter++) {
            $token = $tokens[$nameIter];

            if (is_array($token) && $token[0] == T_STRING && $token[1] == $methodName) {
                for ($startFunctionIter = $iter; $startFunctionIter > $iter - 20; --$startFunctionIter) {
                    $token = $tokens[$startFunctionIter] ?? null;

                    if (is_array($token) && $token[0] == $methodType) {
                        break;
                    }
                }

                return $startFunctionIter > 0 ? $startFunctionIter : null;
            }
        }

        return null;
    }

    /**
     * @param int $startTokenPos
     * @param int $endTokenPos
     * @param array $tokens
     *
     * @return string
     */
    protected function getContentFromTokens(int $startTokenPos, int $endTokenPos, array $tokens): string
    {
        $result = '';

        for ($iter = $startTokenPos; $iter <= $endTokenPos; $iter++) {
            $token = $tokens[$iter];

            if (is_string($token)) {
                $result .= $token;
            } else {
                list($ident, $txt) = $token;

                if (!in_array($ident, [T_COMMENT, T_DOC_COMMENT])) {
                    $result .= $txt;
                }
            }
        }

        return $result;
    }
}
