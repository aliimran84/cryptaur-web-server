<?php

namespace core\engine;

class Email
{
    /**
     * singleton object
     * @var Email
     */
    static private $_instance;

    /**
     * @return Email
     */
    static private function inst()
    {
        if (is_null(self::$_instance)) {
            require(PATH_TO_THIRD_PARTY_DIR . '/PHPMailer/PHPMailerAutoload.php');
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
    }

    /**
     * @param string $to - адрес почты кому
     * @param array $copyTo
     * @param string $subject - тема письма
     * @param string $message - тело письма
     * @param bool $withTemplate - обернуть ли письмо в шаблон
     * @param array $files
     * @return bool - true, если успешно отправлено
     */
    static public function send($to, $copyTo, $subject, $message, $withTemplate = false, $files = array())
    {
        self::inst();

        $from = EMAIL_FROM_EMAIL;
        $smtp_host = EMAIL_HOST;
        $smtp_login = EMAIL_LOGIN;
        $smtp_password = EMAIL_PASSWORD;
        $smtp_port = EMAIL_PORT;
        $secure = EMAIL_SECURE;

        $reply_to = EMAIL_REPLY_TO_EMAIL;

        //Create a new PHPMailer instance
        $mail = new \PHPMailer;

        $mail->CharSet = 'UTF-8';

        //Tell PHPMailer to use SMTP
        $mail->isSMTP();

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false
//            'verify_peer_name' => false
//            'allow_self_signed' => true
            )
        );

        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;

        //Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';

        //Set the hostname of the mail server
        $mail->Host = $smtp_host;
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = $smtp_port;

        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = $secure;

        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;

        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $smtp_login;

        //Password to use for SMTP authentication
        $mail->Password = $smtp_password;

        //Set who the message is to be sent from
        $mail->setFrom($from, EMAIL_FROM_NAME);

        //Set an alternative reply-to address
        $mail->addReplyTo($reply_to, EMAIL_REPLY_TO_NAME);

        if (strlen($to) < 2 && count($copyTo) > 0) { // в данной реализации "кому" обязательно должно быть заполнено
            $to = array_shift($copyTo);
        }

        //Set who the message is to be sent to
        $mail->addAddress($to);

        foreach ($copyTo as $addr) {
            $mail->addCC($addr);
        }

        //Set the subject line
        $mail->Subject = $subject;

        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        //$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
        if ($withTemplate) {
            $message = self::wrapMessageInTemplate($message);
        }
        $mail->msgHTML($message);

        //Replace the plain text body with one created manually
        //$mail->AltBody = 'This is a plain-text message body';

        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.png');
        foreach ($files as $file) {
            $mail->addAttachment($file);
        }

        //send the message, check for errors
        if (!$mail->send()) {
            error_log("Email SMPT error: {$mail->ErrorInfo}");
            return false;
        }
        return true;
    }

    static private function wrapMessageInTemplate($message)
    {
        $domain = APPLICATION_URL;
        $html = <<<EOT
            <div class="email" style="width: 595px;margin: 0 auto;">
                <div class="logo-block" style="width: 100%;margin: 30px 0;text-align: center;">
                    <a href="$domain" class="logo"><img style="width: 300px;" src="$domain/images/CRYPTAUR_written.png" alt="cryptaur"></a>
                </div>
                <div class="menu" style="width: 100%;height: 38px;background: rgba(33, 43, 90, 1);">
                    <ul style="margin: 0;padding: 0;">
                        <li style="float: left;list-style:none;width:50%;height:38px;line-height:38px;margin:0;text-align: center;text-decoration: underline;"><a style="font-size: 12px;color: #ffffff;font-family: sans-serif;font-style: normal;font-weight: 400;text-transform: uppercase;" href="$domain/home_ru">Home</a></li>
                        <li style="float: left;list-style:none;width:50%;height:38px;line-height:38px;margin:0;text-align: center;text-decoration: underline;"><a style="font-size: 12px;color: #ffffff;font-family: sans-serif;font-style: normal;font-weight: 400;text-transform: uppercase;" href="$domain/home_ru#contacts">Contract</a></li>
                    </ul>
                </div>
                <div class="message" style="color: rgba(146, 146, 146, 1);">
                    $message
                </div>
                <div class="logo-block-footer" style="text-align:center;">
                    <a href="$domain" style="margin:25px auto;display:inline-block;" class="logo"><img style="width: 95px;" src="$domain/images/CRYPTAUR_AVECTYPOCENTRE.png" alt="cryptaur"></a>
                </div>
                <div class="footer" style="width: 100%;margin: 0;text-align: center;">
                    <p style="font-size: 14px;font-style: italic;font-weight: 400;line-height: 1.8;width: 50%;margin:0 auto;text-align: center;color: rgba(146, 146, 146, 1);">If you would like to stop receiving invites from other people, follow the <a style="text-decoration: underline;" href="$domain">link</a></p>
                </div>
            </div>
EOT;
        return $html;
    }
}