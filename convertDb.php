<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Investor;
use core\models\Wallet;

Application::init();

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
")[0]['created_at'];

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
//        AND auth_user.is_staff = 0

    foreach ($users as $i => $user) {
        $tokens = (double)@DB::get("
            select sum(amount)/100000000 as tokens from transactions_history where
            account_id=? and type=0
        ;", [$user['id']])[0]['tokens'];
        $tokens_not_used_in_bounty = (double)@DB::get("
            select sum(amount)/100000000 as tokens from transactions_history where
            account_id=? and type=0 and `timestamp`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['tokens'];
        $b1 = (double)@DB::get("
            select sum(amount) as b1 from syndicates_cashbackbonus where recipient_id=? and status=3
        ", [$user['id']])[0]['b1'];
        $b2 = (double)@DB::get("
            select sum(amount)/1000000000000000000 from transactions_history where account_id = ? and type = 1
        ", [$user['id']])[0]['b2'];
        $bounty = (double)($b1 - $b2);

        $usd = (double)@DB::get("
            SELECT
                sum( exchanged_amount ) / 100 as usd
            FROM
                exchange_exchangeorder
            WHERE
                account_id = ?
                AND `status` = 4
        ", [$user['id']])[0]['usd'];

        $refId = @(int)$previousIdToNew[$user['invited_by_id']];

        $btc = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='btc' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        $eth = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='eth' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        // btc -> eth = 40.9254 fix на указанный момент
        $amountEth = $btc / 40.9254 + $eth;

        DB::set("
            INSERT INTO `investors`
            SET
                `phone` = ?, `referrer_id` = ?, `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?, `firstname` = ?, `lastname` = ?,
                `password_hash` = ?,
                `eth_address` = ?, `eth_withdrawn` = ?,
                `tokens_count` = ?, `tokens_not_used_in_bounty` = ?,
                `eth_not_used_in_bounty` = ?, `eth_bounty` = ?
        ", [
                $user['phone_number'], $refId, $user['referral'],
                $user['date_joined'],
                $user['email'], $user['first_name'], $user['last_name'],
                $user['password'],
                '', 0,
                $tokens, $tokens_not_used_in_bounty,
                $amountEth, $bounty
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
        $wallet = Wallet::registerWallet($id, 'usd', '');
        $wallet->addToWallet($usd, $usd);

        $duration = (time() - $startTime) + 1;
        $currentCount = $i + $offset;
        $speed = number_format($currentCount / $duration, 5);
        if ($speed === 0) {
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