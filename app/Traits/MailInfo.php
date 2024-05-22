<?php
namespace App\Traits;

trait MailInfo {

	public function setMailInfo($mail_setting) 
    {
        config()->set('mail.driver', $mail_setting->driver);
        config()->set('mail.host', $mail_setting->host);
        config()->set('mail.port', $mail_setting->port);
        config()->set('mail.from.address', $mail_setting->from_address);
        config()->set('mail.from.name', $mail_setting->from_name);
        config()->set('mail.username', $mail_setting->username);
        config()->set('mail.password', $mail_setting->password);
        config()->set('mail.encryption', $mail_setting->encryption);
    }
}