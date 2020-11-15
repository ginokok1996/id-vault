<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class ClaimService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    public function checkUserScope($person, $scope)
    {
        $scopes = $this->getUserScopes($person);

        if (array_key_exists($scope, $scopes)) {
            return true;
        }

        return false;
    }

    public function getUserScopes($person)
    {
        $claims = $this->commonGroundService->getResourceList(['component'=>'wac', 'type'=>'claims'], ['person'=>$person])['hydra:member'];

        $results = [];
        foreach ($claims as $claim) {
            $results[$claim['property']] = $claim;
        }

        return $results;
    }
}
