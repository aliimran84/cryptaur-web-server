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
    const TYPE_GETWALLET = 6;

    const ETH_TO_WEI = '1000000000000000000';
    const TOKENS_TO_WITHOUT_DECIMALS = '100000000';

    const USERID_SHIFT = 1000;

    static private $pendingQueueTypesByInvestor = [];

    public $id = 0;
    public $uuid = '';
    public $datetime = 0;
    public $datetime_end = 0;

    /**
     * @var Investor
     */
    public $investor = null;
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
                `datetime_end` datetime(0) NOT NULL,
                `investor_id` int(10) UNSIGNED NOT NULL,
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
    static private function constructFromDbData($data)
    {
        $instance = new EthQueue();
        $instance->id = $data['id'];
        $instance->uuid = $data['uuid'];
        $instance->datetime = strtotime($data['datetime']);
        $instance->datetime_end = strtotime($data['datetime_end']);
        $instance->investor = Investor::getById($data['investor_id']);
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
            case self::TYPE_GETWALLET:
                return '/get-wallet';
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
            case self::TYPE_GETWALLET:
                return '/get-wallet';
        }
        return '';
    }

    /**
     * @param int $actionType
     * @param int $investorId
     * @param array $data
     * @return EthQueue
     */
    static private function new_queue($actionType, $investorId, $data)
    {
        $uuid = Utility::uuid();
        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        DB::set("
            INSERT INTO `eth_queue` SET
                `uuid` = ?,
                `datetime` = NOW(),
                `datetime_end` = NOW(),
                `investor_id` = ?
                `action_type` = ?,
                `is_pending` = 1,
                `is_success` = 0,
                `result` = '',
                `data` = ?
        ;", [$uuid, $investorId, $actionType, $json_data]);
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

    private function updateEndDatetime()
    {
        $this->datetime_end = time();
        DB::set("
            UPDATE `eth_queue` SET
                `datetime_end` = NOW()
            WHERE `id` = ?
        ;", [$this->id]);
    }

    /**
     * @param int $investorId
     * @return null|array
     */
    static public function getWallet($investorId)
    {
        $user = $investorId + self::USERID_SHIFT;
        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::getMethodByType(self::TYPE_GETWALLET), [
            'user' => $user
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_getwallet/' . Utility::microtime_float(), [
            '_investorId' => $investorId,
            'user' => $user,
            'return' => $return
        ]);

        $data = @json_decode($return, true);

        if (!isset($data['result'])) {
            return null;
        }

        return $data['result'];
    }


    /**
     * @param int $actionType
     * @param int $investorId
     * @param array $data
     * @param string $ethAddress
     * @param double $ethValue
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function sendEthBounty($actionType, $investorId, $data, $ethAddress, $ethValue)
    {
        $eth_queue = self::new_queue($actionType, $investorId, $data);

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
            '_investorId' => $eth_queue->investor->id,
            '_ethValue' => $ethValue,
            '_data' => $data,
            'wei' => $weiValue,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    /**
     * @param int $actionType
     * @param int $investorId
     * @param array $data
     * @param string $ethAddress
     * @param double $tokens
     * @param string $coin
     * @param string $txid
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function mintTokens($actionType, $investorId, $data, $ethAddress, $tokens, $coin = '', $txid = '')
    {
        $eth_queue = self::new_queue($actionType, $investorId, $data);

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
            '_investorId' => $eth_queue->investor->id,
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
        $this->updateEndDatetime();
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                // todo: add eth to eth_not_used_in_bounty
                $txid = $this->result;
                $data = [
                    'tokens' => $this->data['tokens']
                ];
                EthQueue::mintTokens(EthQueue::TYPE_MINT_REINVEST, $this->investor->id, $data, $investor->eth_address, $this->data['tokens'], 'eth', $txid);
                break;
            case self::TYPE_MINT_REINVEST:
                $this->investor->addTokens($this->data['tokens']);
                break;
            case self::TYPE_SENDETH_WITHDRAW:
                $this->investor->eth_withdrawn += $this->data['ethToWithdraw'];
                DB::set("
                    UPDATE `investors`
                    SET
                        `eth_withdrawn` = ?
                    WHERE
                        `id` = ?
                    LIMIT 1
                ;", [$this->investor->eth_withdrawn, $this->investor->id]);
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
        $this->updateEndDatetime();
        $data = $this->data;
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                $this->investor->addEthBounty($data['ethToReinvest']);
                break;
            case self::TYPE_MINT_REINVEST:
                // signalize
                break;
            case self::TYPE_SENDETH_WITHDRAW:
                $this->investor->addEthBounty($data['ethToWithdraw']);
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

    static public function pendingQueueTypesByInvestor($investorId)
    {
        if (isset(self::$pendingQueueTypesByInvestor[$investorId])) {
            return self::$pendingQueueTypesByInvestor[$investorId];
        }
        if (!Application::$authorizedInvestor) {
            return [];
        }
        $types = [];
        foreach (DB::get("
            SELECT DISTINCT(`action_type`) as `action_type` FROM `eth_queue`
            WHERE `investor_id` = ? AND `is_pending` = '1'
        ;", [Application::$authorizedInvestor->id]) as $type_data) {
            $types[$type_data['action_type']] = true;
        }
        self::$pendingQueueTypesByInvestor[$investorId] = $types;
        return $types;
    }
}