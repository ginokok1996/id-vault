<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class MailService
{

    private $client;

    public function __construct()
    {

        $this->client = new Client();
    }

    public function sendMail($mailgunKey, $mailgunDomain, $body, $subject, $receiver, $sender)
    {
        $url = "mailgun+api://{$mailgunKey}:{$mailgunDomain}@api.eu.mailgun.net";

        $transport = Transport::fromDsn($url);
        $mailer = new Mailer($transport);

        $text = strip_tags(preg_replace('#<br\s*/?>#i', "\n", $body), '\n');

        $email = (new Email())
            ->from($sender)
            ->to($receiver)
            ->subject($subject)
            ->html($body)
            ->text($text);

        $mailer->send($email);
    }
}
