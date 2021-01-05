<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Dossier;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DossierSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;

    public function __construct(CommongroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['createDossier', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function createDossier(ViewEvent $event)
    {
        $dossier = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $authentication = $event->getRequest()->headers->get('authentication');

        if ($method != 'POST') {
            return;
        }
        if ($dossier instanceof Dossier) {
            try {
                $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['id' => $authentication])['hydra:member'];
            } catch (\Throwable $e) {
                throw new  Exception('Invalid authentication header');
            }

            if (!count($authorizations) > 0) {
                throw new  Exception('Invalid authentication header');
            }

            $authorization = $authorizations[0];

            $scopes = $dossier->getScopes();

            foreach ($scopes as $scope) {
                if (!in_array($scope, $authorization['scopes'])) {
                    throw new  Exception('Scope '.$scope.' is not authorized by user');
                }
            }

            $newDossier = [];
            $newDossier['name'] = $dossier->getName();
            $newDossier['description'] = $dossier->getDescription();
            $newDossier['goal'] = $dossier->getGoal();
            $newDossier['scopes'] = $dossier->getScopes();
            $newDossier['expiryDate'] = $dossier->getExpiryDate()->format('h:m Y-m-d');
            $newDossier['sso'] = $dossier->getSso();
            $newDossier['legal'] = (bool) $dossier->getLegal();
            $newDossier['authorization'] = '/authorizations/'.$authorization['id'];

            $this->commonGroundService->saveResource($newDossier, ['component' => 'wac', 'type' => 'dossiers']);

            $alert = [];
            $alert['name'] = 'Dossier created';
            $alert['description'] = 'dossier '.$dossier->getName().' created by '.$authorization['application']['name'];
            $alert['link'] = $authorization['userUrl'];
            $alert['icon'] = 'fas fa-bell';
            $alert['type'] = 'info';

            $this->commonGroundService->createResource($alert, ['component' => 'uc', 'type' => 'alerts']);
        }

        return $dossier;
    }
}
