<?php

require __DIR__ . '/loader.php';

use core\engine\Application;

Application::init();

\core\models\EthQueue::checkQueue();