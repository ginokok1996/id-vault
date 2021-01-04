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
        return $claim;
    }

    public function getRequiredScope($type)
    {
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
        exit;
    }
}
