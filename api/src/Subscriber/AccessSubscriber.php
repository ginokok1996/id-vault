<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AccessToken;
use App\Service\AccessTokenGeneratorService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AccessSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $accessTokenGeneratorService;

    public function __construct(CommongroundService $commonGroundService, AccessTokenGeneratorService $accessTokenGeneratorService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->accessTokenGeneratorService = $accessTokenGeneratorService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['grantAccess', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function grantAccess(ViewEvent $event)
    {
        $token = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        if ($method != 'POST') {
            return;
        }
        if ($token instanceof AccessToken) {
            $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $token->getClientSecret()])['hydra:member'];
            $authorizationLog = [];
            if (count($applications) < 1) {
                //@todo error
            } else {
                $application = $applications[0];

                $authorization = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $token->getCode()]);

                if ($authorization['newUser'] == null || empty($authorization['newUser'])) {
                    $authorization['newUser'] = false;
                }

                $token->setAccessToken($this->accessTokenGeneratorService->generateAccessToken($authorization, $application));
                $token->setTokenType('bearer');
                $token->setExpiresIn('3600');
                $token->setNewUser($authorization['newUser']);
                $token->setScope(implode('+', $authorization['scopes']));
                $authorizationLog['status'] = '200';
                $goal = $token->getGoal();
                if (isset($goal)) {
                    $authorizationLog['goal'] = $goal;
                }
                $authorizationLog['authorization'] = '/authorizations/'.$authorization['id'];

                $authorization['code'] = null;
                $authorization['application'] = '/applications/'.$authorization['application']['id'];

                if ($authorization['newUser']) {
                    $authorization['newUser'] = false;
                }

                $authorization = $this->commonGroundService->saveResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);
            }

            $authorizationLog['endpoint'] = 'access_tokens';
            $this->commonGroundService->createResource($authorizationLog, ['component' => 'wac', 'type' => 'authorization_logs']);

            $alert = [];
            $alert['name'] = 'Information requested';
            $alert['description'] = 'Information requested by '.$authorization['application']['name'];
            $alert['link'] = $authorization['userUrl'];
            $alert['icon'] = 'fas fa-bell';
            $alert['type'] = 'info';

            $this->commonGroundService->createResource($alert, ['component' => 'uc', 'type' => 'alerts']);
        }

        return $token;
    }
}
