<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;

class GroupService
{
    private $commonGroundService;
    private $userService;

    public function __construct(CommonGroundService $commonGroundService, UserService $userService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->userService = $userService;
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
