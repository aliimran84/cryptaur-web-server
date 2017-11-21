<?php

namespace core;

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
     * @param array $files
     * @return bool - true, если успешно отправлено
     */
    static public function send($to, $copyTo, $subject, $message, $files = array())
    {
        self::inst();

        $from = EMAIL_FROM_EMAIL;
        $smtp_host = EMAIL_HOST;
        $smtp_login = EMAIL_LOGIN;
        $smtp_password = EMAIL_PASSWORD;
        $smtp_port = EMAIL_PORT;
        $secure = 'ssl';

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
        $mail->msgHTML($message);

        //Replace the plain text body with one created manually
        //$mail->AltBody = 'This is a plain-text message body';

        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.png');
        foreach ($files as $file) {
            $mail->addAttachment($file);
        }

        //send the message, check for errors
        return $mail->send();
    }
}

function sendEmail($to, $copyTo, $subject, $message, $files = array())
{
    $from = EMAIL_FROM_EMAIL;
    $smtp_host = EMAIL_HOST;
    $smtp_login = EMAIL_LOGIN;
    $smtp_password = EMAIL_PASSWORD;
    $smtp_port = EMAIL_PORT;
    $secure = 'ssl';

    $reply_to = EMAIL_REPLY_TO_EMAIL;

    //Create a new PHPMailer instance
    $mail = new PHPMailer;

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
    $mail->msgHTML($message);

    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';

    //Attach an image file
    //$mail->addAttachment('images/phpmailer_mini.png');
    foreach ($files as $file) {
        $mail->addAttachment($file);
    }

    //send the message, check for errors
    return $mail->send();
}