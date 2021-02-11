<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CreateClient;
use App\Service\ClientService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class CreateClientSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $clientService;
    private $serializer;

    public function __construct(CommongroundService $commonGroundService, ClientService $clientService, SerializerInterface $serializer)
    {
        $this->commonGroundService = $commonGroundService;
        $this->clientService = $clientService;
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['createClient', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function createClient(ViewEvent $event)
    {
        $client = $event->getControllerResult();
        if ($client instanceof CreateClient && $event->getRequest()->getMethod() == 'POST') {
            $result = [];
            $contacts = $client->getContacts();
            $uris = $client->getRedirectUris();
            $organization = $this->clientService->createOrganization($client->getClientName());
            $wrc = $this->clientService->createWrcApplication($client->getClientName(), $uris[0], $organization);
            $application = $this->clientService->createWacApplication($client->getClientName(), $uris[0], $organization, $wrc);

            $result['client_id'] = $application['id'];
            $result['client_secret'] = $application['secret'];
            $result['client_secret_expires_at'] = 0;
            $result = array_merge($result, json_decode($event->getRequest()->getContent(), true));

            $json = $this->serializer->serialize(
                $result,
                'json'
            );

            $response = new Response(
                $json,
                Response::HTTP_CREATED,
                ['content-type' => 'application/json']
            );

            $event->setResponse($response);
        }

        return $client;
    }
}
