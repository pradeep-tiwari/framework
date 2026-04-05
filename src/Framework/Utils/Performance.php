<?php

namespace Lightpack\Utils;

class Performance
{
    private static array $markers = [];

    /**
     * Mark a checkpoint in execution
     */
    public static function mark(string $label): void
    {
        self::$markers[$label] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(),
        ];
    }

    /**
     * Get execution time in milliseconds
     */
    public static function getExecutionTime(): float
    {
        if (!defined('LIGHTPACK_START')) {
            return 0.0;
        }

        return round((microtime(true) - LIGHTPACK_START) * 1000, 2);
    }

    /**
     * Get peak memory usage in MB
     */
    public static function getPeakMemory(): float
    {
        return round(memory_get_peak_usage() / 1024 / 1024, 2);
    }

    /**
     * Get all performance metrics
     */
    public static function getMetrics(): array
    {
        return [
            'execution_time_ms' => self::getExecutionTime(),
            'peak_memory_mb' => self::getPeakMemory(),
            'markers' => self::formatMarkers(),
        ];
    }

    /**
     * Format markers with relative times
     */
    private static function formatMarkers(): array
    {
        if (empty(self::$markers) || !defined('LIGHTPACK_START')) {
            return [];
        }

        $formatted = [];
        foreach (self::$markers as $label => $data) {
            $formatted[$label] = [
                'time_ms' => round(($data['time'] - LIGHTPACK_START) * 1000, 2),
                'memory_mb' => round($data['memory'] / 1024 / 1024, 2),
            ];
        }

        return $formatted;
    }

    /**
     * Log metrics to error log
     */
    public static function log(): void
    {
        $metrics = self::getMetrics();
        error_log(sprintf(
            '[Performance] Time: %sms | Peak Memory: %sMB',
            $metrics['execution_time_ms'],
            $metrics['peak_memory_mb']
        ));
    }

    /**
     * Reset all tracking data
     */
    public static function reset(): void
    {
        self::$markers = [];
    }
}
