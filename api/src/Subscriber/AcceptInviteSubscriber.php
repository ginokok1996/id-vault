<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AcceptInvite;
use App\Service\GroupService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Config\Definition\Exception\Exception;


class AcceptInviteSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['acceptInvite', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function acceptInvite(ViewEvent $event)
    {
        $invite = $event->getControllerResult();

        if ($invite instanceof AcceptInvite && $event->getRequest()->getMethod() == 'POST') {
            if (!$invite->getGroupId() || !$invite->getClientId()) {
                throw new Exception('no group or client id provided');
            } else {
                //try {
                    $selectedGroup = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'groups', 'id' => $invite->getGroupId()]);
                    if ($selectedGroup['application']['id'] !== $invite->getClientId()) {
                        throw new Exception('Client id does not match with group');
                    }
                    $this->groupService->acceptInvite($invite->getUsername(), $selectedGroup);
//                } catch (\Throwable $e) {
//                    throw new  Exception('Invalid group id');
//                }
            }
        }
        return $invite;
    }
}
