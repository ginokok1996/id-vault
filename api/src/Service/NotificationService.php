<?php

// App\Service\NotificationService.php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
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
        $user = $this->security->getUser();

        $requiredScope = $this->getRequiredScope($claim['property']);

        $authentications = $this->commonGroundService->getResourceList(['component'=>'wac', 'type'=>'authentications'], ['userUrl'=>$user, 'scope[]'=>[$requiredScope, 'notification']])['hydra:member'];
        // Check if this Claim has a token and Authorizations
        if (key_exists('token', $claim) && !empty($claim['token'])
            && key_exists('authorizations', $claim) && !empty($claim['authorizations'])) {
            foreach ($claim['authorizations'] as $auth) {
                // If so check for each Authorization if it has the notification and the correct scopes
                if (key_exists('scopes', $auth) && !empty($auth['scopes'])) {
                    // Check if the authorization has the required scope for this claim.property
                    if (in_array('notification', $auth['scopes']) && in_array($requiredScope, $auth['scopes'])) {
                        // If so notify the Organization of the updated Claim

                        // Create authTokenUrl
                        if ($this->params->get('app_env') != 'prod') {
                            $authTokenUrl = 'https://dev.id-vault.com/oauth/tokeninfo/' . $claim['token'];
                        } else {
                            $authTokenUrl = 'https://id-vault.com/oauth/tokeninfo/' . $claim['token'];
                        }

                        // Create JSON
                        $notification = [];
                        $notification->authorization_token = $authTokenUrl;
                        $notification->scopes = [$requiredScope];

                        $notification = json_encode($notification);

                        // send json
                    }
                }
            }
        }

        return $claim;
    }

    public function getRequiredScope($type)
    {
        switch ($type) {
            case "Email":
                $requiredScope = 'claim.email';
                break;
            case "email adresses":
                $requiredScope = 'claim.email';
                break;
        }

        return $requiredScope;
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
