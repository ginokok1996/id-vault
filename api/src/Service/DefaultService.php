<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class DefaultService
{
    private $commonGroundService;
    private $params;
    private $flash;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params, FlashBagInterface $flash)
    {
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->flash = $flash;
    }

    public function getApplication()
    {
        return $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);
    }

    public function getProvider($type)
    {
        $application = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);
        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => $type, 'application' => $application])['hydra:member'];
        if (count($providers) > 0) {
            return $providers[0];
        } else {
            return false;
        }
    }

    public function singleSignOn($authorizations)
    {
        foreach ($authorizations as &$authorization) {
            if (isset($authorization['application']['singleSignOnUrl']) && in_array('single_sign_on', $authorization['scopes'])) {
                $application = $this->commonGroundService->isResource($authorization['application']['contact']);
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $authorization['backgroundColor'] = $matches;
                }

                $authorization['singleSignOnUrl'] = $authorization['application']['singleSignOnUrl']."?code={$authorization['id']}";
            }
        }

        return $authorizations;
    }

    public function getUserUrl($username)
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];

        return $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
    }

    public function throwFlash($type, $message)
    {
        $this->flash->add($type, $message);
    }

    public function calculateMargin($cost, $balance)
    {
        return (1 - $cost / $balance) * 100;
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

    public function createToken(string $id, string $userId, array $provider)
    {
        $now = New \DateTime('now');
        $token = [];
        $token['provider'] = '/providers/'.$provider['id'];
        $token['user'] = '/users/'.$userId;
        $token['token'] = $id;
        $token['dateAccepted'] = $now->format('Y-m-d');
        $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);
    }

    public function authGoogle(string $code, string $username) {
        $provider = $this->getProvider('gmail');
        $client = $this->createClient('https://oauth2.googleapis.com');

        $body = [
            'client_id'         => $provider['configuration']['app_id'],
            'client_secret'     => $provider['configuration']['secret'],
            'redirect_uri'      => 'http://id-vault.com/dashboard/security/google',
            'code'              => $code,
            'grant_type'        => 'authorization_code',
        ];

        $response = $client->request('POST', '/token', [
            'form_params'  => $body,
            'content_type' => 'application/x-www-form-urlencoded',
        ]);
        $accessToken = json_decode($response->getBody()->getContents(), true);
        $json = json_decode(base64_decode(explode('.', $accessToken['id_token'])[1]), true);
        $userUrl = $this->getUserUrl($username);
        $user = $this->commonGroundService->getResource($userUrl);

        if ($json['email'] != $username) {
            return false;
        }

        $this->createToken($json['sub'], $user['id'], $provider);

        return true;
    }

    public function authFacebook(string $code, string $username) {
        $provider = $this->getProvider('facebook');
        $client = $this->createClient('https://graph.facebook.com');

        $response = $client->request('GET', '/v8.0/oauth/access_token?client_id='.str_replace('"', '', $provider['configuration']['app_id']).'&redirect_uri=https://id-vault.com/dashboard/security/facebook&client_secret='.$provider['configuration']['secret'].'&code='.$code);
        $accessToken = json_decode($response->getBody()->getContents(), true);

        $response = $client->request('GET', '/me?&fields=id,name,email&access_token='.$accessToken['access_token']);
        $json = json_decode($response->getBody()->getContents(), true);
        $userUrl = $this->getUserUrl($username);
        $user = $this->commonGroundService->getResource($userUrl);

        if ($json['email'] != $username) {
            return false;
        }

        $this->createToken($json['id'], $user['id'], $provider);

        return true;
    }

    public function authGithub(string $code, string $username) {
        $provider = $this->getProvider('github auth');
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
        $userUrl = $this->getUserUrl($username);
        $user = $this->commonGroundService->getResource($userUrl);

        if ($json['email'] != $username) {
            return false;
        }

        $this->createToken($json['id'], $user['id'], $provider);

        return true;
    }

    public function authLinkedIn(string $code, string $username) {
        $provider = $this->getProvider('linkedIn');
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

        $headers = [
            'Authorization' => 'Bearer '.$accessToken['access_token'],
            'Accept'        => 'application/json',
        ];

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://api.linkedin.com',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $response = $client->request('GET', '/v2/me', [
            'headers' => $headers,
        ]);

        $json = json_decode($response->getBody()->getContents(), true);

        $userUrl = $this->getUserUrl($username);
        $user = $this->commonGroundService->getResource($userUrl);

        if ($email != $username) {
            return false;
        }

        $this->createToken($json['id'], $user['id'], $provider);

        return true;
    }
}
