<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
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
            if (filter_var($user->getUsername(), FILTER_VALIDATE_EMAIL)) {
                $result = $this->userService->createUser($user->getUsername());
                if ($result !== false) {
                    $user->setUser($result);
                    $user->setMessage('User has been created');
                } else {
                    $user->setMessage('Email address is already taken');
                }
            } else {
                $user->setMessage('Email address is invalid');
            }
        }

        return $user;
    }
}
