<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$eth_queues = DB::get("
    SELECT * FROM eth_queue WHERE investor_id = 0
;");

foreach ($eth_queues as $i => $eth_queue) {
    echo "$i/" . count($eth_queues) . "\r\n";
    $data = @json_decode($eth_queue['data'], true);
    if (!$data['investorId']) {
        continue;
    }
    DB::set("
        UPDATE eth_queue
        SET
            `investor_id` = ?,
            datetime_end = NOW()
        WHERE
            id = ?
    ;", [$data['investorId'], $eth_queue['id']]);
}