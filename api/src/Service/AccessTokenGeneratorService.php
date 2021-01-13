<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class AccessTokenGeneratorService
{
    private $commonGroundService;
    private $claimService;

    public function __construct(CommonGroundService $commonGroundService, ClaimService $claimService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->claimService = $claimService;
    }

    public function generateAccessToken($authorization, $application)
    {
        $user = $this->commonGroundService->getResource($authorization['userUrl']);
        $person = $this->commonGroundService->getResource($user['person']);
        $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

        $claims = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['person' => $personUrl])['hydra:member'];

        $array = [];

        $array['sub'] = $application['id'];
        $array['name'] = $person['name'];
        foreach ($authorization['scopes'] as $scope) {
            switch ($scope) {
                case 'schema.person.email':
                    $array['email'] = $user['username'];
                    break;
                case 'schema.person.given_name':
                    $array['given_name'] = $person['givenName'];
                    break;
                case 'schema.person.family_name':
                    $array['family_name'] = $person['familyName'];
                    break;
            }
        }

        foreach ($authorization['scopes'] as $scope) {
            if ($this->claimService->checkUserScope($personUrl, $scope)) {
                foreach ($claims as $claim) {
                    if ($scope == $claim['property']) {
                        $array['claims'][$claim['property']][] = $claim['data'];
                    }
                }
            }
        }

        $array['groups'] = [];
        $array['organizations'] = [];
        if (count($application['userGroups']) > 0) {
            foreach ($application['userGroups'] as $group) {
                if (count($group['memberships']) > 0) {
                    foreach ($group['memberships'] as $membership) {
                        if ($membership['userUrl'] == $authorization['userUrl'] && !empty($membership['dateAcceptedUser']) || !empty($membership['dateAcceptedGroup'])) {
                            $result = [];
                            $result['id'] = $group['id'];
                            $result['name'] = $group['name'];
                            if (isset($membership['dateAcceptedGroup'])) {
                                $result['dateJoined'] = $membership['dateAcceptedGroup'];
                            } elseif (isset($membership['dateAcceptedUser'])) {
                                $result['dateJoined'] = $membership['dateAcceptedUser'];
                            }

                            if (isset($group['organization'])) {
                                $array['organizations'][] = $group['organization'];
                                $result['organization'] = $group['organization'];
                            }
                            $array['groups'][] = $result;
                        }
                    }
                }
            }
        }

        $array['iss'] = $application['id'];
        $array['aud'] = $application['authorizationUrl'];
        $array['exp'] = '3600';
        $array['jti'] = $authorization['id'];
        $array['alg'] = 'HS256';
        $array['iat'] = strtotime('now');

        // Create token header as a JSON string
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Create token payload as a JSON string
        $payload = json_encode($array);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader.'.'.$base64UrlPayload, 'abC123!', true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader.'.'.$base64UrlPayload.'.'.$base64UrlSignature;
    }
}
