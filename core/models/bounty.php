<?php

namespace core\models;

class Bounty
{
    const CURRENT_BOUNTY_PROGRAM = [3, 3, 3, 3, 4, 4];

    /**
     * @return int[]
     */
    static public function program()
    {
        return self::CURRENT_BOUNTY_PROGRAM;
    }

    /**
     * reward in $
     * @param Investor $investor
     * @param [] [0=>1, 1=>5, ...]
     * @param int $level
     * @return int
     */
    static public function rewardForInvestor(&$investor, &$rewardPerLevel = [], $level = 0)
    {
        $reward = 0;
        if ($level > count(self::program())) {
            return $reward;
        }

        if (!isset($rewardPerLevel[$level])) {
            $rewardPerLevel[$level] = 0;
        }

        if ($level === 0) {
            $investor->initCompressedReferalls(count(self::program()));
        } else {
            $tokenRate = Coin::getRate(Coin::token());
            $reward += $tokenRate * $investor->tokens_not_used_in_bounty * Bounty::CURRENT_BOUNTY_PROGRAM[$level] / 100;
            $rewardPerLevel[$level] += $reward;
        }

        if (is_null($investor->compressed_referrals) || count($investor->compressed_referrals) === 0) {
            return $reward;
        }

        foreach ($investor->compressed_referrals as &$referral) {
            $reward += self::rewardForInvestor($referral, $rewardPerLevel, $level + 1);
        }

        return $reward;
    }
}