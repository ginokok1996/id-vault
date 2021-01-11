<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ScopeService
{
    private $commonGroundService;
    private $params;
    private $claimService;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params, ClaimService $claimService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->claimService = $claimService;
    }

    private function getScope(string $scope): ?array
    {
        $returnScope = $this->params->get('scopes');
        $scope = explode('.', $scope);
        while ($scope != null) {
            $part = array_shift($scope);
            if (key_exists($part, $returnScope)) {
                $returnScope = $returnScope[$part];
            } else {
                return null;
            }
        }

        return $returnScope;
    }

    private function validateArrayProperty($property, $resource)
    {
        if (key_exists('name', $property)) {
            $data = $resource[$property['name']];
        } else {
            return false;
        }
        if (key_exists('type', $property) && $property['type'] == 'array' && key_exists('subType', $property) && count($data) > 0) {
            $data = $data[0];
            if (key_exists('key', $property) && key_exists($property['key'], $data)) {
                $data = $data[$property['key']];
            } elseif (key_exists('key', $property) && !key_exists($property['key'], $data)) {
                return false;
            }
        } elseif (key_exists('type', $property) && $property['type'] == 'array' && key_exists('key', $property) && key_exists($property['key'], $data)) {
            $data = $data[$property['key']];
        } elseif (key_exists('key', $property) && !key_exists($property['key'], $data)) {
            return false;
        }

        return $data;
    }

    private function validateResource($scope, $resource)
    {
        if (is_array($scope['location']['property'])) {
            $returned = $this->validateArrayProperty($scope['location']['property'], $resource);
            if ($returned) {
                return true;
            } elseif (key_exists('source', $scope)) {
                return $scope['source'];
            } else {
                return false;
            }
        } elseif (key_exists($scope['location']['property'], $resource) && $resource[$scope['location']['property']]) {
            return true;
        } elseif (key_exists('source', $scope)) {
            return $scope['source'];
        } else {
            return false;
        }
    }

    public function checkScopes(array $scopes, $user)
    {
        $person = $this->commonGroundService->getResource($user['person']);
        $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
        $deficiencies = [];

        foreach ($scopes as $scope) {
            $result = $this->claimService->checkUserScope($personUrl, $scope);

            if ($result !== true) {
                $deficiency['scope'] = $scope;

                if ($result !== false) {
                    $deficiency['source'] = $result;
                }
                $deficiencies[] = $deficiency;
                unset($deficiency);
            }
        }

        return $deficiencies;
    }
}
