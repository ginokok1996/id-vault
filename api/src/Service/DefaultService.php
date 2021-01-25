<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class DefaultService
{
    private $commonGroundService;
    private $params;
    private $flash;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params, FlashBagInterface $flash)
    {
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->flash = $flash;
    }

    public function getApplication()
    {
        return $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);
    }

    public function getProvider($type)
    {
        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => $type, 'application' => $this->params->get('app_id')])['hydra:member'];
        if (count($providers) > 0) {
            return $providers[0];
        } else {
            return false;
        }
    }

    public function singleSignOn($authorizations)
    {
        foreach ($authorizations as &$authorization) {
            if (isset($authorization['application']['singleSignOnUrl']) && in_array('single_sign_on', $authorization['scopes'])) {
                $application = $this->commonGroundService->isResource($authorization['application']['contact']);
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $authorization['backgroundColor'] = $matches;
                }

                $authorization['singleSignOnUrl'] = $authorization['application']['singleSignOnUrl']."?code={$authorization['id']}";
            }
        }

        return $authorizations;
    }

    public function getUserUrl($username)
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];

        return $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
    }

    public function throwFlash($type, $message)
    {
        $this->flash->add($type, $message);
    }

    public function calculateMargin($cost, $balance)
    {
        return (1 - $cost / $balance) * 100;
    }
}
