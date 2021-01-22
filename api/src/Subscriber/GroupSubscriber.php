<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Group;
use App\Service\UserService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class GroupSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $userService;
    private $serializer;

    public function __construct(CommongroundService $commonGroundService, UserService $userService, SerializerInterface $serializer)
    {
        $this->commonGroundService = $commonGroundService;
        $this->userService = $userService;
        $this->serializer = $serializer;
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
            if (!$group->getClientId() || !$group->getOrganization()) {
                throw new Exception('no clientId and/or organization provided');
            } else {
                try {
                    $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $group->getClientId()]);
                    $groups = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'groups'], ['application.id' => $application['id']])['hydra:member'];
                    $groupList = [];
                    if (count($groups) > 0) {
                        foreach ($groups as $oldGroup) {
                            $newGroup = [];
                            $newGroup['name'] = $oldGroup['name'];
                            $newGroup['id'] = $oldGroup['id'];
                            $newGroup['@id'] = $event->getRequest()->getSchemeAndHttpHost().'/api/groups/'.$oldGroup['id'];
                            if (isset($oldGroup['description'])) {
                                $newGroup['description'] = $oldGroup['description'];
                            }
                            $newGroup['users'] = [];
                            if (count($oldGroup['memberships']) > 0) {
                                foreach ($oldGroup['memberships'] as $membership) {
                                    if (!empty($membership['dateAcceptedUser']) || !empty($membership['dateAcceptedGroup'])) {
                                        $user = $this->commonGroundService->getResource($membership['userUrl']);
                                        $newGroup['users'][] = $user['username'];
                                    }
                                }
                            }
                            if (!empty($oldGroup['organization']) && $oldGroup['organization'] == $group->getOrganization()) {
                                $newGroup['organization'] = $oldGroup['organization'];
                                $groupList[] = $newGroup;
                            }
                        }
                    }

                    $group->setGroups($groupList);
                } catch (\Throwable $e) {
                    throw new  Exception('Invalid clientId');
                }
            }
        } elseif ($event->getRequest()->getMethod() == 'GET' && $event->getRequest()->get('_route') == 'api_groups_get_group_collection') {
            $id = $event->getRequest()->attributes->get('id');

            try {
                $group = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'groups', 'id' => $id]);
            } catch (\Throwable $e) {
                throw new  Exception('Invalid group id');
            }
            $result = [];
            $result['id'] = $group['id'];
            $result['@id'] = $event->getRequest()->getSchemeAndHttpHost().'/api/groups/'.$id;
            $result['name'] = $group['name'];
            $result['description'] = $group['description'];
            $result['organization'] = $group['organization'];
            $result['users'] = [];
            foreach ($group['memberships'] as $membership) {
                $user = $this->commonGroundService->getResource($membership['userUrl']);
                $result['users'][] = $user['username'];
            }

            $json = $this->serializer->serialize(
                $result,
                'json'
            );

            $response = new Response(
                $json,
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );

            $event->setResponse($response);
        }

        return $group;
    }
}
