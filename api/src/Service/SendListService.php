<?php

namespace App\Service;

use App\Entity\SendList;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultBundle\Service\IdVaultService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendListService
{
    private $commonGroundService;
    private $params;
    private $idVaultService;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params, IdVaultService $idVaultService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->idVaultService = $idVaultService;
    }

    public function createList(SendList $sendListDTO)
    {
        $results = [];

        // Get info from the DTO SendList
        $newSendList['name'] = $sendListDTO->getName();
        $newSendList['description'] = $sendListDTO->getDescription();
        $newSendList['mail'] = $sendListDTO->getMail();
        $newSendList['phone'] = $sendListDTO->getPhone();
        $newSendList['resource'] = $sendListDTO->getResource();

        // Get organization for this new SendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendListDTO->getClientSecret()])['hydra:member'];
        if (count($applications) < 1) {
            throw new  Exception('No applications found with this client secret! '.$sendListDTO->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    $newSendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    throw new  Exception('No organization found in this application contact! '.$applicationContact['id']);
                }
            } else {
                throw new  Exception('No contact found in this application! '.$application['id']);
            }

            // Create a new sendList in BS
            array_push($results, $this->commonGroundService->createResource($newSendList, ['component' => 'bs', 'type' => 'send_lists']));
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    // TODO:updateList/saveList + deleteList

    public function getLists(SendList $sendListDTO)
    {
        $results = [];

        // Get organization to filter with, if given.
        if ($sendListDTO->getClientSecret()) {
            $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendListDTO->getClientSecret()])['hydra:member'];
            if (count($applications) >= 1) {
                $application = $applications[0];
                if (isset($application['contact'])) {
                    $applicationContact = $this->commonGroundService->getResource($application['contact']);
                    if (isset($applicationContact['organization']['id'])) {
                        $organization = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                    } else {
                        throw new  Exception('No organization found in this application contact! '.$applicationContact['id']);
                    }
                } else {
                    throw new  Exception('No contact found in this application! '.$application['id']);
                }

                // Get all SendLists with this organization
                // If resource is set also filter with that
                if ($sendListDTO->getResource()) {
                    $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'], ['organization' => $organization, 'resource' => $sendListDTO->getResource(), 'order[dateCreated]' => 'desc'])['hydra:member'];
                } else {
                    $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'], ['organization' => $organization, 'order[dateCreated]' => 'desc'])['hydra:member'];
                }
            }
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'], ['order[dateCreated]' => 'desc'])['hydra:member'];
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function addSubscribersToList(SendList $sendListDTO)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getResource());
        $emails = $sendListDTO->getEmails();

        foreach ($emails as $email) {
            // Check if this email has already a subscriber object in BS
            $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['email' => $email])['hydra:member'];
            if (count($subscribers) > 0) {
                // Set subscriber to the existing subscriber to update later
                $subscriber = $subscribers[0];

                // Add sendList to this subscriber
                $subscriberSendLists = [];
                foreach ($subscriber['sendLists'] as $subscriberSendList) {
                    if ($subscriberSendList['id'] != $sendList['id']) {
                        array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
                    }
                }

                $subscriber['sendLists'] = $subscriberSendLists;
                $subscriber['sendLists'][] = '/send_lists/'.$sendList['id'];
            } else {
                // Set email to create a new subscriber
                $subscriber['email'] = $email;

                // Get sendList from the DTO
                $subscriber['sendLists'][] = '/send_lists/'.$sendList['id'];
            }

            // Update or create a subscriber in BS
            array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers'])['@id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function sendToList(SendList $sendListDTO)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getResource());
        if (!empty($sendList['subscribers'])) {
            $body = $sendListDTO->getHtml();
            $subject = $sendListDTO->getTitle();
            $sender = $sendListDTO->getSender();

            // Loop through all subscribers
            foreach ($sendList['subscribers'] as $subscriber) {
                array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $subscriber['email'], $sender)['@id']);
            }
        } else {
            throw new  Exception('This sendList has no subscribers! '.$sendList['id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }
}
