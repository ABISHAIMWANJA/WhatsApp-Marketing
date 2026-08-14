<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */


declare(strict_types=1);

namespace App\Yantrana\Services\System;

class ServerPerformanceMonitorService
{
    private float $startTime;
    private array $thresholds;
    private array $metrics;
    private array $warnings;
    private string $status;

    public function __construct(array $customThresholds = [])
    {
        $this->startTime = hrtime(true);
        $this->thresholds = array_merge([
            'cpu_warning' => 0.015,
            'cpu_critical' => 0.03,
            'memory_warning' => 80.0,
            'memory_critical' => 90.0,
            'io_warning' => 0.010
        ], $customThresholds);

        $this->metrics = [
            'cpu_time' => 0.0,
            'memory_used' => 0,
            'memory_percent' => 0.0,
            'total_time' => 0.0
        ];

        $this->warnings = [];
        $this->status = 'normal';
    }

    public function runAnalysis(): self
    {
        $this->measureCpuPerformance();
        $this->analyzeMemoryUsage();
        $this->getSystemLoad();
        $this->measureIoPerformance();

        $this->metrics['total_time'] = (hrtime(true) - $this->startTime) / 1e9;
        $this->evaluateStatus();

        return $this;
    }

    private function measureCpuPerformance(): void
    {
        $start = hrtime(true);
        $x = 0.0;

        for ($i = 1; $i <= 40_000; $i++) {
            $x += sqrt($i) * log($i + 1);
        }

        $this->metrics['cpu_time'] = (hrtime(true) - $start) / 1e9;
    }

    private function analyzeMemoryUsage(): void
    {
        $this->metrics['memory_used'] = memory_get_usage(true);
        $this->metrics['memory_peak'] = memory_get_peak_usage(true);
        $this->metrics['memory_limit'] = ini_get('memory_limit');

        if ($this->metrics['memory_limit'] !== '-1') {
            $limitBytes = $this->convertToBytes($this->metrics['memory_limit']);
            if ($limitBytes > 0) {
                $this->metrics['memory_percent'] = min(
                    100.0,
                    round($this->metrics['memory_used'] / $limitBytes * 100, 1)
                );
            }
        }
    }

    private function getSystemLoad(): void
    {
        if (
            function_exists('sys_getloadavg') &&
            !in_array('sys_getloadavg', explode(',', ini_get('disable_functions') ?? ''), true)
        ) {
            $load = @sys_getloadavg();
            if (is_array($load)) {
                $this->metrics['load_avg'] = array_slice($load, 0, 3);
                return;
            }
        }

        if (is_readable('/proc/loadavg')) {
            $load = @file('/proc/loadavg', FILE_IGNORE_NEW_LINES);
            if ($load !== false && isset($load[0])) {
                $this->metrics['load_avg'] = array_slice(
                    array_map('floatval', explode(' ', $load[0])),
                    0,
                    3
                );
            }
        }
    }

    private function measureIoPerformance(): void
    {
        if (!is_writable('.')) {
            return;
        }

        $testFile = '.perf_test_' . bin2hex(random_bytes(8));
        $start = hrtime(true);

        try {
            if (file_put_contents($testFile, str_repeat('x', 1024)) === false) {
                return;
            }
            $this->metrics['io_time'] = (hrtime(true) - $start) / 1e9;
        } finally {
            @unlink($testFile);
        }
    }

    private function evaluateStatus(): void
    {
        // CPU Evaluation
        if ($this->metrics['cpu_time'] > $this->thresholds['cpu_critical']) {
            $this->status = 'critical';
            $this->warnings[] = 'CPU performance critically degraded';
        } elseif ($this->metrics['cpu_time'] > $this->thresholds['cpu_warning']) {
            $this->status = 'warning';
            $this->warnings[] = 'CPU performance warning';
        }

        // Memory Evaluation
        if ($this->metrics['memory_percent'] > $this->thresholds['memory_critical']) {
            $this->status = 'critical';
            $this->warnings[] = 'Memory usage critical';
        } elseif ($this->metrics['memory_percent'] > $this->thresholds['memory_warning']) {
            if ($this->status !== 'critical') {
                $this->status = 'warning';
            }
            $this->warnings[] = 'Memory usage high';
        }

        // I/O Evaluation
        if (
            isset($this->metrics['io_time']) &&
            $this->metrics['io_time'] > $this->thresholds['io_warning']
        ) {
            $this->warnings[] = 'Storage I/O slower than expected';
            if ($this->status === 'normal') {
                $this->status = 'warning';
            }
        }
    }

    private function convertToBytes(string $value): int
    {
        $unit = strtolower(preg_replace('/[^bkmgtpezy]/i', '', $value) ?: '');
        $bytes = (float)preg_replace('/[^0-9\.]/', '', $value);

        if ($unit) {
            $bytes *= 1024 ** (int)max(0, stripos('bkmgtpezy', $unit[0]));
        }

        return (int)round($bytes);
    }

    public function getResults(): array
    {
        return [
            'status' => $this->status,
            'metrics' => $this->metrics,
            'warnings' => $this->warnings,
            'thresholds' => $this->thresholds
        ];
    }

    public function isNormal(): bool
    {
        return $this->status === 'normal';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    public function isCritical(): bool
    {
        return $this->status === 'critical';
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function terminate(): never
    {
        http_response_code(503); // Service Unavailable
        header('Retry-After: 30'); // Suggest retry after 30 seconds
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "CRITICAL: " . PHP_EOL);
            exit('server busy');
        }
        die(json_encode([
            'status' => 'error',
            'message' => 'server busy',
            'metrics' => $this->metrics
        ]));
    }
}