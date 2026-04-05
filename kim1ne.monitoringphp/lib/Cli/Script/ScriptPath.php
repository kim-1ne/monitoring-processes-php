<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

readonly class ScriptPath
{
    public function __construct(
        public string $path,
        private array $excludeFiles = [],
    ) {}

    public function getScriptPath(string $scriptName): ?string
    {
        $script = $this->path . $scriptName;

        if (!file_exists($script)) {
            return null;
        }

        return $script;
    }

    /**
     * @return array<array{name: string, path: string, size: int, modified: int}>
     */
    public function getScripts(): array
    {
        $scripts = [];

        if (!is_dir($this->path)) {
            return $scripts;
        }

        foreach (glob($this->path . '*.php') as $file) {
            $filename = basename($file);

            if (in_array($filename, $this->excludeFiles, true)) {
                continue;
            }

            $scripts[] = [
                'name' => $filename,
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
            ];
        }

        return $scripts;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getExcludeFiles(): array
    {
        return $this->excludeFiles;
    }
}
