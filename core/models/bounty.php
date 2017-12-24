<?php

namespace core\models;

use core\engine\Application;

class Bounty
{
    const CURRENT_BOUNTY_PROGRAM = [3, 3, 3, 3, 4, 4];

    const WITHDRAW_IS_ON = 'withdraw_is_on';
    const REINVEST_IS_ON = 'reinvest_is_on';

    /**
     * @return int[]
     */
    static public function program()
    {
        return self::CURRENT_BOUNTY_PROGRAM;
    }

    /**
     * reward in US$
     * @param Investor $investor
     * @param [] [0=>1, 1=>5, ...]
     * @param int $level
     * @return double ETH
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
            $reward += $investor->eth_not_used_in_bounty * self::program()[$level - 1] / 100;
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

    /**
     * Wright rewardForInvestor to eth_bounty for all investors and set 0 tokens_not_used_in_bounty
     */
    static public function fixBounty()
    {
        // взять всех инвесторов, для каждого посчитать rewardForInvestor, полученное значение добавить в eth_bounty
        // по окончанию для всех нужно обнулить tokens_not_used_in_bounty обнулить
        // todo: fill body
    }

    /**
     * @return bool
     */
    static public function withdrawIsOn()
    {
        return Application::getValue(self::WITHDRAW_IS_ON);
    }

    /**
     * @return bool
     */
    static public function reinvestIsOn()
    {
        return Application::getValue(self::REINVEST_IS_ON);
    }
}