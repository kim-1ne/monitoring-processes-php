<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

readonly class WhiteList
{
    /**
     * @var ScriptPath[]
     */
    private array $paths;

    public function __construct(
        ScriptPath ...$paths,
    ) {
        $this->paths = $paths;
    }

    public function resolveAllowedPath(string $fullPath): ?string
    {
        $realFullPath = realpath($fullPath);

        if ($realFullPath === false) {
            return null;
        }

        foreach ($this->paths as $path) {
            $realAllowedPath = realpath($path->path);

            if ($realAllowedPath === false) {
                continue;
            }

            if (str_starts_with($realFullPath, $realAllowedPath)) {
                return $realFullPath;
            }
        }

        return null;
    }

    /**
     * @return array<array{name: string, path: string, size: int, modified: int}>
     */
    public function getScripts(): array
    {
        $allScripts = [];
        $seenPaths = [];

        foreach ($this->paths as $path) {
            $scripts = $path->getScripts();

            foreach ($scripts as $script) {
                if (!in_array($script['path'], $seenPaths, true)) {
                    $seenPaths[] = $script['path'];
                    $allScripts[] = $script;
                }
            }
        }

        return $allScripts;
    }
}
