<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

Application::init();

\core\controllers\Base_controller::icoInfo(true);