<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\SendList;
use App\Service\SendListService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
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
                if ($resource->getSendList()) {
                    if ($this->commonGroundService->isResource($resource->getSendList())) {
                        $sendList = $this->commonGroundService->getResource($resource->getSendList(), [], false); // don't cashe here
                        if (!isset($sendList) or $sendList['@type'] != 'SendList') {
                            throw new  Exception('This sendList resource is not of the type SendList! '.$sendListDTO->getSendList());
                        }
                    } else {
                        throw new  Exception('This sendList resource is no commonground resource! '.$sendListDTO->getSendList());
                    }
                }
                switch ($resource->getAction()) {
                    case 'saveList':
                        $this->sendListService->saveList($resource);
                        break;
                    case 'addSubscribersToList':
                        if (empty($resource->getEmails())) {
                            throw new  Exception('No emails given!');
                        }
                        $this->sendListService->addSubscribersToList($resource);
                        break;
                    case 'sendToList':
                        if (empty($resource->getTitle())) {
                            throw new  Exception('No title given!');
                        }
                        if (empty($resource->getHtml())) {
                            throw new  Exception('No html given!');
                        }
                        if (empty($resource->getSender())) {
                            throw new  Exception('No sender given!');
                        }
                        $this->sendListService->sendToList($resource);
                        break;
                    case 'getLists':
                        $this->sendListService->getLists($resource);
                        break;
                    case 'deleteList':
                        $this->sendListService->deleteList($resource);
                        break;
                    default: return;
                }
            } else {
                return;
            }
        }
    }
}
