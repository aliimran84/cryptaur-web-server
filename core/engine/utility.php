<?php

namespace core\engine;

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
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * @param string $url
     * @param array $data
     * @param string $hmac_key
     * @return mixed
     */
    static public function httpPostWithHmac($url, $data = [], $hmac_key = '')
    {
        $query = http_build_query($data);
        $hmac_signature = hash_hmac('sha256', $query, pack("H*", $hmac_key));
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "HMAC-Signature: $hmac_signature"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
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
        return !!file_put_contents("$logsDir/$file", $output, FILE_APPEND);
    }

    /**
     * @param string $file
     * @param mixed $data
     * @return bool
     */
    static public function log($file, $data)
    {
        $logsDir = PATH_TO_TMP_DIR . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0777, true);
            chmod($logsDir, 0777);
        }
        self::mkdir_0777(dirname("$logsDir/$file"));
        return !!file_put_contents("$logsDir/$file", json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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

    static public function bchexdec($hex)
    {
        if (strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return \bcadd(bcmul(16, self::bchexdec($remain)), hexdec($last));
        }
    }

    static public function bcdechex($dec)
    {
        $last = \bcmod($dec, 16);
        $remain = \bcdiv(\bcsub($dec, $last), 16);

        if ($remain == 0) {
            return dechex($last);
        } else {
            return self::bcdechex($remain) . dechex($last);
        }
    }

    /**
     * @param string|number $dec
     * @return string
     */
    static public function hex($dec)
    {
        return self::bcdechex(number_format($dec, 0, '.', ''));
    }

    /**
     * @param double $dec
     * @return double
     */
    static public function minPrecisionNumber($dec)
    {
        return (double)number_format($dec, 18, '.', '');
    }

    /**
     * @param string|number $dec1
     * @param string|number $dec2
     * @return double
     */
    static public function mul($dec1, $dec2)
    {
        return bcmul(
            number_format($dec1, 18, '.', ''),
            number_format($dec2, 18, '.', ''),
            18
        );
    }

    static public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}