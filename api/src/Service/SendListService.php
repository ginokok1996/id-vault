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

    public function createList(SendList $sendListDTO)
    {
        $results = [];

        // Get info from the DTO SendList
        $newSendList['name'] = $sendListDTO->getName();
        $newSendList['description'] = $sendListDTO->getDescription();
        $newSendList['mail'] = $sendListDTO->getMail();
        $newSendList['phone'] = $sendListDTO->getPhone();

        // Get organization for this new SendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendListDTO->getClientSecret()])['hydra:member'];
        if (count($applications) < 1) {
            array_push($results, 'No applications found with this client secret');
            array_push($results, $sendListDTO->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    $newSendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    array_push($results, 'No organization found in this application contact');
                    array_push($results, $applicationContact);
                }
            } else {
                array_push($results, 'No contact found in this application');
                array_push($results, $application);
            }

            // Create a new sendList in BS
            array_push($results, $this->commonGroundService->createResource($newSendList, ['component' => 'bs', 'type' => 'send_lists']));
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function addUserToList(SendList $sendListDTO, $userAuthorization)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getResource());

        // Get user
        $user = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $userAuthorization]);
        if (isset($user['person'])) {
            // Check if this user already has a subscriber object in BS
            $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['person' => $user['person']])['hydra:member'];
            if (count($subscribers) > 0) {
                // Set subscriber to the existing subscriber to update later
                $subscriber = $subscribers[0];

                // Add sendList to this subscriber
                $subscriberSendLists = [];
                foreach ($subscriber['sendLists'] as $subscriberSendList) {
                    if ($subscriberSendList['id'] != $sendList['id']) {
                        array_push($subscriberSendLists, '/send_lists/'.$sendList['id']);
                    }
                }

                $subscriber['sendLists'] = $subscriberSendLists;
                $subscriber['sendLists'][] = '/send_lists/'.$sendList['id'];
            } else {
                // Set person to create a new subscriber
                $subscriber['person'] = $user['person'];

                // Get sendList from the DTO
                $subscriber['sendLists'][] = '/send_lists/'.$sendList['id'];
            }

            // Update or create a subscriber in BS
            array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers']));
        } else {
            array_push($results, 'This user has no person');
            array_push($results, $user);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }
}
