<?php

namespace Tests\Unit;

use App\Services\TelemetryPayloadNormalizer;
use PHPUnit\Framework\TestCase;

class TelemetryPayloadNormalizerTest extends TestCase
{
    public function test_normalizes_flat_payloads(): void
    {
        $normalizer = new TelemetryPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'Sensor' => 'ULEAMAQI',
            'temperatura' => 24.8,
            'humedad' => '61.2',
            'timestamp' => 1_785_189_600,
        ]);

        $this->assertSame('ULEAMAQI', $normalized['serial_number']);
        $this->assertSame(24.8, $normalized['metrics']['temperatura']);
        $this->assertSame(61.2, $normalized['metrics']['humedad']);
    }

    public function test_normalizes_chirpstack_object_payloads(): void
    {
        $normalizer = new TelemetryPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'deviceInfo' => [
                'deviceName' => 'rak-node-01',
                'devEui' => 'AABBCCDDEEFF0011',
            ],
            'time' => '2026-07-28T14:25:00Z',
            'object' => [
                'temperature' => 22.5,
                'humidity' => 58,
                'battery' => [
                    'voltage' => 3.71,
                ],
            ],
        ], 'application/1/device/AABBCCDDEEFF0011/event/up');

        $this->assertSame('rak-node-01', $normalized['serial_number']);
        $this->assertSame(22.5, $normalized['metrics']['temperature']);
        $this->assertSame(58, $normalized['metrics']['humidity']);
        $this->assertSame(3.71, $normalized['metrics']['battery_voltage']);
    }

    public function test_normalizes_water_sensor_payload_without_status_flags_as_metrics(): void
    {
        $normalizer = new TelemetryPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'Sensor' => 'ULEAMCENTRAL02',
            'temperatura' => null,
            'ph' => 12.11,
            'oxigeno_disuelto' => 3.6,
            'potenciometro_1' => 1.33,
            'potenciometro_2' => 1.64,
            'ec_us_cm' => 0,
            'ec_temperature_c' => 25.7,
            'salinity_ppm' => 0,
            'tds_ppm' => 0,
            'turbidity_ntu' => 1000.0,
            'turbidity_temperature_c' => 26.3,
            'timestamp' => 1785341760,
            'dateTime' => '29/07/2026 11:16:00',
            'sensor_status' => [
                'board' => true,
                'temperature' => false,
                'ph' => true,
            ],
        ]);

        $this->assertSame('ULEAMCENTRAL02', $normalized['serial_number']);
        $this->assertArrayNotHasKey('temperatura', $normalized['metrics']);
        $this->assertSame(12.11, $normalized['metrics']['ph']);
        $this->assertSame(3.6, $normalized['metrics']['oxigeno_disuelto']);
        $this->assertSame(25.7, $normalized['metrics']['ec_temperature_c']);
        $this->assertSame(1000.0, $normalized['metrics']['turbidity_ntu']);
        $this->assertArrayNotHasKey('sensor_status_board', $normalized['metrics']);
        $this->assertSame('2026-07-29 11:16:00', $normalized['dateTime']);
        $this->assertSame(1785341760, $normalized['timestamp']);
    }
}
