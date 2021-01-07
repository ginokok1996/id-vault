<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CreateGroup;
use App\Entity\Group;
use App\Entity\User;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Config\Definition\Exception\Exception;


class CreateGroupSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;

    public function __construct(CommongroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['createGroup', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function createGroup(ViewEvent $event)
    {
        $group = $event->getControllerResult();

        if ($group instanceof CreateGroup && $event->getRequest()->getMethod() == 'POST') {
            if (!$group->getClientId()) {
                throw new Exception('no clientId provided');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);

                    $newGroup = [];
                    $newGroup['name'] = $group->getName();
                    if ($group->getDescription() !== null) {
                        $newGroup['description'] = $group->getDescription();
                    }
                    $newGroup['application'] = '/applications/'.$application['id'];
                    if ($group->getOrganization() !== null) {
                        $newGroup['organization'] = $group->getOrganization();
                    }
                    $this->commonGroundService->createResource($newGroup, ['component' => 'wac', 'type' => 'groups']);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
            }
        }
        return $group;
    }
}
