<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\GroupInvite;
use App\Service\GroupService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class GroupInviteSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['inviteUser', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function inviteUser(ViewEvent $event)
    {
        $group = $event->getControllerResult();

        if ($group instanceof GroupInvite && $event->getRequest()->getMethod() == 'POST') {
            if (!$group->getGroupId() || !$group->getClientId()) {
                throw new Exception('no group or client id provided');
            } else {
                try {
                    $selectedGroup = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'groups', 'id' => $group->getGroupId()]);
                    if ($selectedGroup['application']['id'] !== $group->getClientId()) {
                        throw new Exception('Client id does not match with group');
                    }
                    $this->groupService->inviteUser($group->getUsername(), $selectedGroup, $group->getAccepted());
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid group id');
                }
            }
        }

        return $group;
    }
}
