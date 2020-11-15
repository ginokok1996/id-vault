<?php

// App\Service\NotificationService.php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

class NotificationService
{
    /**
     * @var Security
     */
    private $security;

    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params, Security $security)
    {
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->security = $security;
    }

    /*
     * Validates a resource with specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource after enrichment
     */
    public function checkAuthorizationScopes(array $claim)
    {
        $claim = $this->commonGroundService->getResource($claim['@id']);
        $requiredScope = $this->getRequiredScope($claim['property']);
//        $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'])['hydra:member'];
        if (isset($claim['authorizations']) && !empty($claim['authorizations'])) {
            foreach ($claim['authorizations'] as $auth) {
                if (isset($auth['application']['scopes']) && !empty($auth['application']['scopes'])) {
                    if (in_array($requiredScope, $auth['application']['scopes']) && in_array('notification', $auth['application']['scopes'])) {
                        if (isset($auth['application']['notificationEndpoint']) && !empty($auth['application']['notificationEndpoint'])) {
                            $this->sendNotification($auth['application']['notificationEndpoint'], $claim, );
                        }
                    }
                }
            }
        }
        // Check if this Claim has a token and Authorizations
//        if (key_exists('token', $claim) && !empty($claim['token'])
//            && key_exists('authorizations', $claim) && !empty($claim['authorizations'])) {
//            foreach ($claim['authorizations'] as $auth) {
//                // If so check for each Authorization if it has the notification and the correct scopes
//                if (key_exists('scopes', $auth) && !empty($auth['scopes'])) {
//                    // Check if the authorization has the required scope for this claim.property
//                    if (in_array('notification', $auth['scopes']) && in_array($requiredScope, $auth['scopes'])) {
//                        // If so notify the Organization of the updated Claim
//
//
//                    }
//                }
//            }
//        }

        exit;

        return $claim;
    }

    public function getRequiredScope($type)
    {
//        var_dump('scope wordt gecheckt');
        switch ($type) {
            case 'Email':
                $requiredScope = 'claim.email';
                break;
            case 'email adresses':
                $requiredScope = 'claim.email';
                break;
            case 'Telefoonnummer':
                $requiredScope = 'claim.phonenumber';
                break;
            case 'Naam':
                $requiredScope = 'claim.name';
                break;
        }

        return $requiredScope;
    }

    public function sendNotification($endpoint, $claim)
    {
        $user = $this->security->getUser();

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $endpoint,
            // You can set any number of default request options.
            'timeout' => 2.0,
        ]);

        $response = $client->request('POST', $endpoint, [
            'message' => 'The claim '.$claim['name'].' on ID-Vault has been edited by '.$user->getUsername(),
        ]);
//        var_dump('post is gestuurd');
        exit;
    }

//    public function setForwardUrl(array $resource)
//    {
//        if ($this->params->get('app_env') != 'prod') {
//            $resource['forwardUrl'] = 'https://dev.'.$this->params->get('app_domain').'/irc/assents/'.$resource['id'];
//        } else {
//            $resource['forwardUrl'] = 'https://'.$this->params->get('app_domain').'/irc/assents/'.$resource['id'];
//        }
//
//        $resource = $this->commonGroundService->saveResource($resource, ['component'=>'irc', 'type'=>'assents']);
//
//        return $resource;
//    }
}
