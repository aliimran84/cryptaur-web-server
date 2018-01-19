<?php

namespace core\engine;

class Configuration
{
    static public $CONFIG = [];

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

        if (!isset($config['application_url'])) {
            $needConfigure = true;
            $config['!_application_url'] = '';
        } else {
            $config['application_url'] = trim($config['application_url'], '/');
            DEFINE('APPLICATION_URL', $config['application_url']);
        }

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

        if (!isset($config['email_smtp'])) {
            $needConfigure = true;
            $config['!_email_smtp'] = [
                'host' => 'localhost',
                'port' => 0,
                'login' => '',
                'password' => '',
                'secure' => 'tls',
                'reply_to_email' => '',
                'reply_to_name' => '',
                'from_email' => '',
                'from_name' => ''
            ];
        } else {
            DEFINE('EMAIL_HOST', $config['email_smtp']['host']);
            DEFINE('EMAIL_PORT', $config['email_smtp']['port']);
            DEFINE('EMAIL_LOGIN', $config['email_smtp']['login']);
            DEFINE('EMAIL_SECURE', $config['email_smtp']['secure']);
            DEFINE('EMAIL_PASSWORD', $config['email_smtp']['password']);
            DEFINE('EMAIL_REPLY_TO_EMAIL', $config['email_smtp']['reply_to_email']);
            DEFINE('EMAIL_REPLY_TO_NAME', $config['email_smtp']['reply_to_name']);
            DEFINE('EMAIL_FROM_NAME', $config['email_smtp']['from_name']);
            DEFINE('EMAIL_FROM_EMAIL', $config['email_smtp']['from_email']);
        }

        if (!isset($config['2fa'])) {
            $needConfigure = true;
            $config['!_2fa'] = [
                'use_2fa' => false
            ];
        } else {
            DEFINE('USE_2FA', $config['2fa']['use_2fa']);
        }
        
        if (!isset($config['sms_url'])) {
            $needConfigure = true;
            $config['!_sms_url'] = "";
        } else {
            DEFINE('SMS_URL', $config['sms_url']);
        }

        if (!isset($config['coins'])) {
            $coins = [
                'ETH' => [
                    'min_conirmation' => 12,
                    'activate' => true
                ],
                'BTC' => [
                    'min_conirmation' => 3,
                    'activate' => true
                ],
                'DOGE' => [
                    'min_conirmation' => 6,
                    'activate' => true
                ],
                'ETC' => [
                    'min_conirmation' => 10,
                    'activate' => false
                ],
                'BTG' => [
                    'min_conirmation' => 10,
                    'activate' => false
                ],
                'BCC' => [
                    'min_conirmation' => 10,
                    'activate' => false
                ]
            ];
            $config['coins'] = $coins;
        }

        if (!isset($config['token'])) {
            $config['token'] = [
                'name' => 'CPT'
            ];
        }

        if (!isset($config['eth'])) {
            $config['eth'] = [
                'bounty_dispenser' => '',
                'bounty_password' => '',
                'bounty_node_url' => '',
                'bounty_cold_wallet' => '',
                'tokens_contract' => '',
                'tokens_wallet' => '',
                'tokens_password' => '',
                'tokens_node_url' => '',
                'queue_url' => '',
                'queue_key' => ''
            ];
        }
        DEFINE('ETH_BOUNTY_DISPENSER', $config['eth']['bounty_dispenser']);
        DEFINE('ETH_BOUNTY_PASSWORD', $config['eth']['bounty_password']);
        DEFINE('ETH_BOUNTY_NODE_URL', $config['eth']['bounty_node_url']);
        DEFINE('ETH_BOUNTY_COLD_WALLET', $config['eth']['bounty_cold_wallet']);
        DEFINE('ETH_TOKENS_CONTRACT', $config['eth']['tokens_contract']);
        DEFINE('ETH_TOKENS_WALLET', $config['eth']['tokens_wallet']);
        DEFINE('ETH_TOKENS_PASSWORD', $config['eth']['tokens_password']);
        DEFINE('ETH_TOKENS_NODE_URL', $config['eth']['tokens_node_url']);
        DEFINE('ETH_QUEUE_URL', trim($config['eth']['queue_url'], '/'));
        DEFINE('ETH_QUEUE_KEY', $config['eth']['queue_key']);

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

        Configuration::$CONFIG = $config;

        return ['success' => true];
    }
}
