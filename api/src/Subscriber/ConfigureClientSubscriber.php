<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CreateClient;
use App\Service\ClientService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use function PHPUnit\Framework\isNull;

class ConfigureClientSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['configureClient', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function configureClient(ViewEvent $event)
    {
        if ($event->getRequest()->get('_route') == 'api_create_clients_client_configuration_collection') {
            if ($event->getRequest()->getMethod() != 'GET' || $event->getRequest()->headers->has('Authorization') == false) {
                Throw new AccessDeniedException('Authorization header not found');
            }

            if (strpos($event->getRequest()->headers->get('Authorization'), 'Bearer ') !== false) {
                $code = str_replace('Bearer ', '', $event->getRequest()->headers->get('Authorization'));
            } else {
                Throw new AccessDeniedException('Authorization header invalid');
            }

            try {
                $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $code]);
            } catch (\Throwable $e) {
                Throw new NotFoundHttpException('unable to find client');
            }

            $array = json_decode($event->getRequest()->getContent(), true);

            $result = [];
            $result['client_id'] = $application['id'];
            $result['client_secret'] = $application['secret'];
            if (isset($application['webhookUrl'])){
                $result['initiate_login_uri'] = $application['webhookUrl'];
            }
            $result['client_secret_expires_at'] = 0;
            if (!isNull($array)) {
                $result = array_merge($result, $array);
            }

            $json = $this->serializer->serialize(
                $result,
                'json'
            );

            $response = new Response(
                $json,
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );

            $event->setResponse($response);
        }
    }
}
