<?php

namespace sunframework\system;

/**
 * Class MailUtil
 * @package sunframework\system
 *
 * To send a mail, use https://github.com/PHPMailer/PHPMailer and it will save you a lot of time.
 */
class MailUtil {
    /**
     * Get a list of temporary e-mails that you might want to filter.
     * @return array
     */
    public static function GetExcludedList() {
        return explode("\n", file_get_contents(__DIR__ . '/MailExcludeList.txt'));
    }
}