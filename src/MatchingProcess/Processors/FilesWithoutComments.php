<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess\Processors;

class FilesWithoutComments implements ProcessorInterface
{
    /**
     * @inheriDoc
     *
     * @param string $previousFile
     * @param string $currentFile
     *
     * @return bool
     */
    public function checkFiles(string $previousFile, string $currentFile): bool
    {
        $prContent = file_get_contents($previousFile);
        $crContent = file_get_contents($currentFile);
        $prHash = hash('sha1', $this->cleanPHPComments($prContent));
        $crHash = hash('sha1', $this->cleanPHPComments($crContent));

        return $prHash === $crHash;
    }

    /**
     * @inheriDoc
     */
    public function setOriginalClassName(?string $className)
    {
        // not needed
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function cleanPHPComments(string $content): string
    {
        $tokens = token_get_all($content);
        $result = '';

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $result .= $token;
            } else {
                [$ident, $txt] = $token;

                if (!in_array($ident, [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)) {
                    $result .= $txt;
                }
            }
        }

        return $result;
    }
}
