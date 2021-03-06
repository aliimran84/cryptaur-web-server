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
    const TYPE_SENDETH_WALLET = 7;
    const TYPE_SENDCPT_WALLET = 8;
    const TYPE_GETFEE = 9;
    const TYPE_SENDPROOF_WALLET = 10;

    const FEE_STORAGE_KEY = 'ETHQUEUE_FEE';
    const FEE_SECS_TO_UPDATE = 300;

    const ETH_TO_WEI = '1000000000000000000';
    const CPT_TOKENS_TO_WITHOUT_DECIMALS = '100000000';
    const PROOF_TOKENS_TO_WITHOUT_DECIMALS = '100000000';

    const USERID_SHIFT = 1000;

    const SENDETHWALLET_IS_ON = 'SENDETHWALLET_IS_ON';
    const SENDCPTWALLET_IS_ON = 'SENDCPTWALLET_IS_ON';
    const SENDPROOFWALLET_IS_ON = 'SENDPROOFWALLET_IS_ON';

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
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`)
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
     * @param int $investorId
     * @param string[] $types
     * @return EthQueue[]
     */
    static public function getInvestorPendingDeposits($investorId, $types = [])
    {
        $ethQueue_elements_data = DB::get("
            SELECT * FROM `eth_queue`
            WHERE
                `investor_id` = ? AND
                `is_pending` = 1 AND
                `action_type` IN (?)
        ;", [Application::$authorizedInvestor->id, implode(',', $types)]);
        $ethQueue_elements = [];
        foreach ($ethQueue_elements_data as $element_data) {
            $ethQueue_elements[] = self::constructFromDbData($element_data);
        }
        return $ethQueue_elements;
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
            case self::TYPE_SENDETH_WALLET:
                return '/send-eth';
            case self::TYPE_SENDCPT_WALLET:
                return '/send-cpt';
            case self::TYPE_SENDPROOF_WALLET:
                return '/send-proof';
            case self::TYPE_GETFEE:
                return '/fee';
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
            case self::TYPE_SENDETH_WALLET:
            case self::TYPE_SENDCPT_WALLET:
            case self::TYPE_SENDPROOF_WALLET:
                return '/send-status';
            case self::TYPE_GETWALLET:
                return '/get-wallet';
            case self::TYPE_GETFEE:
                return '/fee';
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
                `investor_id` = ?,
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
     * @return double
     */
    static public function getFee()
    {
        $fee = 0;
        $fee_data = Application::getValue(self::FEE_STORAGE_KEY);
        if ($fee_data) {
            $fee = $fee_data['fee'];
            if (time() - $fee_data['datetime'] < self::FEE_SECS_TO_UPDATE) {
                return $fee;
            }
        }

        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            return $fee;
        }

        $result = @json_decode(Utility::httpPostWithHmac(ETH_QUEUE_URL . self::getMethodByType(self::TYPE_GETFEE), [],
            ETH_QUEUE_KEY), true);
        if (isset($result['result'])) {
            $fee = (double)$result['result'];
        }
        Application::setValue(self::FEE_STORAGE_KEY, [
            'fee' => $fee,
            'datetime' => time()
        ]);
        return $fee;
    }

    /**
     * @param int $investorId
     * @return null|EtherWallet
     */
    static public function getWallet($investorId)
    {
        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            return null;
        }

        $ethtoolsId = Investor::getEthtoolsIdByInvestorId($investorId);
        if (!$ethtoolsId) {
            return null;
        }
        $user = $ethtoolsId + self::USERID_SHIFT;
        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::getMethodByType(self::TYPE_GETWALLET), [
            'user' => $user
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_getwallet/' . Utility::microtime_float(), [
            '_investorId' => $investorId,
            '_ethtoolsId' => $ethtoolsId,
            'user' => $user,
            'return' => $return
        ]);

        $data = @json_decode($return, true);

        if (!isset($data['result'])) {
            return null;
        }

        if (@$data['result']['pending'] || (strlen(@$data['result']['result']) < 33)) {
            return null;
        }

        $ethAddress = $data['result']['result'];
        $eth = Utility::floor_prec(@$data['result']['balance']['ETH'], 8);
        $cpt = Utility::floor_prec(@$data['result']['balance']['CPT'], 8);
        $proof = Utility::floor_prec(@$data['result']['balance']['PROOF'], 8);
        $wallet = EtherWallet::getFromDbByInvestorId($investorId);
        if (!is_null($wallet)) {
            $minus_eth = 0;
            $minus_cpt = 0;
            $minus_proof = 0;
            $pendingEthQueue = DB::get("
                SELECT *
                FROM `eth_queue`
                WHERE
                    `investor_id` = ? AND
                    `is_pending` = 1
            ;", [$wallet->investor->id]);
            foreach ($pendingEthQueue as $element_data) {
                $element = self::constructFromDbData($element_data);
                switch ($element->action_type) {
                    case self::TYPE_SENDETH_WALLET:
                        $minus_eth += $element->data['eth'];
                        break;
                    case self::TYPE_SENDCPT_WALLET:
                        $minus_cpt += $element->data['cpt'];
                        break;
                    case self::TYPE_SENDPROOF_WALLET:
                        $minus_proof += $element->data['proof'];
                        break;
                }
            }

            $resultEth = $eth - $minus_eth;
            if ($resultEth < 0) {
                $resultEth = 0;
            }
            $resultCpt = $cpt - $minus_cpt;
            if ($resultCpt < 0) {
                $resultCpt = 0;
            }
            $resultProof = $proof - $minus_proof;
            if ($resultProof < 0) {
                $resultProof = 0;
            }
            $wallet->update($resultEth, $resultCpt, $resultProof);
            if ($wallet->eth_address != $ethAddress) {
                $wallet->eth_address = $ethAddress;
                $wallet->investor->setEthAddress($ethAddress);
                DB::set("
                    UPDATE `eth_queue_wallets`
                    SET
                        `eth_address` = ?
                    WHERE `id` = ?
                ;", [$ethAddress, $wallet->id]);
            }
        } else {
            $wallet = EtherWallet::create($investorId, $ethAddress, $eth, $cpt, $proof);
        }

        return $wallet;
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
        if (in_array($investorId, [35748, 40436, 40687, 42387, 43651, 43809, 44426, 44449, 46010, 48077, 48090, 48092, 48093, 49579, 49588, 50913])) {
            return [-6661, ''];
        }

        $eth_queue = self::new_queue($actionType, $investorId, $data);

        if (!Bounty::reinvestIsOn() && !Bounty::withdrawIsOn()) {
            $eth_queue->handleError('8421: !Bounty::reinvestIsOn() && !Bounty::withdrawIsOn()');
            return [-8421, ''];
        }

        if (!ETH_BOUNTY_DISPENSER) {
            $eth_queue->handleError('8422: !ETH_BOUNTY_DISPENSER');
            return [-8422, ''];
        }

        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            $eth_queue->handleError('8423: !ETH_QUEUE_URL || !ETH_QUEUE_KEY');
            return [-8423, ''];
        }

        if (!$ethAddress) {
            $eth_queue->handleError('8424: !$ethAddress');
            return [-8424, ''];
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
            $eth_queue->handleError('8321: !Deposit::mintingIsOn()');
            return [-8321, ''];
        }

        if (!ETH_QUEUE_URL || !ETH_QUEUE_KEY) {
            $eth_queue->handleError('8322: !ETH_QUEUE_URL || !ETH_QUEUE_KEY');
            return [-8322, ''];
        }

        if (!$ethAddress) {
            $eth_queue->handleError('8323: !$ethAddress');
            return [-8323, ''];
        }

        $tokens_send = Utility::int_string(bcmul(Utility::double_string($tokens), self::CPT_TOKENS_TO_WITHOUT_DECIMALS));
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
            '_actionType' => $actionType,
            'tokens' => $tokens_send,
            'txid' => $txid_send,
            'contract' => ETH_TOKENS_CONTRACT,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    /**
     * @param int $investorId
     * @param array $data
     * @param string $ethAddress
     * @param double $ethValue
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function sendEthWallet($investorId, $data, $ethAddress, $ethValue)
    {
        if (in_array($investorId, [35748, 40436, 40687, 42387, 43651, 43809, 44426, 44449, 46010, 48077, 48090, 48092, 48093, 49579, 49588, 50913])) {
            return [-6663, ''];
        }

        $eth_queue = self::new_queue(self::TYPE_SENDETH_WALLET, $investorId, $data);

        if (!EthQueue::sendEthWalletIsOn()) {
            $eth_queue->handleError('8521: !EthQueue::sendEthWalletIsOn()');
            return [-8521, ''];
        }

        if (!$ethAddress) {
            $eth_queue->handleError('8522: !$ethAddress');
            return [-8522, ''];
        }

        $weiValue = Utility::int_string(bcmul(Utility::double_string($ethValue), self::ETH_TO_WEI));
        $ethtoolsId = Investor::getEthtoolsIdByInvestorId($investorId);
        if (!$ethtoolsId) {
            return [-8523, ''];
        }
        $user = $ethtoolsId + self::USERID_SHIFT;
        $sender = $eth_queue->investor->eth_address;

        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::sendMethodByType(self::TYPE_SENDETH_WALLET), [
            'uuid' => $eth_queue->uuid,
            'user' => $user,
            'sender' => $sender,
            'receiver' => $ethAddress,
            'wei' => $weiValue
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_sendethwallet/' . Utility::microtime_float(), [
            '_uuid' => $eth_queue->uuid,
            '_user' => $user,
            '_sender' => $sender,
            '_ethAddress' => $ethAddress,
            '_investorId' => $eth_queue->investor->id,
            '_ethtoolsId' => $ethtoolsId,
            '_ethValue' => $ethValue,
            'wei' => $weiValue,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    /**
     * @param int $investorId
     * @param array $data
     * @param string $ethAddress
     * @param double $cptValue
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function sendCptWallet($investorId, $data, $ethAddress, $cptValue)
    {
        if (in_array($investorId, [35748, 40436, 40687, 42387, 43651, 43809, 44426, 44449, 46010, 48077, 48090, 48092, 48093, 49579, 49588, 50913])) {
            return [-6664, ''];
        }

        $eth_queue = self::new_queue(self::TYPE_SENDCPT_WALLET, $investorId, $data);

        if (!EthQueue::sendCptWalletIsOn()) {
            $eth_queue->handleError('8621: !EthQueue::sendCptWalletIsOn()');
            return [-8621, ''];
        }

        if (!$ethAddress) {
            $eth_queue->handleError('8622: !$ethAddress');
            return [-8622, ''];
        }

        $cptWithoutDecimals = Utility::int_string(bcmul(Utility::double_string($cptValue), self::CPT_TOKENS_TO_WITHOUT_DECIMALS));
        $ethtoolsId = Investor::getEthtoolsIdByInvestorId($investorId);
        if (!$ethtoolsId) {
            return [-8623, ''];
        }
        $user = $ethtoolsId + self::USERID_SHIFT;
        $sender = $eth_queue->investor->eth_address;

        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::sendMethodByType(self::TYPE_SENDCPT_WALLET), [
            'uuid' => $eth_queue->uuid,
            'user' => $user,
            'sender' => $sender,
            'receiver' => $ethAddress,
            'tokens' => $cptWithoutDecimals
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_sendcptwallet/' . Utility::microtime_float(), [
            '_uuid' => $eth_queue->uuid,
            '_user' => $user,
            '_sender' => $sender,
            '_ethAddress' => $ethAddress,
            '_investorId' => $eth_queue->investor->id,
            '_ethtoolsId' => $ethtoolsId,
            '_cptValue' => $cptValue,
            'tokens' => $cptWithoutDecimals,
            'return' => $return
        ]);

        return [0, $eth_queue->uuid];
    }

    /**
     * @param int $investorId
     * @param array $data
     * @param string $ethAddress
     * @param double $proofValue
     * @return array [int, string] if int < 0 -> error, else - uuid
     */
    static public function sendProofWallet($investorId, $data, $ethAddress, $proofValue)
    {
        if (in_array($investorId, [35748, 40436, 40687, 42387, 43651, 43809, 44426, 44449, 46010, 48077, 48090, 48092, 48093, 49579, 49588, 50913])) {
            return [-6664, ''];
        }

        $eth_queue = self::new_queue(self::TYPE_SENDPROOF_WALLET, $investorId, $data);

        if (!EthQueue::sendProofWalletIsOn()) {
            $eth_queue->handleError('8631: !EthQueue::sendProofWalletIsOn()');
            return [-8631, ''];
        }

        if (!$ethAddress) {
            $eth_queue->handleError('8632: !$ethAddress');
            return [-8632, ''];
        }

        $proofWithoutDecimals = Utility::int_string(bcmul(Utility::double_string($proofValue), self::PROOF_TOKENS_TO_WITHOUT_DECIMALS));
        $ethtoolsId = Investor::getEthtoolsIdByInvestorId($investorId);
        if (!$ethtoolsId) {
            return [-8633, ''];
        }
        $user = $ethtoolsId + self::USERID_SHIFT;
        $sender = $eth_queue->investor->eth_address;

        $return = Utility::httpPostWithHmac(ETH_QUEUE_URL . self::sendMethodByType(self::TYPE_SENDPROOF_WALLET), [
            'uuid' => $eth_queue->uuid,
            'user' => $user,
            'sender' => $sender,
            'receiver' => $ethAddress,
            'tokens' => $proofWithoutDecimals
        ], ETH_QUEUE_KEY);
        Utility::log('eth_queue_sendproofwallet/' . Utility::microtime_float(), [
            '_uuid' => $eth_queue->uuid,
            '_user' => $user,
            '_sender' => $sender,
            '_ethAddress' => $ethAddress,
            '_investorId' => $eth_queue->investor->id,
            '_ethtoolsId' => $ethtoolsId,
            '_proofValue' => $proofValue,
            'tokens' => $proofWithoutDecimals,
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
                    $element->handleError('not found');
                } else {
                    if ($return['result']['pending']) {
                        continue;
                    }
                    if ($return['result']['success']) {
                        $element->handleSuccess($return['result']['result']);
                    } else {
                        $element->handleError($return['result']['errorcode'] . ':' . $return['result']['result']);
                    }
                }
            }
        }

        Application::setValue($checkingKey, false);
    }

    private function handleSuccess($result)
    {
        $this->update(false, true, $result);
        $this->updateEndDatetime();
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                // todo: add eth to eth_not_used_in_bounty
                $txid = $this->result;
                $data = [
                    'tokens' => $this->data['tokens']
                ];
                EthQueue::mintTokens(EthQueue::TYPE_MINT_REINVEST, $this->investor->id, $data, $this->investor->eth_address, $this->data['tokens'], 'eth', $txid);
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
                $this->investor->addTokens($this->data['tokens']);
                break;
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                // investor see message on dashboard until this moment
                break;
            case self::TYPE_SENDETH_WALLET:
            case self::TYPE_SENDCPT_WALLET:
            case self::TYPE_SENDPROOF_WALLET:
                // nothing
                break;
        }
    }

    private function handleError($result)
    {
        $this->update(false, false, $result);
        $this->updateEndDatetime();
        $data = $this->data;
        switch ($this->action_type) {
            case self::TYPE_SENDETH_REINVEST:
                $this->investor->addEthBounty($data['ethToReinvest']);
                break;
            case self::TYPE_MINT_REINVEST:
                Utility::log('eth_queue_mint_error/' . Utility::microtime_float(), [
                    'uuid' => $this->uuid,
                    'data' => $this->data,
                    'investorId' => $this->investor->id
                ]);
                break;
            case self::TYPE_SENDETH_WITHDRAW:
                $this->investor->addEthBounty($data['ethToWithdraw']);
                break;
            case self::TYPE_MINT_DEPOSIT:
                foreach ($this->data['depositsId'] as $depositId) {
                    $deposit = Deposit::getById($depositId);
                    $deposit->setUsedInMinting(false);
                }
                break;
            case self::TYPE_MINT_OLD_INVESTOR_INIT:
                DB::set("INSERT INTO `investors_waiting_tokens` SET `investor_id` = ?", [$this->investor->id]);
                $this->investor->setEthAddress('');
                break;
            case self::TYPE_SENDETH_WALLET:
            case self::TYPE_SENDCPT_WALLET:
            case self::TYPE_SENDPROOF_WALLET:
                $wallet = EtherWallet::getFromDbByInvestorId($this->investor->id);
                $wallet->resetUpdateDateTime();
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

    /**
     * @return bool
     */
    static public function sendEthWalletIsOn()
    {
        return Application::getValue(self::SENDETHWALLET_IS_ON);
    }

    /**
     * @return bool
     */
    static public function sendCptWalletIsOn()
    {
        return Application::getValue(self::SENDCPTWALLET_IS_ON);
    }

    /**
     * @return bool
     */
    static public function sendProofWalletIsOn()
    {
        return Application::getValue(self::SENDPROOFWALLET_IS_ON);
    }
}