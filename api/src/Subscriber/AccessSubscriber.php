<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AccessToken;
use App\Service\AccessTokenGeneratorService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;


class AccessSubscriber implements EventSubscriberInterface
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
            if (count($applications) < 1) {
                echo $token->getClientSecret();
                echo $token->getCode();
                echo $token->getClientId();
                echo 'error';
                die;
                //@todo error
            } else {
                $application = $applications[0];

                $authorization = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $token->getCode()]);
                $authorization['code'] = null;
                $authorization['application'] = '/applications/'.$application['id'];
                $authorization = $this->commonGroundService->updateResource($authorization);

                $token->setAccessToken($this->accessTokenGeneratorService->generateAccessToken($authorization, $application));
                $token->setTokenType('bearer');
                $token->setExpiresIn('3600');
                $token->setScope(implode("+",$authorization['scopes']));
            }
        }

        return $token;
    }
}
