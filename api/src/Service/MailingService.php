<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultBundle\Service\IdVaultService;
use Twig\Environment;

class MailingService
{

    private $twig;
    private $idVaultService;

    public function __construct(CommonGroundService $commonGroundService, IdVaultService $idVaultService, Environment $twig)
    {
        $this->idVaultService = $idVaultService;
        $this->twig = $twig;
    }

    /**
     * This function renders an twig template (optionally with data) and sends the mail request to ID-Vault.
     *
     * @param string $template path to email template.
     * @param string $sender   email of the sender.
     * @param string $receiver email of the receiver.
     * @param array  $data     (optional) array used to render the template with twig.
     * @param string $subject  subject of the email.
     *
     * @return array|false array response from id-vault or false if failed.
     */
    public function sendMail(string $template, string $sender, string $receiver, string $subject, array $data = [])
    {
        $body = $this->twig->render($template, $data);

        return $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $receiver, $sender);
    }
}
