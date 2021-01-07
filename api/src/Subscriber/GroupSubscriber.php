<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Group;
use App\Entity\User;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Config\Definition\Exception\Exception;


class GroupSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['getGroups', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function getGroups(ViewEvent $event)
    {
        $group = $event->getControllerResult();

        if ($group instanceof Group && $event->getRequest()->getMethod() == 'POST') {
            if (!$group->getClientId()) {
                throw new Exception('no clientId provided');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);
                    $group->setGroups($this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'groups'], ['application.id' => $application['id']])['hydra:member']);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
            }
        }
        return $group;
    }
}
