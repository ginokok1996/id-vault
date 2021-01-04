<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class OauthService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * This function handles the redirect url logic
     *
     * @param $url string the redirect url provided in query
     * @param $application object application object from wac
     *
     * @return string return the redirect url
     */
    public function createRedirectUrl($url, $application) {

        // Als localhost dan prima -> dit us wel unsafe want ondersteund ook subdomein of path localhost
        if ($url && strpos($url, 'localhost')) {
            // $redirectUrl is al oke dus we hoeven niks te doen
        } elseif ($url && str_replace('http://', 'https://', $url) != str_replace('http://', 'https://', $application['authorizationUrl'])) {
            // $redirectUrl
        } else {
            $url = $application['authorizationUrl'];
        }

        return $url;
    }

    /**
     * This function handles the creation of the authorization
     *
     * @param $user object user object from uc
     * @param $application object application object from wac
     * @param $scopes array the scopes requested for this authorization
     *
     * @return object|false returns authorization object or false if failed to create
     */
    public function createAuthorization($application, $user, $scopes) {
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

        $authorization = [];
        $authorization['application'] = '/applications/'.$application['id'];
        $authorization['scopes'] = $scopes;
        $authorization['goal'] = ' ';
        $authorization['userUrl'] = $userUrl;

        return $this->commonGroundService->createResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);
    }

    /**
     * This function handles the update of the authorization
     *
     * @param $id string id of the authorization
     * @param $scopes array the scopes requested for this authorization
     *
     * @return object|false returns authorization object or false if failed to create
     */
    public function updateAuthorization($id, $scopes) {
        $authorization = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $id]);
        $authorization['application'] = '/applications/'.$authorization['application']['id'];

        foreach ($authorization['authorizationLogs'] as &$log) {
            $log = ['/authorization_logs/'.$log['id']];
        }

        foreach ($scopes as $scope) {
            $authorization['scopes'][] = $scope;
        }

        return $this->commonGroundService->saveResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);
    }

    /**
     * This function handles the comparison between requested and existing scopes
     *
     * @param $userUrl string clean url of user from uc
     * @param $application object application object from wac
     * @param $scopes array array of requested scopes
     *
     * @return object|false returns object or false if failed to create
     */
    public function compareExistingScopes($userUrl, $application, $scopes) {
        $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $userUrl, 'application' => '/applications/'.$application['id']])['hydra:member'];
        if (count($authorizations) > 0) {
            $authorization = $authorizations[0];
            $object['authorizationNeeded'] = false;
            $object['newScopes'] = [];
            $object['id'] = $authorization['id'];
            foreach ($scopes as $scope) {
                if (!in_array($scope, $authorization['scopes'])) {
                    $object['newScopes'][] = $scope;
                    $object['authorizationNeeded'] = true;
                }
            }
            return $object;
        } else {
            return false;
        }
    }
}
