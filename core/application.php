<?php

namespace core;

class Application
{
    static public function init()
    {
        define('PATH_TO_WORKING_DIR', __DIR__ . '/../working_dir');
    }
}