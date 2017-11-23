<?php

namespace core\models;

use core\engine\DB;

class Investor
{
    public $id = 0;
    public $referrer_id = 0;
    public $referrer_code = '';
    public $joined_datetime = 0;
    public $email = '';
    public $tokens_count = 0;
    public $eth_address = '';
    public $eth_withdrawn = 0;

    static public function getById($id)
    {
        $investor = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id])[0];

        if (!$investor) {
            return null;
        }

        $instance = new Investor();
        $instance->id = $investor['id'];
        $instance->referrer_id = $investor['referrer_id'];
        $instance->referrer_code = $investor['referrer_code'];
        $instance->joined_datetime = strtotime($investor['joined_datetime']);
        $instance->email = $investor['email'];
        $instance->tokens_count = $investor['tokens_count'];
        $instance->eth_address = $investor['eth_address'];
        $instance->eth_withdrawn = $investor['eth_withdrawn'];

        return $instance;
    }
}