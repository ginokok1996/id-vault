<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Mail;
use App\Service\MailService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MailSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $mailService;

    public function __construct(CommongroundService $commonGroundService, MailService $mailService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->mailService = $mailService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['sendMail', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function sendMail(ViewEvent $event)
    {
        $mail = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $authentication = $event->getRequest()->headers->get('authentication');
        $route = $event->getRequest()->attributes->get('_route');

        if ($method != 'POST') {
            return;
        }
        if ($mail instanceof Mail) {
            try {
                $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $mail->getApplicationId()]);
            } catch (\Throwable $e) {
                throw new  Exception('Invalid authentication header');
            }

            if (!isset($application['mailgunApiKey']) && !isset($application['mailgunDomain'])) {
                throw new  Exception('MailgunApiKey or mailgunDomain is not defined in your application');
            }

            $this->mailService->sendMail($application['mailgunApiKey'], $application['mailgunDomain'], $mail->getBody(), $mail->getSubject(), $mail->getReceiver(), $mail->getSender());
            $mail->setMessage('Mail send to mailgun');
        }

        return $mail;
    }
}
