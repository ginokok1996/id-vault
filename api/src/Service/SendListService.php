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

    // TODO: make this cleaner, split into more smaller functions, remove duplicate code and use less if else and for where possible.
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
            $sendList = $this->commonGroundService->saveResource($sendList, ['component' => 'bs', 'type' => 'send_lists']);

            // If everything so far didn't throw any exceptions, create subscribers for the given groups
            if ($sendListDTO->getGroups()) {
                $groupIds = $sendListDTO->getGroups();

                $this->addGroupsToList($groupIds, $sendList['id']);
            }

            // Make sure to get the up to date sendlist with correct subscribers (might be added above here^)
            $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);

            // Now make sure to remove any wac/groups from the sendList if this is needed.
            $subscribers = [];
            foreach ($sendList['subscribers'] as $subscriber) {
                // If this subscriber has a resource that is a wac/group
                if (isset($subscriber['resource']) and strpos($subscriber['resource'], '/wac/groups/')) {
                    $remove = true;
                    // Check if it is still needed to add this to this sendList
                    if ($sendListDTO->getGroups()) {
                        $groupIds = $sendListDTO->getGroups();
                        foreach ($groupIds as $groupId) {
                            if (strpos($subscriber['resource'], $groupId)) {
                                $remove = false;
                            }
                        }
                    }
                    if ($remove) {
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
                }
            }

            array_push($results, $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false));
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

    // TODO: make a removeSubscribersFromList
    public function addSubscribersToList(SendList $sendListDTO)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);

        if ($sendListDTO->getEmails()) {
            $emails = $sendListDTO->getEmails();

            foreach ($emails as $email) {
                // Check if this email has already a subscriber object in BS
                $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['email' => $email])['hydra:member'];
                if (count($subscribers) > 0) {
                    // Set subscriber to the existing subscriber to update later
                    $subscriber = $subscribers[0];

                    // Set sendLists of this subscriber
                    $subscriberSendLists = [];
                    foreach ($subscriber['sendLists'] as $subscriberSendList) {
                        if ($subscriberSendList['id'] != $sendList['id']) {
                            array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
                        }
                    }

                    // Add the the sendList to this subscriber
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
        }

        if ($sendListDTO->getGroups()) {
            $groupIds = $sendListDTO->getGroups();

            $this->addGroupsToList($groupIds, $sendList['id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    public function addGroupsToList(array $groupIds, string $sendListId)
    {
        foreach ($groupIds as $groupId) {
            // Creat Url with group id
            $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'wac', 'type' => 'groups', 'id' => $groupId]);

            // Check if this is an existing resource
            if ($this->commonGroundService->isResource($groupUrl)) {
                // Get the resource
                $group = $this->commonGroundService->getResource($groupUrl, [], false);
                //...and make sure it is a group resource
                if (isset($group) and $group['@type'] == 'Group') {
                    // Check if this group has already a subscriber object in BS
                    $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['resource' => $groupUrl])['hydra:member'];
                    if (count($subscribers) > 0) {
                        // Set subscriber to the existing subscriber to update later
                        $subscriber = $subscribers[0];

                        // Set sendLists of this subscriber
                        $subscriberSendLists = [];
                        foreach ($subscriber['sendLists'] as $subscriberSendList) {
                            if ($subscriberSendList['id'] != $sendListId) {
                                array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
                            }
                        }

                        // Add the the sendList to this subscriber
                        $subscriber['sendLists'] = $subscriberSendLists;
                        $subscriber['sendLists'][] = '/send_lists/'.$sendListId;
                    } else {
                        // Set resource to groupUrl to create a new subscriber
                        // Make sure to create a new subscriber and not use data already in this $subscriber:
                        $subscriber = [];
                        $subscriber['resource'] = $groupUrl;

                        // Add the sendList to it
                        $subscriber['sendLists'][] = '/send_lists/'.$sendListId;
                    }

                    // Update or create a subscriber in BS and add them to the result.
                    array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers'])['@id']);
                } else {
                    throw new  Exception('This group resource is not of the type Group! '.$groupUrl);
                }
            } else {
                throw new  Exception('This group resource is no commonground resource! '.$groupUrl);
            }
        }
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
                // if this subscriber has an email set send the mail to that email
                if (isset($subscriber['email'])) {
                    array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $subscriber['email'], $sender)['@id']);
                }
                // if this subscriber has a resource set, check what kind of resource it is and handle accordingly
                if (isset($subscriber['resource'])) {
                    if ($this->commonGroundService->isResource($subscriber['resource'])) {
                        $resource = $this->commonGroundService->getResource($subscriber['resource']);

                        // Check the resource type
                        switch ($resource['@type']) {
                            case 'Group':
                                // If it is an (wac/)group resource loop through all memberships
                                foreach ($resource['memberships'] as $membership) {
                                    // If this membership is accepted
                                    if (isset($membership['dateAcceptedGroup']) or isset($membership['dateAcceptedUser'])) {
                                        // Get the user if it exists
                                        if ($this->commonGroundService->isResource($membership['userUrl'])) {
                                            $user = $this->commonGroundService->getResource($membership['userUrl']);
                                            // Get CC/contact of the user if it exists
                                            if (isset($user['person']) and $this->commonGroundService->isResource($user['person'])) {
                                                $person = $this->commonGroundService->getResource($user['person']);
                                                // Get email of the contact and send email to it
                                                if (isset($person['emails'][0]['email'])) {
                                                    array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $person['emails'][0]['email'], $sender)['@id']);
                                                } elseif (strpos($user['username'], '@') and strpos($user['username'], '.')) {
                                                    array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $user['username'], $sender)['@id']);
                                                } else {
                                                    throw new  Exception('This person ['.$user['person'].'] of User ['.$membership['userUrl'].'] has no email! ');
                                                }
                                            } elseif (strpos($user['username'], '@') and strpos($user['username'], '.')) {
                                                array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $body, $subject, $user['username'], $sender)['@id']);
                                            } else {
                                                throw new  Exception('This user ['.$membership['userUrl'].'] of Membership ['.$membership['@id'].'] has no person! ');
                                            }
                                        } else {
                                            throw new  Exception('This membership ['.$membership['@id'].'] of Group ['.$subscriber['resource'].'] has no userUrl! ');
                                        }
                                    }
                                }
                                break;
                            default:
                                throw new  Exception('This resource is of a type that cannot be used to send emails to! '.$subscriber['resource']);
                        }
                    } else {
                        throw new  Exception('This resource is no commonground resource! '.$subscriber['resource']);
                    }
                }
            }
        } else {
            throw new  Exception('This sendList has no subscribers! '.$sendList['id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }
}
