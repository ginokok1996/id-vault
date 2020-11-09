<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AccessToken;
use App\Entity\SendList;
use App\Service\AccessTokenGeneratorService;
use App\Service\SendListService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

// TODO: Make a service for this subscriber?
class SendListSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $sendListService;
    private $serializer;
    private $commonGroundService;
    private $accessTokenGeneratorService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SendListService $sendListService, SerializerInterface $serializer, CommongroundService $commonGroundService, AccessTokenGeneratorService $accessTokenGeneratorService)
    {
        $this->params = $params;
        $this->sendListService = $sendListService;
        $this->commonGroundService = $commonGroundService;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->accessTokenGeneratorService = $accessTokenGeneratorService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['sendList', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function sendList(ViewEvent $event)
    {
        $resource = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        // We should also check on entity = component
        if ($method == 'POST' && $route == 'api_send_lists_post_collection') {
            $resource = $this->sendListService->createList($resource);
        } elseif ($method == 'POST') {
            var_dump($route); // TODO: REMOVE VAR DUMP HERE! :)
        } else {
            return;
        }

        if ($resource instanceof SendList) {
            if ($this->commonGroundService->isResource($resource->getResource())) {
                $sendList = $this->commonGroundService->getResource($resource->getResource(), [], false); // don't cashe here
                $resource = $this->sendListService->addUserToList($resource);
            }
        }
    }
}
