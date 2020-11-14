<?php

namespace App\Subscriber;

use App\Service\NotificationService;
use Conduction\CommonGroundBundle\Event\CommonGroundEvents;
use Conduction\CommonGroundBundle\Event\CommongroundUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public static function getSubscribedEvents()
    {
        return [
            CommonGroundEvents::SAVE  => 'save',
            //            CommonGroundEvents::CREATE  => 'create',
            //            CommonGroundEvents::CREATED => 'created',
        ];
    }

    public function save(CommongroundUpdateEvent $event)
    {
//        var_dump('subscriber gaat af');

        // Lets make sure that we are dealing with a Claim resource from the WAC
        $url = $event->getUrl();
        if (!$url || !is_array($url) || $url['component'] != 'wac' || $url['type'] != 'claims') {
            return;
        }
//        var_dump('check op claim gaat goed');

        // Lets see if we need to do anything with the resource
        $resource = $event->getResource();
        $resource = $this->notificationService->checkAuthorizationScopes($resource);
        $event->setResource($resource);

        return $event;
    }

//    public function create(CommongroundUpdateEvent $event)
//    {
//        $resource = $event->getResource();
//        $url = $event->getUrl();
//        if (!$url || !is_array($url) || $url['component'] != 'wac' || $url['type'] != 'claims') {
//            return false;
//        }
//
//        $event->setResource($this->notificationService->checkAuthorizationScopes($resource));
//
//        return $event;
//    }
//
//    public function created(CommongroundUpdateEvent $event)
//    {
//        $resource = $event->getResource();
//        if (!array_key_exists('@type', $resource) || $resource['@type'] != 'Claim') {
//            return;
//        }
//
//        $resource = $this->notificationService->setForwardUrl($resource);
//
//        $event->setResource($resource);
//
//        return $event;
//    }
}
