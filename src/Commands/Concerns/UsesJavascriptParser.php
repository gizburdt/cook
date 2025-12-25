<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait UsesJavascriptParser
{
    protected function addInputToViteConfig(string $file, string $input): bool
    {
        if (! $this->files->exists($file)) {
            return false;
        }

        $content = $this->files->get($file);

        if (str_contains($content, $input)) {
            return false;
        }

        // Multi-line format
        $pattern = "/(input:\s*\[\s*\n)(\s*)('[^']+',?\s*\n)/";

        $replacement = "$1$2$3$2'{$input}',\n";

        $newContent = preg_replace($pattern, $replacement, $content, 1);

        // Single-line format
        if ($newContent === $content) {
            $pattern = "/(input:\s*\[)('[^']+')/";

            $replacement = "$1$2, '{$input}'";

            $newContent = preg_replace($pattern, $replacement, $content, 1);
        }

        if ($newContent === $content) {
            return false;
        }

        $this->files->put($file, $newContent);

        return true;
    }
}
