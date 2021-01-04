<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\GetScopes;
use App\Service\AccessTokenGeneratorService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class ScopeRequestSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $commonGroundService;
    private $accessTokenGeneratorService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, CommongroundService $commonGroundService, AccessTokenGeneratorService $accessTokenGeneratorService)
    {
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->accessTokenGeneratorService = $accessTokenGeneratorService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['scopeRequest', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function scopeRequest(ViewEvent $event)
    {
        $scopeRequest = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        if ($method != 'POST') {
            return;
        }

        if ($scopeRequest instanceof GetScopes) {
            try {
                $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['id' => $scopeRequest->getAuthorization()])['hydra:member'];
            } catch (\Throwable $e) {
                throw new  Exception('Invalid authorization code');
            }

            if (count($authorizations) > 0) {
                $authorization = $authorizations[0];

                $newRequest = [];
                $newRequest['scopes'] = $scopeRequest->getScopes();
                $newRequest['authorization'] = '/authorizations/'.$authorization['id'];

                $newRequest = $this->commonGroundService->createResource($newRequest, ['component' => 'wac', 'type' => 'scope_requests']);

                $scopeRequest->setStatus('request for scopes submitted');

                $alert = [];
                $alert['name'] = 'Extra scopes Requested';
                $alert['description'] = $authorization['application']['name'].' requested additional scopes to be authorized';
                $alert['link'] = $authorization['userUrl'];
                $alert['icon'] = 'fas fa-bell';
                $alert['type'] = 'info';

                $user = $this->commonGroundService->getResource($authorization['userUrl']);

                $person = $this->commonGroundService->getResource($user['person']);
                $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

                $this->commonGroundService->createResource($alert, ['component' => 'uc', 'type' => 'alerts']);
            } else {
                throw new  Exception('Invalid authorization code');
            }
        }

        return $scopeRequest;
    }
}
