<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AccessToken;
use App\Entity\UserInfo;
use App\Service\AccessTokenGeneratorService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class UserInfoSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $accessTokenGeneratorService;
    private $serializer;

    public function __construct(CommongroundService $commonGroundService, AccessTokenGeneratorService $accessTokenGeneratorService, SerializerInterface $serializer)
    {
        $this->commonGroundService = $commonGroundService;
        $this->accessTokenGeneratorService = $accessTokenGeneratorService;
        $this->serializer = $serializer;
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

        if ($event->getRequest()->get('_route') == 'api_user_infos_user_info_collection') {
            if ($method != 'GET' || $event->getRequest()->headers->has('Authorization') == false && $event->getRequest()->get('_route') == 'api_user_infos_user_info_collection') {
                Throw new AccessDeniedException('Authorization header not found');
            }
            $contentType = $event->getRequest()->headers->get('accept');
            if (!$contentType) {
                $contentType = $event->getRequest()->headers->get('Accept');
            }

            if (strpos($event->getRequest()->headers->get('Authorization'), 'Bearer ') !== false) {
                $code = str_replace('Bearer ', '', $event->getRequest()->headers->get('Authorization'));
            } else {
                Throw new AccessDeniedException('Authorization header invalid');
            }
            $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['code' => $code])['hydra:member'];

            if (!count($authorizations) > 0) {
                Throw new AccessDeniedException('Authorization header invalid');
            }
            $authorization = $authorizations[0];

            $authorization['code'] = Uuid::uuid4();
            if ($authorization['newUser'] == null || empty($authorization['newUser'])) {
                $authorization['newUser'] = false;
            }

            $authorization['application'] = '/applications/'.$authorization['application']['id'];

            if ($authorization['newUser']) {
                $authorization['newUser'] = false;
            }

            $authorization = $this->commonGroundService->saveResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);

            switch ($contentType) {
                case 'application/json':
                    $result = $this->accessTokenGeneratorService->generateAccessToken($authorization, $authorization['application'], true);
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
                    break;
                default:
                    $result = $this->accessTokenGeneratorService->generateAccessToken($authorization, $authorization['application']);
                    $json = $this->serializer->serialize(
                        $result,
                        'json'
                    );

                    $response = new Response(
                        $json,
                        Response::HTTP_OK,
                        ['content-type' => 'application/jwt']
                    );
                    $event->setResponse($response);
                    break;
            }

            return $token;
        }

    }
}
