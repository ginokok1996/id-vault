<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\UserGroup;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserGroupSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $userService;
    private $requestStack;

    public function __construct(CommongroundService $commonGroundService, UserService $userService, RequestStack $requestStack)
    {
        $this->commonGroundService = $commonGroundService;
        $this->userService = $userService;
        $this->requestStack = $requestStack;
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

        if ($group instanceof UserGroup && $event->getRequest()->getMethod() == 'POST') {
            if (!$group->getClientId() || !$group->getUsername()) {
                throw new Exception('no clientId and/or username provided');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);
                    $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $group->getUsername()])['hydra:member'][0];
                    $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId or username');
                }
                $groups = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'groups'], ['application.id' => $application['id'], 'memberships.userUrl' => $userUrl])['hydra:member'];
                $groupList = [];
                if (count($groups) > 0) {
                    foreach ($groups as $oldGroup) {
                        $newGroup = [];

                        $result = array_filter($oldGroup['memberships'], function ($var) use ($userUrl) {
                           return ($var['userUrl'] == $userUrl);
                        })[0];

                        if (!empty($result['dateAcceptedUser']) || !empty($result['dateAcceptedGroup'])) {
                            $newGroup['id'] = $oldGroup['id'];
                            $newGroup['@id'] = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().'/api/groups/'.$oldGroup['id'];
                            $newGroup['name'] = $oldGroup['name'];
                            if (isset($oldGroup['description'])) {
                                $newGroup['description'] = $oldGroup['description'];
                            }
                            isset($result['dateAcceptedUser']) ? $newGroup['dateJoined'] = $result['dateAcceptedUser'] : $newGroup['dateJoined'] = $result['dateAcceptedGroup'];
                            $newGroup['organization'] = $oldGroup['organization'];
                            $groupList[] = $newGroup;
                        }

                    }
                }
                $group->setGroups($groupList);

            }
        }

        return $group;
    }

}
