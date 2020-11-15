<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class ClaimService
{
    private $commonGroundService;


    public function checkUserScope($user, $scope)
    {
        $scopes = $this->getUserScopes($user);

        if(array_key_exists($scope, $scopes)){
            return true;
        }
        return false;
    }

    public function getUserScopes($user, CommonGroundService $commonGroundService)
    {
        $claims = $this->commonGroundService->getResourceList(['component'=>'wac', 'type'=>'cliams'], ['user'=>$user]);

        $results = [];
        foreach ($claims as $claim){
            $results[$claim['property']] = $claim;
        }
        
        return $claims;
    }

}
