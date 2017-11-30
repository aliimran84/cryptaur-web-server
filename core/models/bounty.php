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
     * @param Investor $referrer
     * @return int
     */
    static public function rewardForReferrer(&$referrer)
    {
        $reward = 0;
        $investorOnLevels = [
            -1 => [$referrer]
        ];
        foreach (self::program() as $level => $percent) {
            $investorOnLevels[$level] = [];
            foreach ($investorOnLevels[$level - 1] as &$investor) {
                $investorOnLevels[$level] += Investor::summonedBy($investor, true);
            }
            foreach ($investorOnLevels[$level] as &$investor) {
                // todo: REPLCAE $investor->tokens_count. Must calc only deposits that not used at bounty in the past
                $reward += $investor->tokens_count * $percent / 100;
            }
        }
        return $reward;
    }
}