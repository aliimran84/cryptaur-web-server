<?php

namespace core;

class Configuration
{

    /**
     * @param string $configFile full path to config json file
     * @return bool
     */
    static public function requireLoadConfigFromFile($configFile)
    {
        $result = self::loadConfigFromFile($configFile);
        if ($result['success']) {
            return true;
        }
        echo "---Stop on loading config---<br><br>\n\n";
        echo $result['error'];
        exit;
    }

    /**
     * @param string $configFile full path to config json file
     * @return array
     */
    static public function loadConfigFromFile($configFile)
    {
        $oldConfig = [];

        if (is_file($configFile)) {
            $oldConfig = json_decode(file_get_contents($configFile), true);
            if (is_null($oldConfig)) {
                return [
                    'success' => false,
                    'error' => 'CONFIG NOT VALID JSON: ' . $configFile
                ];
            }
        }

        $config = $oldConfig;

        /**
         * bool нужно ли вмешаться администратору для конфигурирования
         */
        $needConfigure = false;

        if (!isset($config['application_id'])) {
            $config['application_id'] = uniqid();
        }
        DEFINE('APPLICATION_ID', $config['application_id']);

        if (!isset($config['db'])) {
            $needConfigure = true;
            $config['!_db'] = [
                'host' => 'localhost',
                'login' => '',
                'password' => '',
                'db_name' => ''
            ];
        } else {
            DEFINE('DB_HOST', $config['db']['host']);
            DEFINE('DB_LOGIN', $config['db']['login']);
            DEFINE('DB_PASSWORD', $config['db']['password']);
            DEFINE('DB_NAME', $config['db']['db_name']);
        }

        // если конфиг отличается после проверки всех параметров
        if (json_encode($config) !== json_encode($oldConfig)) {
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))) {
                error_log("CONFIG CHANGES SAVED\nNEW:\n" . json_encode($config) . "\nOLD:\n" . json_encode($oldConfig));
            } else {
                return [
                    'success' => false,
                    'error' => 'ERROR CREATE CONFIG'
                ];
            }
        }

        if ($needConfigure) {
            return [
                'success' => false,
                'error' => "NEED CONFIGURE: $configFile.\n<br>Change properties started with !_ and remove !_"
            ];
        }

        return ['success' => true];
    }
}
