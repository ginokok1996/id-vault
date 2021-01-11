<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\SendList;
use App\Service\SendListService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// TODO: Make a service for this subscriber?
class SendListSubscriber implements EventSubscriberInterface
{
    private $sendListService;
    private $commonGroundService;

    public function __construct(SendListService $sendListService, CommongroundService $commonGroundService)
    {
        $this->sendListService = $sendListService;
        $this->commonGroundService = $commonGroundService;
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

        if ($resource instanceof SendList) {
            if ($method == 'POST' && $route == 'api_send_lists_post_collection') {
                if ($this->commonGroundService->isResource($resource->getResource())) {
                    $sendList = $this->commonGroundService->getResource($resource->getResource(), [], false); // don't cashe here
                }
                switch ($resource->getAction()) {
                    case 'createList':
                        if (!empty($resource->getName())) {
                            $this->sendListService->createList($resource);
                        }
                        break;
                    case 'addUserToList':
                        if (isset($sendList) and $sendList['@type'] == 'SendList' and $event->getRequest()->headers->get('user-authorization')) {
                            $this->sendListService->addUserToList($resource, $event->getRequest()->headers->get('user-authorization'));
                        }
                        break;
                    case 'sendToList':
                        if (isset($sendList) and $sendList['@type'] == 'SendList' and !empty($resource->getTitle() and !empty($resource->getHtml()))) {
                            $this->sendListService->sendToList($resource);
                        }
                        break;
                    case 'getLists':
                        $this->sendListService->getLists($resource);
                        break;
                    default: return;
                }
            } else {
                return;
            }
        }
    }
}
