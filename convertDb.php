<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$btcToEthFixRate = 40.9254;

$startTime = time();

$usersCount = DB::get("
    SELECT
        count(auth_user.id) as count
    FROM
        auth_user
        JOIN syndicates_syndicateaccount ON syndicates_syndicateaccount.account_id = auth_user.id
        JOIN account_account ON account_account.id = auth_user.id
    WHERE
        account_account.email_confirmed = 1
        AND (auth_user.first_name != 'Vision' or auth_user.last_name != 'User')
")[0]['count'];

$lastCashbackedTs = DB::get("
    select created_at from exchange_exchangeorder where id = (
        select order_id from syndicates_syndicateexchangeorder where id = (
           select order_id from syndicates_cashbackbonus where status = 3 order by id desc limit 1
        )
    )
")[0]['created_at']; // 1510057754

$previousIdToNew = [];

$limitSize = 1000;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            auth_user.id,
            syndicates_syndicateaccount.invited_by_id,
            auth_user.password,
            auth_user.email,
            auth_user.first_name,
            auth_user.last_name,
            auth_user.email,
            auth_user.date_joined,
            account_account.phone_number,
            syndicates_syndicateaccount.referral
        FROM
            auth_user
            JOIN syndicates_syndicateaccount ON syndicates_syndicateaccount.account_id = auth_user.id
            JOIN account_account ON account_account.id = auth_user.id
        WHERE
            account_account.email_confirmed = 1
            AND (auth_user.first_name != 'Vision' or auth_user.last_name != 'User')
        LIMIT $offset, $limitSize
    ;");

    foreach ($users as $i => $user) {
        $tokens = (double)@DB::get("
            select sum(amount)/100000000 as tokens from transactions_history where
            account_id=? and type=0
        ;", [$user['id']])[0]['tokens'];
        $bounty_remains = (double)@DB::get("
            SELECT (
                (
                    SELECT sum( amount ) AS amount
                    FROM syndicates_cashbackbonus
                    WHERE
                        recipient_id = ? AND
                        STATUS = 3
                ) -
                (
                    SELECT sum( amount ) / 1000000000000000000 AS amount
                    FROM transactions_history
                    WHERE
                        account_id = ? AND
                        type = 1
                ) 
            ) AS eth_bounty
        ;", [$user['id'], $user['id']])[0]['eth_bounty'];

        $refId = @(int)$previousIdToNew[$user['invited_by_id']];

        $btc = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='btc' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        $eth = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='eth' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        $amountEth = $btcToEthFixRate * $btc + $eth;

        DB::set("
            INSERT INTO `investors`
            SET
                `phone` = ?, `referrer_id` = ?, `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?, `firstname` = ?, `lastname` = ?,
                `password_hash` = ?,
                `eth_address` = ?, `eth_withdrawn` = ?,
                `tokens_count` = ?,
                `eth_not_used_in_bounty` = ?, `eth_bounty` = ?
        ", [
                $user['phone_number'], $refId, $user['referral'],
                $user['date_joined'],
                $user['email'], $user['first_name'], $user['last_name'],
                $user['password'],
                '', 0,
                $tokens,
                $amountEth, $bounty_remains
            ]
        );
        $id = DB::lastInsertId();
        DB::set("
            INSERT INTO investors_to_previous_system
            SET
                `investor_id` = ?,
                `previoussystem_id` = ?
        ", [$id, $user['id']]);
        $previousIdToNew[$user['id']] = $id;

        $transactions = @DB::get("
            select * from exchange_exchangeorder where
            (source_currency='eth' or source_currency='btc') and account_id=? and status=4
        ;", [$user['id']]);

        $ethCoinAmount = 0;
        $ethUsdAmount = 0;
        $btcCoinAmount = 0;
        $btcUsdAmount = 0;
        foreach ($transactions as $transaction) {
            if (strtolower($transaction['source_currency']) === 'eth') {
                $ethCoinAmount += $transaction['amount_to_exchange'];
                $ethUsdAmount += $transaction['exchanged_amount'] / 100;
            } else if (strtolower($transaction['source_currency']) === 'btc') {
                $btcCoinAmount += $transaction['amount_to_exchange'];
                $btcUsdAmount += $transaction['exchanged_amount'] / 100;;
            }
            DB::set("
                INSERT INTO `deposits`
                SET
                    `investor_id` = ?,  `coin` = ?, `txid` = ?,
                    `vout` = 0, `amount` = ?, `usd` = ?,
                    `rate` = ?,
                    `datetime` = ?,
                    `is_donation` = 0,
                    `registered` = 1,
                    `used_in_minting` = 1
            ;", [
                $id, strtolower($transaction['source_currency']), $transaction['withdrawal_transaction_id'],
                $transaction['amount_to_exchange'], $transaction['exchanged_amount'] / 100,
                $transaction['exchanged_amount'] / 100 / $transaction['amount_to_exchange'],
                DB::timetostr($transaction['created_at'])
            ]);
        }

        DB::set("
            INSERT INTO `wallets`
            SET
                `investor_id` = ?,
                `coin` = 'btc',
                `address`= '',
                `balance` = ?,
                `usd_used`= ?
        ;", [$id, $btcCoinAmount, $btcUsdAmount]);
        DB::set("
            INSERT INTO `wallets`
            SET
                `investor_id` = ?,
                `coin` = 'eth',
                `address`= '',
                `balance` = ?,
                `usd_used`= ?
        ;", [$id, $ethCoinAmount, $ethUsdAmount]);

        $duration = (time() - $startTime) + 1;
        $currentCount = $i + $offset;
        $speed = number_format($currentCount / $duration, 5);
        if ($speed == 0) {
            $remained = 1;
        } else {
            $remained = (int)($usersCount - $currentCount) / $speed;
        }
        echo date('Y-m-d H:i:s') . ": fill for $currentCount/$usersCount (userid: {$user['id']}, duration: {$duration}s, speed: {$speed}u/s, remained: {$remained}s)\r\n";
    }
}

// для всех инвесторов, которым в старой системе выставлен родителем инвестор, добавленный позже делаем вот такой фокус
DB::query("
    UPDATE investors
    JOIN investors_to_previous_system ON investors.id = investors_to_previous_system.investor_id
    JOIN syndicates_syndicateaccount ON syndicates_syndicateaccount.account_id = investors_to_previous_system.previoussystem_id 
    SET investors.referrer_id = ( SELECT investor_id FROM investors_to_previous_system WHERE previoussystem_id = syndicates_syndicateaccount.invited_by_id ) 
    WHERE
        investors.referrer_id = 0 
        AND syndicates_syndicateaccount.invited_by_id > 0
");