<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Dossier;
use App\Service\AccessTokenGeneratorService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class DossierSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['createDossier', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function createDossier(ViewEvent $event)
    {
        $token = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $authentication = $event->getRequest()->headers->get('authentication');
        $route = $event->getRequest()->attributes->get('_route');

        if ($method != 'POST') {
            return;
        }
        if ($token instanceof Dossier) {

            if ($authentication == 'test') {
                throw new  Exception('Invalid authentication key');
            }

        }

        return $token;
    }
}
