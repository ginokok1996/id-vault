<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use GuzzleHttp\Client;

class MailService
{
    private $em;
    private $commonGroundService;
    private $params;
    private $claimService;
    private $client;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params, ClaimService $claimService)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->claimService = $claimService;

        $this->client = new Client();
    }

    public function sendMail($mailgunKey, $mailgunDomain, $body, $subject, $receiver, $sender) {

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

        /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
        $mailer->send($email);
    }
}
