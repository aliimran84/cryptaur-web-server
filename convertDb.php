<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Investor;
use core\models\Wallet;

Application::init();

$startTime = time();

DB::query("
            CREATE TABLE IF NOT EXISTS `temp_investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `previous_id` int(10) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`)
            );
        ");

$users = DB::get("
    SELECT
        auth_user.id,
        syndicates_syndicateaccount.invited_by_id,
        auth_user.password,
        auth_user.email,
        auth_user.date_joined,
        account_account.phone_number,
        account_account.referral_code
    FROM
        auth_user
        JOIN syndicates_syndicateaccount ON syndicates_syndicateaccount.account_id = auth_user.id
        JOIN account_account ON account_account.id = auth_user.id
    WHERE
        auth_user.email NOT LIKE \"%+%\"
        AND account_account.email_confirmed = 1
;");
//        AND auth_user.is_staff = 0

$previousIdToNew = [];
$usersCount = count($users);
foreach ($users as $i => $user) {
    $tokens_data = DB::get("
        select sum(amount)/100000000 as tokens from transactions_history where account_id=? and type=0
    ;", [$user['id']]);
    $tokens = (double)@$tokens_data[0]['tokens'];
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

    $refId = @(int)$previousIdToNew[$user['id']];

    DB::set("
        INSERT INTO `investors`
        SET
            `phone` = ?,
            `referrer_id` = ?,
            `referrer_code` = ?,
            `joined_datetime` = ?,
            `email` = ?,
            `password_hash` = ?,
            `eth_address` = ?,
            `eth_withdrawn` = ?,
            `tokens_count` = ?,
            `eth_bounty` = ?
    ", [$user['phone_number'], $refId, $user['referral_code'], $user['date_joined'], $user['email'], $user['password'], '', 0, $tokens, $bounty]
    );
    $id = DB::lastInsertId();
    $previousIdToNew[$user['id']] = $id;
    $wallet = Wallet::registerWallet($id, 'usd', '');
    $wallet->addToWallet($usd, $usd);

    $duration = time() - $startTime;
    $speed = number_format($i / $duration, 5);
    $remained = (int)($usersCount - $i) / $speed;
    echo date('Y-m-d H:i:s') . ": fill for $i/$usersCount (duration: {$duration}s, speed: {$speed}u/s, remained: {$remained}s)\r\n";
}

DB::query("DROP TABLE IF EXISTS temp_investors;");