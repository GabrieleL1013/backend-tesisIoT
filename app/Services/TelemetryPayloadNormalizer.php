<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TelemetryPayloadNormalizer
{
    public function normalize(array $payload, ?string $topic = null): ?array
    {
        $serialNumber = $this->extractSerialNumber($payload, $topic);

        if (!$serialNumber) {
            return null;
        }

        $metrics = $this->extractMetrics($payload);

        if (empty($metrics)) {
            return null;
        }

        $timestamp = $this->extractTimestamp($payload);
        $dateTime = $this->extractDateTime($payload, $timestamp);

        return [
            'serial_number' => $serialNumber,
            'metrics' => $metrics,
            'timestamp' => $timestamp,
            'dateTime' => $dateTime,
            'topic' => $topic,
        ];
    }

    protected function extractSerialNumber(array $payload, ?string $topic = null): ?string
    {
        $serialCandidates = [
            data_get($payload, 'Sensor'),
            data_get($payload, 'sensor'),
            data_get($payload, 'serial_number'),
            data_get($payload, 'deviceInfo.devEui'),
            data_get($payload, 'devEui'),
            data_get($payload, 'endDeviceIds.device_id'),
            data_get($payload, 'endDeviceIds.dev_eui'),
            data_get($payload, 'deviceInfo.deviceName'),
        ];

        foreach ($serialCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        if ($topic && preg_match('#/device/([^/]+)/event/up$#', $topic, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function extractMetrics(array $payload): array
    {
        $source = $this->resolveMetricSource($payload);
        $flatMetrics = $this->flattenMetrics($source);

        $metrics = [];

        foreach ($flatMetrics as $key => $value) {
            if ($this->shouldIgnoreMetricKey($key)) {
                continue;
            }

            $normalizedValue = $this->normalizeMetricValue($value);

            if ($normalizedValue === null) {
                continue;
            }

            $metrics[$key] = $normalizedValue;
        }

        return $metrics;
    }

    protected function shouldIgnoreMetricKey(string $key): bool
    {
        return $key === 'flags'
            || str_ends_with($key, '_valid')
            || str_ends_with($key, '_high');
    }

    protected function resolveMetricSource(array $payload): array
    {
        $rawData = data_get($payload, 'data');

        if (is_string($rawData) && trim($rawData) !== '') {
            $jsonDecoded = json_decode($rawData, true);
            if (is_array($jsonDecoded) && $jsonDecoded !== []) {
                return $jsonDecoded;
            }

            $base64Decoded = base64_decode($rawData, true);
            if ($base64Decoded !== false) {
                $base64Json = json_decode($base64Decoded, true);
                if (is_array($base64Json) && $base64Json !== []) {
                    return $base64Json;
                }
            }
        }

        foreach (['object', 'decodedObject'] as $preferredKey) {
            $candidate = data_get($payload, $preferredKey);

            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return Arr::except($payload, [
            'Sensor',
            'sensor',
            'serial_number',
            'timestamp',
            'dateTime',
            'time',
            'dt',
            'ts',
            'sensor_status',
            'object',
            'decodedObject',
            'data',
            'deviceInfo',
            'applicationID',
            'applicationName',
            'devEUI',
            'devEui',
            'deduplicationId',
            'fCnt',
            'fPort',
            'adr',
            'dr',
            'txInfo',
            'rxInfo',
            'tags',
            'variables',
            'confirmedUplink',
        ]);
    }

    protected function flattenMetrics(array $values, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($values as $key => $value) {
            $normalizedKey = Str::of((string) $key)
                ->trim()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/i', '_')
                ->trim('_')
                ->value();

            if ($normalizedKey === '') {
                continue;
            }

            $fullKey = $prefix === '' ? $normalizedKey : $prefix . '_' . $normalizedKey;

            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenMetrics($value, $fullKey));
                continue;
            }

            $flattened[$fullKey] = $value;
        }

        return $flattened;
    }

    protected function normalizeMetricValue(mixed $value): float|int|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    protected function extractTimestamp(array $payload): int
    {
        $timestamp = data_get($payload, 'timestamp') ?? data_get($payload, 'ts');

        if (is_numeric($timestamp)) {
            return (int) $timestamp;
        }

        $timeCandidate = $this->extractTimeCandidate($payload);

        if (is_string($timeCandidate) && trim($timeCandidate) !== '') {
            try {
                return $this->parseDateTime($timeCandidate)->timestamp;
            } catch (\Throwable) {
            }
        }

        return now()->timestamp;
    }

    protected function extractDateTime(array $payload, int $timestamp): string
    {
        $timeCandidate = $this->extractTimeCandidate($payload);

        if (is_string($timeCandidate) && trim($timeCandidate) !== '') {
            try {
                return $this->parseDateTime($timeCandidate)->toDateTimeString();
            } catch (\Throwable) {
            }
        }

        return Carbon::createFromTimestamp($timestamp)->toDateTimeString();
    }

    protected function extractTimeCandidate(array $payload): ?string
    {
        foreach (['dateTime', 'dt', 'time'] as $field) {
            $candidate = data_get($payload, $field);

            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    protected function parseDateTime(string $value): Carbon
    {
        foreach (['d/m/Y H:i:s', 'Y-m-d H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value, 'America/Guayaquil');
            } catch (\Throwable) {
            }
        }

        return Carbon::parse($value);
    }
}
