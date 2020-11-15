<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AccessTokenGeneratorService
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

    public function generateAccessToken($authorization, $application)
    {
        $user = $this->commonGroundService->getResource($authorization['userUrl']);
        $person = $this->commonGroundService->getResource($user['person']);

        $array = [];

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
                case 'person.birthPlace':
                    if (isset($person['birthplace'])) {
                        $array['birth_place'] = $person['birthplace'];
                    }
                    break;
                case 'schema.person.telephone':
                    if (isset($person['telephones'][0]['telephone'])) {
                        $array['telephone'] = $person['telephones'][0]['telephone'];
                    }
                    break;
                case 'schema.person.birthday':
                    if (isset($person['birthday'])) {
                        $array['birthday'] = $person['birthday'];
                    }
                    break;
                case'schema.person.taxID':
                    if (isset($person['taxID'])) {
                        $array['bsn'] = $person['taxID'];
                    }
            }
        }

        $array['sub'] = $application['id'];
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
