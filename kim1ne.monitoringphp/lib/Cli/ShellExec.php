<?php

namespace Kim1ne\MonitoringPhp\Cli;

class ShellExec
{
    /**
     * Выполнить команду и получить вывод
     */
    public static function run(string $command, array $args = []): ?string
    {
        $fullCommand = self::buildCommand($command, $args);
        $output = shell_exec($fullCommand);

        return $output !== false ? trim($output) : null;
    }

    /**
     * Запустить команду в фоне и получить PID
     */
    public static function runBackground(string $command, array $args = []): ?int
    {
        $fullCommand = self::buildCommand($command, $args);
        $fullCommand .= ' > /dev/null 2>&1 & echo $!';

        $pid = shell_exec($fullCommand);
        $pid = trim($pid);

        return is_numeric($pid) ? (int)$pid : null;
    }

    private static function buildCommand(string $command, array $args): string
    {
        $escapedArgs = array_map('escapeshellarg', $args);
        return $command . ($escapedArgs ? ' ' . implode(' ', $escapedArgs) : '');
    }

    /**
     * Выполнить произвольную shell-команду
     */
    public static function runString(string $command): ?string
    {
        $output = shell_exec($command);
        return $output !== false ? trim($output) : null;
    }
}
