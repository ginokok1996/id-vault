<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ScopeService
{
    private $em;
    private $commonGroundService;
    private $params;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
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
        $organization = $this->commonGroundService->getResource($user['organization']);
        $possibleScopes = $this->params->get('scopes');

        $deficiencies = [];

        foreach ($scopes as $scope) {
            $toValidate = $this->getScope($scope);

            if (!$toValidate ||
                !key_exists('location', $toValidate) ||
                !key_exists('component', $toValidate['location']) ||
                !key_exists('type', $toValidate['location'])
            ) {
                $result = true;
            } elseif ($toValidate['location']['component'] == 'cc' && $toValidate['location']['type'] == 'people') {
                $result = $this->validateResource($toValidate, $person);
            } elseif ($toValidate['location']['component'] == 'uc' && $toValidate['location']['type'] == 'users') {
                $result = $this->validateResource($toValidate, $user);
            } elseif ($toValidate['location']['component'] == 'wrc' && $toValidate['location']['type'] == 'organizations') {
                $result = $this->validateResource($toValidate, $user);
            } else {
                $result = true;
            }

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
