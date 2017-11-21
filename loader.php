<?php

namespace loader;

function load_sources_dir($path)
{
    $dir = new \RecursiveDirectoryIterator($path);
    $iterator = new \RecursiveIteratorIterator($dir);
    foreach ($iterator as $file) {
        $fname = $file->getFilename();
        if (preg_match('%\.php$%', $fname)) {
            require($file->getPathname());
        }
    }
}

load_sources_dir(__DIR__ . '/core');