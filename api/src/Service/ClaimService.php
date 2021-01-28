<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTimeZone;
use GuzzleHttp\Client;

class ClaimService
{
    private $commonGroundService;
    private $defaultService;

    public function __construct(CommonGroundService $commonGroundService, DefaultService $defaultService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->defaultService = $defaultService;
    }

    /**
     * This function checks if the scope exists for the given person.
     *
     * @param string $person person uri
     * @param string $scope  name of scope
     *
     * @return bool true or false depending if scope exists for user
     */
    public function checkUserScope(string $person, string $scope): bool
    {
        $scopes = $this->getUserScopes($person);

        // check if there is an claim for the requested scope & schema or application is in the scope.
        if (array_key_exists($scope, $scopes) || (!strpos('schema', $scope) !== false || !strpos('application', $scope) !== false)) {
            return true;
        }

        return false;
    }

    /**
     * This function gets the claims associated with the user.
     *
     * @param string $person person uri
     *
     * @return array array of the scopes
     */
    public function getUserScopes(string $person): array
    {
        $claims = $this->commonGroundService->getResourceList(['component'=>'wac', 'type'=>'claims'], ['person'=>$person])['hydra:member'];

        $results = [];
        foreach ($claims as $claim) {
            $results[$claim['property']] = $claim;
        }

        return $results;
    }

    /**
     * This function creates an guzzle client with the provided url.
     *
     * @param string $url url this client needs to send requests to
     *
     * @return Client created client
     */
    public function createClient(string $url): Client
    {
        return $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $url,
            // You can set any number of default request options.
            'timeout'  => 5.0,
        ]);
    }

    public function generateClaim(array $data, string $id, string $type)
    {
        $now = new \DateTime('now', new DateTimeZone('Europe/Amsterdam'));
        $provider = $this->defaultService->getProvider($type);
        $array = [];
        $array['@context'] = ['https://www.w3.org/2018/credentials/v1', 'https://www.w3.org/2018/credentials/examples/v1'];
        $array['id'] = $id;
        $array['type'] = ['VerifiableCredential', $type];
        $array['issuer'] = $provider['id'];
        $array['inssuanceDate'] = $now->format('H:i:s d-m-Y');
        $array['credentialSubject']['id'] = $id;
        foreach ($data as $key => $value) {
            $array['credentialSubject'][$key] = $value;
        }

        return $array;
    }

    public function createWacClaim(array $data, string $person, string $type)
    {
        $claim = [];
        $claim['person'] = $person;
        $claim['property'] = $type;
        $claim['data'] = $this->generateClaim($data, $this->commonGroundService->getResource($person)['id'], $type);
        $this->commonGroundService->createResource($claim, ['component' => 'wac', 'type' => 'claims']);
    }

    public function googleClaim(string $code, string $person): bool
    {
        $provider = $this->defaultService->getProvider('gmail');
        $client = $this->createClient('https://oauth2.googleapis.com');

        $body = [
            'client_id'         => $provider['configuration']['app_id'],
            'client_secret'     => $provider['configuration']['secret'],
            'redirect_uri'      => 'http://id-vault.com/dashboard/claim-your-data/google',
            'code'              => $code,
            'grant_type'        => 'authorization_code',
        ];

        $response = $client->request('POST', '/token', [
            'form_params'  => $body,
            'content_type' => 'application/x-www-form-urlencoded',
        ]);
        $accessToken = json_decode($response->getBody()->getContents(), true);
        $json = json_decode(base64_decode(explode('.', $accessToken['id_token'])[1]), true);

        $this->createWacClaim(['email' => $json['email']], $person, 'gmail');

        return true;
    }

    public function facebookClaim(string $code, string $person): bool
    {
        $provider = $this->defaultService->getProvider('facebook');
        $client = $this->createClient('https://graph.facebook.com');

        $response = $client->request('GET', '/v8.0/oauth/access_token?client_id='.str_replace('"', '', $provider['configuration']['app_id']).'&redirect_uri=https://id-vault.com/dashboard/claim-your-data/facebook&client_secret='.$provider['configuration']['secret'].'&code='.$code);
        $accessToken = json_decode($response->getBody()->getContents(), true);

        $response = $client->request('GET', '/me?&fields=id,name,email&access_token='.$accessToken['access_token']);
        $json = json_decode($response->getBody()->getContents(), true);

        $this->createWacClaim(array('email' => $json['email']), $person, 'facebook');

        return true;
    }

    public function githubClaim(string $code, string $person): bool
    {
        $provider = $this->defaultService->getProvider('github claim');
        $client = $this->createClient('https://github.com');

        $body = [
            'client_id'         => $provider['configuration']['app_id'],
            'client_secret'     => $provider['configuration']['secret'],
            'code'              => $code,
        ];

        $response = $client->request('POST', '/login/oauth/access_token', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params'  => $body,
        ]);

        $token = json_decode($response->getBody()->getContents(), true);

        $headers = [
            'Authorization' => 'token '.$token['access_token'],
            'Accept'        => 'application/json',
        ];

        $client = $this->createClient('https://api.github.com');

        $response = $client->request('GET', '/user', [
            'headers' => $headers,
        ]);

        $json = json_decode($response->getBody()->getContents(), true);

        $this->createWacClaim(array('email' => $json['email']), $person, 'github');

        return true;
    }

    /**
     * This function creates an claim for linkedIn
     *
     * @param string $code code received from linkedIn
     * @param string $person person uri
     * @return bool true when finished
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function linkedinClaim(string $code, string $person): bool
    {
        $provider = $this->defaultService->getProvider('linkedIn');
        $client = $this->createClient('https://www.linkedin.com');

        $body = [
            'client_id'         => $provider['configuration']['app_id'],
            'client_secret'     => $provider['configuration']['secret'],
            'redirect_uri'      => 'http://id-vault.com/dashboard/claim-your-data/linkedIn',
            'code'              => $code,
            'grant_type'        => 'authorization_code',
        ];

        $response = $client->request('POST', '/oauth/v2/accessToken', [
            'form_params'  => $body,
            'content_type' => 'application/x-www-form-urlencoded',
        ]);

        $accessToken = json_decode($response->getBody()->getContents(), true);

        $headers = [
            'Authorization' => 'Bearer '.$accessToken['access_token'],
            'Accept'        => 'application/json',
        ];

        $client = $this->createClient('https://api.linkedin.com');

        $response = $client->request('GET', '/v2/emailAddress?q=members&projection=(elements*(handle~))', [
            'headers' => $headers,
        ]);

        $email = json_decode($response->getBody()->getContents(), true);

        $this->createWacClaim(array('email' => $email['elements'][0]['handle~']['emailAddress']), $person, 'linkedIn');

        return true;
    }
}
