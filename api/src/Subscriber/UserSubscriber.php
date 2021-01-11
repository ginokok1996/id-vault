<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $userService;

    public function __construct(CommongroundService $commonGroundService, UserService $userService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->userService = $userService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['createUser', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function createUser(ViewEvent $event)
    {
        $user = $event->getControllerResult();

        if ($user instanceof User && $event->getRequest()->getMethod() == 'POST') {
            if (!$user->getScopes()) {
                throw new Exception('no scopes provided');
            }

            if (!$user->getClientId()) {
                throw new Exception('no clientId provided');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $user->getClientId()]);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
            }

            if (filter_var($user->getUsername(), FILTER_VALIDATE_EMAIL)) {
                $result = $this->userService->createUser($user->getUsername());
                $authorization = $this->userService->createAuthorization($result, $application, $user->getScopes());
                $user->setAuthorization($authorization['id']);
            } else {
                $user->setMessage('Email address is invalid');
            }
        }

        return $user;
    }
}
