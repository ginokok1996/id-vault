<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\Serializer\CompactSerializer;

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

        $array['iss'] = $application['@id'];
        $array['aud'] = $application['authorizationUrl'];
        $array['exp'] = '3600';
        $array['jti'] = $authorization['id'];
        $array['alg'] = 'HS256';
        $array['iat'] = strtotime('now');

        $algorithmManager = new AlgorithmManager([
            new RS512(),
        ]);

        $jwk = JWKFactory::createFromKeyFile(
            "../cert/cert.pem"
        );

        $jwsBuilder = new \Jose\Component\Signature\JWSBuilder($algorithmManager);
        $payload = json_encode($array);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => 'RS512'])
            ->build();
        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }
}
