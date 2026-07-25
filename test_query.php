<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lectura;
use Carbon\Carbon;

$node_id = \App\Models\Node::where('serial_number', 'ULEAMAQI')->first()->id;

$dateStr = '2026-07-06';
$hourStr = '06';

$start = Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $hourStr:00:00", 'America/Guayaquil')->setTimezone('UTC');
$end = $start->copy()->addHour();

echo "Local Start: $dateStr $hourStr:00:00\n";
echo "UTC Start: " . $start->format('Y-m-d H:i:s') . "\n";
echo "UTC End: " . $end->format('Y-m-d H:i:s') . "\n";

$count = Lectura::where('node_id', $node_id)->whereBetween('created_at', [$start, $end])->count();
echo "Count for hour 06 (local): $count\n";

$hourStr = '05';
$start = Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $hourStr:00:00", 'America/Guayaquil')->setTimezone('UTC');
$end = $start->copy()->addHour();
$count = Lectura::where('node_id', $node_id)->whereBetween('created_at', [$start, $end])->count();
echo "Count for hour 05 (local): $count\n";

// Let's print the actual boundaries in DB
$first = Lectura::where('node_id', $node_id)->orderBy('created_at', 'asc')->first();
$last = Lectura::where('node_id', $node_id)->orderBy('created_at', 'desc')->first();
if ($first && $last) {
    echo "First record: " . $first->created_at . "\n";
    echo "Last record: " . $last->created_at . "\n";
}
