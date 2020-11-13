<?php

namespace App\Service;

use App\Entity\SendList;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendListService
{
    private $em;
    private $commonGroundService;
    private $params;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
    }

    public function createList(SendList $sendList)
    {
        $results = [];

        // Get info from the DTO SendList
        $newSendList['name'] = $sendList->getName();
        $newSendList['description'] = $sendList->getDescription();
        $newSendList['mail'] = $sendList->getMail();
        $newSendList['phone'] = $sendList->getPhone();

        // Get organization for this new SendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendList->getClientSecret()])['hydra:member'];
        if (count($applications) < 1) {
            array_push($results, 'No applications found with this client secret');
            array_push($results, $sendList->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    $newSendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    array_push($results, 'No organization found in this application contact');
                    array_push($applicationContact);
                }
            } else {
                array_push($results, 'No contact found in this application');
                array_push($application);
            }

            // Create a new sendList in BS
            array_push($results, $this->commonGroundService->createResource($newSendList, ['component' => 'bs', 'type' => 'send_lists']));
        }

        $sendList->setResult($results);

        return $sendList;
    }

    public function addUserToList(SendList $sendList)
    {
        $results = [];

        // Get info from the DTO SendList
        $subscriber['sendLists'] = $sendList->getResource();

        // Update or create a subscriber in BS
        //array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers']));

        // TODO:remove this:
        array_push($results, 'test2');
        array_push($results, $subscriber);

        $sendList->setResult($results);

        return $sendList;
    }
}
