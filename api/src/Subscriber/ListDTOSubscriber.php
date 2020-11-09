<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AccessToken;
use App\Entity\ListDTO;
use App\Service\AccessTokenGeneratorService;
use App\Service\ListDTOService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

// TODO: Make a service for this subscriber?
class ListDTOSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $listDTOService;
    private $serializer;
    private $commonGroundService;
    private $accessTokenGeneratorService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, ListDTOService $listDTOService, SerializerInterface $serializer, CommongroundService $commonGroundService, AccessTokenGeneratorService $accessTokenGeneratorService)
    {
        $this->params = $params;
        $this->listDTOService = $listDTOService;
        $this->commonGroundService = $commonGroundService;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->accessTokenGeneratorService = $accessTokenGeneratorService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['listDTO', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function listDTO(ViewEvent $event)
    {
        $resource = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        // We should also check on entity = component
        if ($method != 'POST') {
            return;
        }

        if ($resource instanceof ListDTO) {
            $resource->getResource();
            $sendList = $this->commonGroundService->getResource($resource->getResource(), [], false); // don't cashe here

            $resource = $this->listDTOService->handle($resource);
        }
        $this->em->persist($resource);
        $this->em->flush();
    }
}
