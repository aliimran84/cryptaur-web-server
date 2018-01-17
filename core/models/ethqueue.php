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
    const TYPE_SENDETH_REINVEST = 4;
    const TYPE_SENDETH_WITHDRAW = 5;

    const ETH_TO_WEI = '1000000000000000000';
    const TOKENS_TO_WITHOUT_DECIMALS = '100000000';

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
     * @param int $type
     * @return string
     */
    static private function sendMethodByType($type)
    {
        switch ($type) {
            case self::TYPE_MINT_REINVEST:
            case self::TYPE_MINT_DEPOSIT:
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                return '/mint-tokens';
            case self::TYPE_SENDETH_REINVEST:
            case self::TYPE_SENDETH_WITHDRAW:
                return '/send-eth-bounty';
        }
        return '';
    }

    /**
     * @param int $type
     * @return string
     */
    static private function getMethodByType($type)
    {
        switch ($type) {
            case self::TYPE_MINT_REINVEST:
            case self::TYPE_MINT_DEPOSIT:
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                return '/mint-status';
            case self::TYPE_SENDETH_REINVEST:
            case self::TYPE_SENDETH_WITHDRAW:
                return '/send-status';
        }
        return '';
    }

    /**
     * @param int $actionType
     * @param array $data
     * @return EthQueue
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
        $id = DB::lastInsertId();
        $element_data = DB::get("
            SELECT *
            FROM `eth_queue`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id]);
        $element = self::constructFromDbData($element_data[0]);
        return $element;
    }

    /**
     * @param int $actionType
     * @param array $data
     * @param string $ethAddress
     * @param double $ethValue
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function sendEthBounty($actionType, $data, $ethAddress, $ethValue)
    {
        $eth_queue = self::new_queue($actionType, $data);

        if (!Bounty::reinvestIsOn() && !Bounty::withdrawIsOn()) {
            $eth_queue->handleError();
            return [-8421, ''];
        }

        if (!ETH_BOUNTY_DISPENSER) {
            $eth_queue->handleError();
            return [-8422, ''];
        }

        $weiValue = Utility::int_string(bcmul(Utility::double_string($ethValue), self::ETH_TO_WEI));

        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::sendMethodByType($actionType), [
            'uuid' => $eth_queue->uuid,
            'sender' => ETH_BOUNTY_DISPENSER,
            'receiver' => $ethAddress,
            'wei' => $weiValue
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_sendethbounty/' . Utility::microtime_float(), [
            '_uuid' => $eth_queue->uuid,
            '_ethAddress' => $ethAddress,
            '_ethValue' => $ethValue,
            '_data' => $data,
            'wei' => $weiValue,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    /**
     * @param int $actionType
     * @param array $data
     * @param string $ethAddress
     * @param double $tokens
     * @param string $coin
     * @param string $txid
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function mintTokens($actionType, $data, $ethAddress, $tokens, $coin = '', $txid = '')
    {
        $eth_queue = self::new_queue($actionType, $data);

        if (!Deposit::mintingIsOn()) {
            $eth_queue->handleError();
            return [-8321, ''];
        }

        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            $eth_queue->handleError();
            return [-8322, ''];
        }

        $tokens_send = Utility::int_string(bcmul(Utility::double_string($tokens), self::TOKENS_TO_WITHOUT_DECIMALS));
        $txid_send = substr(str_pad(preg_replace('/^0x(.*)$/', '$1', $txid), 64, '0', STR_PAD_LEFT), 0, 64);

        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::sendMethodByType($actionType), [
            'uuid' => $eth_queue->uuid,
            'minter' => $ethAddress,
            'tokens' => $tokens_send,
            'originalcointype' => $coin,
            'originaltxhash' => $txid_send,
            'contract' => ETH_TOKENS_CONTRACT
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_mint/' . Utility::microtime_float(), [
            '_uuid' => $eth_queue->uuid,
            '_ethAddress' => $ethAddress,
            '_tokens' => $tokens,
            '_coin' => $coin,
            '_txid' => $txid,
            '_data' => $data,
            'tokens' => $tokens_send,
            'txid' => $txid_send,
            'contract' => ETH_TOKENS_CONTRACT,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    private function update($is_pending, $is_success, $result)
    {
        $this->is_pending = $is_pending;
        $this->is_success = $is_success;
        $this->result = $result;
        DB::set("
            UPDATE `eth_queue`
            SET
                `is_pending` = ?,
                `is_success` = ?,
                `result` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ", [$is_pending, $is_success, $result, $this->id]);
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
                ETH_QUEUE_URL . self::getMethodByType($element->action_type),
                ['uuid' => $element->uuid],
                ETH_QUEUE_KEY
            ), true);
            if (is_array($return) && array_key_exists('result', $return)) {
                if (is_null($return['result'])) {
                    $element->update(false, false, 'not found');
                    $element->handleError();
                } else {
                    if ($return['result']['pending']) {
                        continue;
                    }
                    if ($return['result']['success']) {
                        $element->update(false, true, $return['result']['result']);
                        $element->handleSuccess();
                    } else {
                        $element->update(false, false, $return['result']['errorcode'] . ':' . $return['result']['result']);
                        $element->handleError();
                    }
                }
            }
        }

        Application::setValue($checkingKey, false);
    }

    private function handleSuccess()
    {
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                // todo: add eth to eth_not_used_in_bounty
                $investor = Investor::getById($this->data['investorId']);
                $txid = $this->result;
                $data = [
                    'investorId' => $investor->id,
                    'tokens' => $this->data['tokens']
                ];
                EthQueue::mintTokens(EthQueue::TYPE_MINT_REINVEST, $data, $investor->eth_address, $this->data['tokens'], 'eth', $txid);
                break;
            case self::TYPE_MINT_REINVEST:
                $investor = Investor::getById($this->data['investorId']);
                $investor->addTokens($this->data['tokens']);
                break;
            case self::TYPE_SENDETH_WITHDRAW:
                $investor = Investor::getById($this->data['investorId']);
                $investor->eth_withdrawn += $this->data['ethToWithdraw'];
                DB::set("
                    UPDATE `investors`
                    SET
                        `eth_withdrawn` = ?
                    WHERE
                        `id` = ?
                    LIMIT 1
                ;", [$investor->eth_withdrawn, $investor->id]);
                break;
            case self::TYPE_MINT_DEPOSIT:
                // nothing ?
                break;
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                // signalize;
                break;
        }
    }

    private function handleError()
    {
        $data = $this->data;
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                $investor = Investor::getById($data['investorId']);
                $investor->addEthBounty($data['ethToReinvest']);
                break;
            case self::TYPE_MINT_REINVEST:
                // signalize
                break;
            case self::TYPE_SENDETH_WITHDRAW:
                $investor = Investor::getById($data['investorId']);
                $investor->addEthBounty($data['ethToWithdraw']);
                break;
            case self::TYPE_MINT_DEPOSIT:
                // revert
                // $deposit->setUsedInMinting(true);
                // $investor->addTokens($realTokensMinting);
                break;
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                // revert
                // insert DB::set("DELETE FROM `investors_waiting_tokens` WHERE `investor_id` = ?", [$investor->id]);
                // and $investor->setEthAddress('');
                break;
        }
    }
}