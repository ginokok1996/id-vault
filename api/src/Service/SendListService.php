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

    public function saveList(SendList $sendListDTO)
    {
        $results = [];

        // if sendList is set we are going to update a existing BS/sendlist
        if ($sendListDTO->getSendList()) {
            $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);

            $subscribers = [];
            foreach ($sendList['subscribers'] as $subscriber) {
                array_push($subscribers, '/subscribers/'.$subscriber['id']);
            }
            $sendList['subscribers'] = $subscribers;
        } elseif (empty($sendListDTO->getName())) {
            throw new  Exception('No name given!');
        }

        // Get info from the DTO SendList
        if ($sendListDTO->getName()) {
            $sendList['name'] = $sendListDTO->getName();
        }
        if ($sendListDTO->getDescription()) {
            $sendList['description'] = $sendListDTO->getDescription();
        }
        if ($sendListDTO->getMail() == true || $sendListDTO->getPhone() == true) {
            $sendList['mail'] = $sendListDTO->getMail();
            $sendList['phone'] = $sendListDTO->getPhone();
        }
        if ($sendListDTO->getResource()) {
            $sendList['resource'] = $sendListDTO->getResource();
        }

        // Get organization for this SendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendListDTO->getClientSecret()])['hydra:member'];
        if (count($applications) < 1) {
            throw new  Exception('No applications found with this client secret! '.$sendListDTO->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    $sendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    throw new  Exception('No organization found in this application contact! '.$applicationContact['id']);
                }
            } else {
                throw new  Exception('No contact found in this application! '.$application['id']);
            }

            // Create a new sendList in BS or update an existing one
            array_push($results, $this->commonGroundService->saveResource($sendList, ['component' => 'bs', 'type' => 'send_lists']));
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function deleteList(SendList $sendListDTO)
    {
        $results = [];

        // get the sendlist
        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);

        // loop through all subscribers and remove the sendlist from them
        foreach ($sendList['subscribers'] as $subscriber) {
            // remove sendList from this subscriber
            $subscriberSendLists = [];
            foreach ($subscriber['sendLists'] as $subscriberSendList) {
                if ($subscriberSendList != '/send_lists/'.$sendList['id']) {
                    array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
                }
            }
            $subscriber['sendLists'] = $subscriberSendLists;

            // save the subscriber
            array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers'])['@id']);
        }

        // delete the sendlist
        array_push($results, $this->commonGroundService->deleteResource($sendList));

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

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

        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);
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

        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);
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
