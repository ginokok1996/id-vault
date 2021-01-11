<?php

namespace App\Service;

use App\Entity\SendList;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendListService
{
    private $commonGroundService;
    private $params;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
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
        $newSendList['resource'] = $sendListDTO->getResource();

        // Get organization for this new SendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $sendListDTO->getClientSecret()])['hydra:member'];
        if (count($applications) < 1) {
            array_push($results, 'No applications found with this client secret!');
            array_push($results, $sendListDTO->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    $newSendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    array_push($results, 'No organization found in this application contact!');
                    array_push($results, $applicationContact);
                }
            } else {
                array_push($results, 'No contact found in this application!');
                array_push($results, $application);
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
            if (count($applications) < 1) {
                array_push($results, 'No applications found with this client secret!');
                array_push($results, $sendListDTO->getClientSecret());
            } else {
                $application = $applications[0];
                if (isset($application['contact'])) {
                    $applicationContact = $this->commonGroundService->getResource($application['contact']);
                    if (isset($applicationContact['organization']['id'])) {
                        $organization = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                    } else {
                        array_push($results, 'No organization found in this application contact!');
                        array_push($results, $applicationContact);
                    }
                } else {
                    array_push($results, 'No contact found in this application!');
                    array_push($results, $application);
                }

                // Get all SendLists with this organization
                // If resource is set also filter with that
                if ($sendListDTO->getResource()) {
                    $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'], ['organization' => $organization, 'resource' => $sendListDTO->getResource()])['hydra:member'];
                } else {
                    $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'], ['organization' => $organization])['hydra:member'];
                }
            }
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'send_lists'])['hydra:member'];
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
                        array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
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
            array_push($results, 'This user has no person!');
            array_push($results, $user);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function sendToList(SendList $sendListDTO)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getResource());
        if (!empty($sendList['subscribers'])) {

            // Get data from the DTO object to be able to use these in email content (html)
            $data['title'] = $sendListDTO->getTitle();
            $data['message'] = $sendListDTO->getMessage();
            $data['text'] = $sendListDTO->getText();

            // Create a wrc template to use for email content
            $template['name'] = $data['title'];
            $template['title'] = $data['title'];
            if ($data['message']) {
                $template['description'] = $data['message'];
            }
            $template['content'] = $sendListDTO->getHtml();
            $template['templateEngine'] = 'twig';
            // TODO: connect this template to the organization of the sendList
            // TODO: connect this template to the E-mails template group
            $template = $this->commonGroundService->createResource($template, ['component'=>'wrc', 'type'=>'templates']);
            $content = $this->commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'templates', 'id' => $template['id']]);

            foreach ($sendList['subscribers'] as $subscriber) {
                // Loading the message
                $message = $this->createMessage($data, $sendList, $content, $subscriber['person']);

                // Send email to this subscriber
                array_push($results, $this->commonGroundService->createResource($message, ['component'=>'bs', 'type'=>'messages'])['@id']);
            }
        } else {
            array_push($results, 'This sendList has no subscribers!');
            array_push($results, $sendList);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    //TODO: Dit moet via de mailservice
//    public function createMessage(array $data, array $sendList, $content, $receiver, $attachments = null)
//    {
//        $application = $this->commonGroundService->getResource(['component'=>'wrc', 'type'=>'applications', 'id'=>"{$this->params->get('app_id')}"]);
//        if (key_exists('@id', $application['organization'])) {
//            $serviceOrganization = $application['organization']['@id'];
//        } else {
//            $serviceOrganization = $sendList['organization'];
//        }
//
//        $message = [];
//
//        // Tijdelijke oplossing voor juiste $message['service'] meegeven, was eerst dit hier onder, waar in de query op de organization check het mis gaat:
//        //$message['service'] = $this->commonGroundService->getResourceList(['component'=>'bs', 'type'=>'services'], "type=mailer&organization=$serviceOrganization")['hydra:member'][0]['@id'];
//
//        $message['service'] = '/services/1541d15b-7de3-4a1a-a437-80079e4a14e0';
//        $message['status'] = 'queued';
//
//        $organization = $this->commonGroundService->getResource($sendList['organization']);
//        // lets use the organization as sender
//        if ($organization['contact']) {
//            $message['sender'] = $organization['contact'];
//        }
//
//        // if we don't have that we are going to self send te message
//        $message['reciever'] = $receiver;
//        if (!key_exists('sender', $message)) {
//            $message['sender'] = $receiver;
//        }
//
//        $message['data'] = ['resource'=>$sendList, 'sender'=>$organization, 'receiver'=>$this->commonGroundService->getResource($message['reciever'])];
//        $message['data'] = array_merge($message['data'], $data);  // lets accept contextual data from de bl
//        $message['content'] = $content;
//        if ($attachments) {
//            $message['attachments'] = $attachments;
//        }
//
//        return $message;
//    }
}
