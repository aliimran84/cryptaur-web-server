<?php

namespace core\engine;

use core\models\Investor_controller;
use core\views\Base_view;

class Utility
{
    /**
     * send header location
     * @param $newRelativePath
     */
    static public function location($newRelativePath = '')
    {
        header('Location: /' . $newRelativePath);
        exit;
    }

    const ENCRYPTED_METHOD = 'AES-256-CBC';

    /**
     * @param mixed $data json-coverable data
     * @return string|false
     */
    static public function encodeData($data)
    {

        $string = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$string) {
            return false;
        }
        $iv = substr(hash('sha256', APPLICATION_ID), 0, 16);

        $encryptedString = openssl_encrypt($string, self::ENCRYPTED_METHOD, APPLICATION_ID, 0, $iv);
        if (!$encryptedString) {
            return false;
        }
        $encryptedString = base64_encode($encryptedString);

        return $encryptedString;
    }

    /**
     * @param string $encryptedString
     * @return mixed|false
     */
    static public function decodeData($encryptedString)
    {
        $iv = substr(hash('sha256', APPLICATION_ID), 0, 16);
        $decryptedString = @openssl_decrypt(base64_decode($encryptedString), self::ENCRYPTED_METHOD, APPLICATION_ID, 0, $iv);
        if (!$decryptedString) {
            return false;
        }
        $data = json_decode($decryptedString, true);
        if (!$data) {
            return false;
        }
        return $data;
    }

    static public function validateEthAddress($eth_address)
    {
        return !!preg_match("/^0x[a-fA-F0-9]{40}$/", $eth_address);
    }


    /**
     * @param string $url
     * @param array $data
     * @return mixed
     */
    static public function httpPost($url, $data = [])
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}