<?php
$url = "http://localhost:8000/api/lecturas?serial_number=ULEAMAQI&clave_mqtt=temperatura&filter_mode=hour&start_date=2026-07-06&hour=6";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo "Response:\n";
echo substr($response, 0, 500) . "\n";
