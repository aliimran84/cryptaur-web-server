<?php

namespace core\engine;

use core\controllers\Investor_controller;
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

    /**
     * Создает всю структуру папок с правами 0777
     * Нужна, т.к. mkdir(, 0777) отрезает текущую umask и получается 0755
     * http://stackoverflow.com/questions/3997641/why-cant-php-create-a-directory-with-777-permissions
     * @param string $path
     * @return bool
     */
    static public function mkdir_0777($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (is_file($path)) {
            return false;
        }

        $pathArr = explode("/", $path);

        if (count($pathArr) == 0)
            return false;

        if ($pathArr[0] == "")
            unset($pathArr[0]);

        $currDir = "";

        foreach ($pathArr AS $dir) {
            $currDir .= "/" . $dir;
            if (!is_dir($currDir)) {
                if (!mkdir($currDir, 0777, true))
                    return false;
                if (!chmod($currDir, 0777))
                    return false;
            }
        }

        return true;
    }

    /**
     * @param string $file relative path
     * @param mixed $additionalData
     * @return bool
     */
    static public function logOriginalRequest($file, $additionalData = null)
    {
        $logsDir = PATH_TO_TMP_DIR . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0777, true);
            chmod($logsDir, 0777);
        }

        ob_start();
        var_dump(self::getRequestDataArr());
        var_dump($additionalData);
        $output = ob_get_clean();

        $output = date('Y-m-d H:i:s') . "\n$output\n";

        self::mkdir_0777(dirname("$logsDir/$file"));
        return !!file_put_contents("$logsDir/$file", $output);
    }

    /**
     * @return array
     */
    static public function getRequestDataArr()
    {
        $array = [];

        if (function_exists('getallheaders')) {
            $array['headers'] = getallheaders();
        } else {
            $array['headers'] = "getallheaders() not exist";
        }
        $array['get'] = $_GET;
        $array['post'] = $_POST;
        $array['phpinput'] = @file_get_contents('php://input');
        $array['files'] = $_FILES;

        return $array;
    }
}