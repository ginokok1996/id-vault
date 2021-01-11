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

        // check if there is an claim for the requested scope & schema or application is in the scope.
        if (array_key_exists($scope, $scopes) || (!strpos('schema', $scope) !== false || !strpos('application', $scope) !== false)) {
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
