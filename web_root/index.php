<?php

require __DIR__ . '/../loader.php';

$path_info = \core\router\parse_path();
switch ($path_info['call_parts'][0]) {
    case 'test':
        var_dump($path_info);
        break;
    default:
        echo 'index.php';
}