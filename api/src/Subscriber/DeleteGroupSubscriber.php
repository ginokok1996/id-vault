<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CreateGroup;
use App\Entity\DeleteGroup;
use App\Service\GroupService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DeleteGroupSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['deleteGroup', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function deleteGroup(ViewEvent $event)
    {
        $group = $event->getControllerResult();
        if ($group instanceof DeleteGroup && $event->getRequest()->getMethod() == 'POST') {
            if (!$group->getClientId() || !$group->getOrganization()) {
                throw new Exception('Please provide all required properties');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
                $group->setGroups($this->groupService->deleteGroups($group, $application));
            }
        }

        return $group;
    }
}
