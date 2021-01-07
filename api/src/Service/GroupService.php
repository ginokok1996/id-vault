<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GroupService
{
    private $commonGroundService;
    private $defaultService;

    public function __construct(CommonGroundService $commonGroundService, DefaultService $defaultService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->defaultService = $defaultService;
    }

}
