<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CreateGroup;
use App\Service\GroupService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CreateGroupSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $groupService;

    public function __construct(CommongroundService $commonGroundService, GroupService $groupService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->groupService = $groupService;
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
            if (!$group->getClientId() || !$group->getOrganization() || !$group->getDescription() || !$group->getName()) {
                throw new Exception('Please provide all required properties');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
                $group->setGroup($this->groupService->createGroup($group, $application));
            }
        }
        return $group;
    }
}
