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

    public function throwFlash($type, $message)
    {
        $this->flash->add($type, $message);
    }
}
