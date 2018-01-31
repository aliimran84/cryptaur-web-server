<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Coin;

Application::init();

for ($investorId = 137216; $investorId <= 137349; ++$investorId) {
    DB::set("
            INSERT INTO `investors_referrals_totals`
            SET
                `investor_id` = ?,
                `coin` = ?
            ;", [
            $investorId, Coin::token()
        ]
    );
    foreach (Coin::coins() as $coin) {
        DB::set("
                INSERT INTO `investors_referrals_totals`
                SET
                    `investor_id` = ?,
                    `coin` = ?
                ;", [
                $investorId, $coin
            ]
        );
    }

    DB::set("
            INSERT INTO `investors_referrers` (`investor_id`, `referrers`)
            VALUES
            (
                ?,
                (
                    SELECT CAST(referrers as CHAR(10000)) as referrers FROM (
                        SELECT referrer_id, @g := IF(@g = '', referrer_id, concat(@g, ',', referrer_id)) as referrers
                        FROM
                                `investors`,
                                ( SELECT @g := '' ) AS `tmp`,
                                ( SELECT @pv :=  ? ) AS `initialisation`
                        WHERE `id` = @pv AND @pv := `referrer_id`
                        ORDER BY `id` DESC
                        ) AS tmp
                        ORDER BY referrer_id ASC
                        LIMIT 1
                )
            )
            ;", [
            $investorId, $investorId
        ]
    );
    DB::set("
        UPDATE `investors_referrals` SET `referrals` = IF(`referrals` = '' || `referrals` is null, ?, concat(`referrals`, ',', ?))
        WHERE FIND_IN_SET(`investor_id`, (
            SELECT `referrers`
            FROM `investors_referrers`
            WHERE `investor_id` = ?
        ))
        ;", [
            $investorId, $investorId, $investorId
        ]
    );
}