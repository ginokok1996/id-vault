<?php

namespace App\Service;

use App\Entity\CreateGroup;
use App\Entity\DeleteGroup;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RequestStack;

class GroupService
{
    private $commonGroundService;
    private $userService;
    private $requestStack;

    public function __construct(CommonGroundService $commonGroundService, UserService $userService, RequestStack $requestStack)
    {
        $this->commonGroundService = $commonGroundService;
        $this->userService = $userService;
        $this->requestStack = $requestStack;
    }

    public function createGroup(CreateGroup $group, $application)
    {
        $newGroup = [];
        $newGroup['name'] = $group->getName();
        $newGroup['description'] = $group->getDescription();
        $newGroup['application'] = '/applications/'.$application['id'];
        $newGroup['organization'] = $group->getOrganization();
        $newGroup = $this->commonGroundService->createResource($newGroup, ['component' => 'wac', 'type' => 'groups']);

        return $this->groupResponse($newGroup);
    }

    public function deleteGroup($groupId, DeleteGroup $deleteGroup = null, $application = null)
    {
        $group = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'groups', 'id' => $groupId], [], false);

        if (isset($application) and $group['application']['id'] != $application['id']) {
            throw new Exception('No group found with these properties');
        }
        if (isset($deleteGroup) and $deleteGroup->getOrganization() and $group['organization'] != $deleteGroup->getOrganization()) {
            throw new Exception('No group found with these properties');
        }

        // TODO: Delete all memberships of this group (new function)

        $this->commonGroundService->deleteResource($group);
        return $group['@id'];
    }

    public function deleteGroups(DeleteGroup $deleteGroup, $application)
    {
        $groupList = [];
        $groups = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'groups'], ['application.id' => $application['id']])['hydra:member'];
        if (count($groups) > 0) {
            foreach ($groups as $group) {
                if ($group['organization'] == $deleteGroup->getOrganization()) {
                    // TODO: Delete all memberships of this group (new function)
                    $this->commonGroundService->deleteResource($group);
                    array_push($groupList, $group['@id']);
                }
            }
            return $groupList;
        } else {
            throw new Exception('No group found with these properties');
        }
    }

    public function groupResponse($group)
    {
        $result = [];
        $result['id'] = $group['id'];
        $result['@id'] = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().'/api/groups/'.$group['id'];
        $result['name'] = $group['name'];
        $result['description'] = $group['description'];
        $result['organization'] = $group['organization'];

        return $result;
    }

    public function inviteUser($username, $group, $accepted)
    {
        $membership = [];
        $membership['userGroup'] = '/groups/'.$group['id'];
        $membership['userUrl'] = $this->userService->createUser($username);
        if ($accepted) {
            $date = new \DateTime('today');
            $membership['dateAcceptedUser'] = $date->format('Y-m-d');
        }
        $this->commonGroundService->createResource($membership, ['component' => 'wac', 'type' => 'memberships']);
    }

    public function removeUser($username, $group)
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];
        if (!count($users) > 0) {
            throw new Exception('User not registered with idvault');
        }
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);

        $exist = false;
        foreach ($group['memberships'] as $membership) {
            if ($membership['userUrl'] == $userUrl) {
                $this->commonGroundService->deleteResource($membership);
                $exist = true;
            }
        }
        if (!$exist) {
            throw new Exception('No membership found');
        }
    }

    public function acceptInvite($username, $group)
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];

        if (!count($users) > 0) {
            throw new Exception('User not registered with idvault');
        }
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        $exist = false;
        foreach ($group['memberships'] as &$membership) {
            if ($membership['userUrl'] == $userUrl) {
                $exist = true;
                if ($membership['dateAcceptedGroup'] == null && $membership['dateAcceptedUser'] == null) {
                    $date = $date = new \DateTime('today');
                    $membership['dateAcceptedUser'] = $date->format('Y-m-d');
                    $this->commonGroundService->updateResource($membership);
                } else {
                    throw new Exception('invite was already accepted');
                }
            }
        }

        if (!$exist) {
            throw new Exception('No membership found');
        }
    }
}
