<?php

namespace core\controllers;

use core\models\Investor;

class Bounty_controller
{
    /**
     * @param Investor $investor
     * @param double $tokens
     * @param string $coin
     * @param string $txid
     * @return bool
     */
    static public function mintTokens($investor, $tokens, $coin, $txid)
    {
        //todo: body
        return true;
    }

    static public function withdraw($investor, $value)
    {
        //todo: body
    }
}