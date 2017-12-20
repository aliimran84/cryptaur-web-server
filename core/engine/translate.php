<?php


namespace core\translate;

class Translate
{
    static public $translation = [];

    static public $translationsDir = __DIR__ . '/translations';

    static private $defaultLang = null;

    static public function init()
    {
        self::$translation['en'] = self::getTranslateArrayFromCsv('en');
        self::$translation['ru'] = self::getTranslateArrayFromCsv('ru');
        self::defaultLang();
    }

    static public function defaultLang()
    {
        if (is_null(self::$defaultLang)) {
            self::$defaultLang = self::getCurrentLanguage();
        }
        return self::$defaultLang;
    }

    /**
     * @param string $lang
     * @return array
     */
    static private function getTranslateArrayFromCsv($lang)
    {
        $resultArray = [];
        $handle = fopen(self::$translationsDir . "/$lang.csv", 'r');
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== 2) {
                continue;
            }
            $resultArray[$data[0]] = $data[1];
        }
        fclose($handle);
        return $resultArray;
    }

    /**
     * TRanslate
     * @param string $lang
     * @param string $code
     * @param array $variables
     * @return string
     */
    static public function tr($lang, $code, $variables = [])
    {
        $translation = Translate::$translation;

        if (!isset($variables['APPLICATION_URL'])) {
            $variables['APPLICATION_URL'] = APPLICATION_URL;
        }

        if (!isset($translation[$lang])) {
            error_log("tr $lang");
            return "code:$code";
        }

        if (!isset($translation[$lang][$code])) {
            error_log("tr $lang $code");
            return "code:$code";
        }

        $str = $translation[$lang][$code];

        foreach ($variables as $name => $variable) {
            $str = mb_ereg_replace("%%$name%%", $variable, $str);
        }

        return $str;
    }

    /**
     * Translate with Default language
     * @param string $code
     * @param array $variables
     * @return string
     */
    static public function td($code, $variables = [])
    {
        return self::tr(self::$defaultLang, $code, $variables);
    }

    /**
     * @return string
     */
    static private function getCurrentLanguage()
    {
        $lang = 'en';
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
        if ($lang !== 'ru') {
            $lang = 'en'; // только русским выставляем русский язык, а всем остальным - английский
        }
        if (isset($_GET['language'])) {
            if ($_GET['language'] === 'ru') {
                $lang = 'ru';
            } else if ($_GET['language'] === 'en') {
                $lang = 'en';
            }
            setcookie('language', $lang, time() + 3600 * 24 * 365, '/');

            $newUri = mb_ereg_replace("\?language=$lang", "?", $_SERVER['REQUEST_URI']);
            $newUri = mb_ereg_replace("\&language=$lang", "", $newUri);
            $newUri = trim($newUri, '?');
            header('Location: ' . $newUri);
            exit();
        }
        if (isset($_COOKIE['language'])) {
            if ($_COOKIE['language'] === 'ru') {
                $lang = 'ru';
            } else if ($_COOKIE['language'] === 'en') {
                $lang = 'en';
            } else {
                setcookie('language', null, 0, '/');
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                exit();
            }
        }
        return $lang;
    }

    /**
     * @param string $lang
     * @return string href
     */
    static public function languageSwitchHref($lang)
    {
        $getParamSymbol = '&';
        if (@mb_strpos($_SERVER['REQUEST_URI'], '?') === false) {
            $getParamSymbol = '?';
        }
        return "{$_SERVER['REQUEST_URI']}{$getParamSymbol}language={$lang}";
    }
}
