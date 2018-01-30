<?php

namespace core\captcha;

class Captcha
{
    const TEMP_CAPTCHA = 'temp_captcha';
    
    static public function checkCaptcha($code)
    {
        if (!isset($_SESSION[self::TEMP_CAPTCHA]) || $_SESSION[self::TEMP_CAPTCHA] != $code) {
            return FALSE;
        }
        session_start();
        unset($_SESSION[self::TEMP_CAPTCHA]);
        session_write_close();
        return TRUE;
    }
    
    static public function generateCaptcha()
    {
        list($code, $image) = self::rawCaptcha();
        session_start();
        $_SESSION[self::TEMP_CAPTCHA] = $code;
        session_write_close();
        return $image;
    }
    
    static private function rawCaptcha()
    {
        $letters = 'ABCDEFGKIJKLMNOPQRSTUVWXYZ0123456789';
        $background = __DIR__.'/backgrounds/kinda-jean.png';
        $caplen = 6;

        list($width, $height) = getimagesize($background);

        $font = __DIR__.'/fonts/times_new_yorker.ttf';
        $fontsize = 18;

        $im = imagecreatefrompng($background);
        //$im = imagecreatetruecolor($width, $height);
        //imagesavealpha($im, true);
        //$bg = imagecolorallocatealpha($im, 0, 0, 0, 127);
        //imagefill($im, 0, 0, $bg);

        $captcha = '';
        for ($i = 0; $i < $caplen; $i++) {
            $captcha .= $letters[rand(0, strlen($letters) - 1)];
            $x = ($width - 6) / $caplen * $i + 10;
            $x = rand($x, $x + 4);
            $y = $height - (($height - rand($fontsize - 9, $fontsize + 9)) / 2);
            $curcolor = imagecolorallocate($im, rand(0, 100), rand(0, 100), rand(0, 100));
            $angle = rand(-55, 55);
            imagettftext($im, $fontsize, $angle, $x, $y, $curcolor, $font, $captcha[$i]);
        }

        $file = PATH_TO_TMP_DIR . '/' . $captcha . '.png';

        imagepng($im, $file);
        imagedestroy($im);

        $data = base64_encode(file_get_contents($file));

        unlink($file);

        return [$captcha, $data];
    }
}