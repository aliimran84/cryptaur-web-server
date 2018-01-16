<?php

namespace core\models;

use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

class EthQueue
{
    const TYPE_MINT_REINVEST = 1;
    const TYPE_MINT_DEPOSIT = 2;
    const TYPE_MINT_OLD_INVESTOR_INIT = 3;

    public $id = 0;
    public $uuid = '';
    public $datetime = 0;
    public $action_type = 0;
    public $is_pending = true;
    public $is_success = false;
    public $result = '';
    public $data = [];

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `eth_queue` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `uuid` varchar(40) NOT NULL,
                `datetime` datetime(0) NOT NULL,
                `action_type` tinyint(1) UNSIGNED DEFAULT '0',
                `is_pending` tinyint(1) UNSIGNED DEFAULT '0',
                `is_success` tinyint(1) UNSIGNED DEFAULT '0',
                `result` varchar(1024) DEFAULT '',
                `data` varchar(4096) DEFAULT '',
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

    /**
     * @param array $data
     * @return EthQueue
     */
    static public function constructFromDbData($data)
    {
        $instance = new EthQueue();
        $instance->id = $data['id'];
        $instance->uuid = $data['uuid'];
        $instance->datetime = strtotime($data['datetime']);
        $instance->action_type = $data['action_type'];
        $instance->is_pending = (bool)$data['is_pending'];
        $instance->is_success = (bool)$data['is_success'];
        $instance->result = $data['result'];
        $instance->data = json_decode($data['data'], true);
        return $instance;
    }

    /**
     * @param int $actionType
     * @param array $data
     * @return string
     */
    static private function new_queue($actionType, $data)
    {
        $uuid = Utility::uuid();
        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        DB::set("
            INSERT INTO `eth_queue` SET
                `uuid` = ?,
                `datetime` = NOW(),
                `action_type` = ?,
                `is_pending` = 1,
                `is_success` = 0,
                `result` = '',
                `data` = ?
        ;", [$uuid, $actionType, $json_data]);
        return $uuid;
    }

    /**
     * @param int $actionType
     * @param array $data
     * @param string $ethAddress
     * @param double $tokens
     * @param string $coin
     * @param string $txid
     * @return array [int, string] if int < 0 -> error, string - txid
     */
    static public function mintTokens($actionType, $data, $ethAddress, $tokens, $coin = '', $txid = '')
    {
        if (!Deposit::mintingIsOn()) {
            return [-8321, ''];
        }

        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            return [-8322, ''];
        }

        $uuid = self::new_queue($actionType, $data);

        $tokens_send = number_format(bcmul($tokens, '100000000'), 0, '.', '');
        $txid_send = substr(str_pad(preg_replace('/^0x(.*)$/', '$1', $txid), 64, '0', STR_PAD_LEFT), 0, 64);

        Utility::log('eth_queue_mint/' . Utility::microtime_float(), [
            '_uuid' => $uuid,
            '_ethAddress' => $ethAddress,
            '_tokens' => $tokens,
            '_coin' => $coin,
            '_txid' => $txid,
            'tokens' => $tokens_send,
            'txid' => $txid_send,
            'contract' => ETH_TOKENS_CONTRACT
        ]);
        Utility::httpPostWithHmac(trim(ETH_QUEUE_URL, '/') . '/cgi-bin/mint-tokens', [
            'uuid' => $uuid,
            'minter' => $ethAddress,
            'tokens' => $tokens_send,
            'originalcointype' => $coin,
            'originaltxhash' => $txid_send,
            'contract' => ETH_TOKENS_CONTRACT
        ], ETH_QUEUE_KEY);

        return [0, $uuid];
    }

    static public function checkQueue()
    {
        $checkingKey = 'ETH_QUEUE_CHECKING';
        if (Application::getValue($checkingKey)) {
            return;
        }
        Application::setValue($checkingKey, true);

        $queue = DB::set("
            SELECT *
            FROM `eth_queue`
            WHERE
                `is_pending` = 1
        ;");
        foreach ($queue as $element_data) {
            $element = self::constructFromDbData($element_data);
            $return = @json_decode(Utility::httpPostWithHmac(
                trim(ETH_QUEUE_URL, '/') . '/cgi-bin/mint-status',
                ['uuid' => $element->uuid],
                ETH_QUEUE_KEY
            ), true);
            if (is_array($return) && array_key_exists('result', $return)) {
                $handleError = false;
                $handleSuccess = false;
                if (is_null($return['result'])) {
                    $handleError = true;
                    DB::set("
                        UPDATE `eth_queue`
                        SET
                            `is_pending` = ?,
                            `is_success` = ?,
                            `result` = ?
                        WHERE
                            `id` = ?
                        LIMIT 1
                    ", [false, false, 'not found', $element->id]);
                } else {
                    if ($return['result']['pending']) {
                        continue;
                    }
                    if ($return['result']['success']) {
                        DB::set("
                            UPDATE `eth_queue`
                            SET
                                `is_pending` = ?,
                                `is_success` = ?,
                                `result` = ?
                            WHERE
                                `id` = ?
                            LIMIT 1
                        ", [false, true, $return['result']['result'], $element->id]);
                        $handleSuccess = true;
                    } else {
                        DB::set("
                            UPDATE `eth_queue`
                            SET
                                `is_pending` = ?,
                                `is_success` = ?,
                                `result` = ?
                            WHERE
                                `id` = ?
                            LIMIT 1
                        ", [false, false, $return['result']['errorcode'] . ':' . $return['result']['result'], $element->id]);
                        $handleError = true;
                    }
                }

                if ($handleError) {
                    if ($element->action_type === self::TYPE_MINT_REINVEST) {
                        // signalize
                    } else if ($element->action_type === self::TYPE_MINT_DEPOSIT) {
                        // revert
                        // $deposit->setUsedInMinting(true);
                        // $investor->addTokens($realTokensMinting);
                    } else if ($element->action_type === self::TYPE_MINT_OLD_INVESTOR_INIT) {
                        // revert
                        // insert DB::set("DELETE FROM `investors_waiting_tokens` WHERE `investor_id` = ?", [$investor->id]);
                        // and $investor->setEthAddress('');
                    }
                } else if ($handleSuccess) {
                    if ($element->action_type === self::TYPE_MINT_REINVEST) {
                        $investor = Investor::getById($element->data['investorId']);
                        $investor->addTokens($element->data['tokens']);
                    } else if ($element->action_type === self::TYPE_MINT_DEPOSIT) {
                        // nothing ?
                    } else if ($element->action_type === self::TYPE_MINT_OLD_INVESTOR_INIT) {
                        // signalize
                    }
                }
            }
        }

        Application::setValue($checkingKey, false);
    }
}