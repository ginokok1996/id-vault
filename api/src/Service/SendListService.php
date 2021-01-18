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

        // Create a new sendList in BS or update an existing one
        $sendList = $this->getListData($sendListDTO);
        $sendList = $this->commonGroundService->saveResource($sendList, ['component' => 'bs', 'type' => 'send_lists']);

        $results = $this->updateListGroups($results, $sendListDTO, $sendList);

        $sendListUrl = $this->commonGroundService->cleanUrl(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendList['id']]);
        array_push($results, $this->commonGroundService->getResource($sendListUrl, [], false));

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    private function getListData(SendList $sendListDTO) {
        // if sendList is set we are going to update an existing BS/sendlist
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
        $sendList['organization'] = $this->getListOrganization($sendListDTO->getClientSecret());

        return $sendList;
    }

    private function getListOrganization(string $clientSecret) {
        // Get organization for a sendList
        $applications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['secret' => $clientSecret])['hydra:member'];
        if (count($applications) < 1) {
            throw new  Exception('No applications found with this client secret! '.$sendListDTO->getClientSecret());
        } else {
            $application = $applications[0];
            if (isset($application['contact'])) {
                $applicationContact = $this->commonGroundService->getResource($application['contact']);
                if (isset($applicationContact['organization']['id'])) {
                    return $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $applicationContact['organization']['id']]);
                } else {
                    throw new  Exception('No organization found in this application contact! ' . $applicationContact['id']);
                }
            } else {
                throw new  Exception('No contact found in this application! ' . $application['id']);
            }
        }
    }

    private function updateListGroups(array $results, SendList $sendListDTO, array $sendList) {
        // Create subscribers for the given groups
        if ($sendListDTO->getGroups()) {
            $results = $this->addGroupsToList($results, $sendListDTO->getGroups(), $sendList['id']);
        }

        // Make sure to get the up to date sendlist with correct subscribers (might be added above here^)
        $sendListUrl = $this->commonGroundService->cleanUrl(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendList['id']]);
        $sendList = $this->commonGroundService->getResource($sendListUrl, [], false);

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
                // If needed, remove this subscriber group from the sendList
                if ($remove) {
                    array_push($results, $this->removeListFromSubscriber($subscriber, $sendList['id']));
                }
            }
        }

        return $results;
    }

    public function deleteList(SendList $sendListDTO)
    {
        $results = [];

        // get the sendlist
        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);

        // loop through all subscribers and remove the sendlist from them
        foreach ($sendList['subscribers'] as $subscriber) {
            array_push($results, $this->removeListFromSubscriber($subscriber, $sendList['id']));
        }

        // delete the sendlist
        array_push($results, $this->commonGroundService->deleteResource($sendList));

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    private function removeListFromSubscriber(array $subscriber, string $sendListId) {
        // remove sendList from this subscriber
        $subscriberSendLists = [];
        foreach ($subscriber['sendLists'] as $subscriberSendList) {
            if ($subscriberSendList != '/send_lists/'.$sendListId) {
                array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
            }
        }
        $subscriber['sendLists'] = $subscriberSendLists;

        // save the subscriber
        return $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers'])['@id'];
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
            foreach ($sendListDTO->getEmails() as $email) {
                $results = $this->saveSubscriber($results, $sendList['id'], $email);
            }
        }

        if ($sendListDTO->getGroups()) {
            $results = $this->addGroupsToList($results, $sendListDTO->getGroups(), $sendList['id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    private function addGroupsToList(array $results, array $groupIds, string $sendListId)
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
                    $results = $this->saveSubscriber($results, $sendListId, $groupUrl, 'resource');
                } else {
                    throw new  Exception('This group resource is not of the type Group! '.$groupUrl);
                }
            } else {
                throw new  Exception('This group resource is no commonground resource! '.$groupUrl);
            }
        }

        return $results;
    }

    private function saveSubscriber(array $results, string $sendListId, string $subscriber, string $type = 'email') {
        // Check if this group has already a subscriber object in BS
        if ($type == 'resource') {
            $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['resource' => $subscriber])['hydra:member'];
        } elseif ($type == 'email') {
            $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['email' => $subscriber])['hydra:member'];
        }
        if (count($subscribers) > 0) {
            // Set correct data for updating this existing subscriber
            $subscriber = $this->setUpdateSubscriberData($subscribers[0], $sendListId);
        } else {
            // Set correct data for creating a new subscriber
            $subscriber = $this->setCreateSubscriberData($type, $sendListId);
        }

        // Update or create a subscriber in BS and add them to the result.
        array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers'])['@id']);
        return $results;
    }

    private function setUpdateSubscriberData(array $subscriber, string $sendListId) {
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

        return $subscriber;
    }

    private function setCreateSubscriberData(string $type, string $sendListId) {
        $subscriber = [];

        if ($type == 'resource') {
            // Set resource to groupUrl
            $subscriber['resource'] = $subscriber;
        } elseif ($type == 'email') {
            // Set email to create a new subscriber
            $subscriber['email'] = $subscriber;
        }

        // Add the sendList to it
        $subscriber['sendLists'][] = '/send_lists/'.$sendListId;

        return $subscriber;
    }

    public function sendToList(SendList $sendListDTO)
    {
        $results = [];

        $sendList = $this->commonGroundService->getResource($sendListDTO->getSendList(), [], false);
        if (!empty($sendList['subscribers'])) {
            $email['body'] = $sendListDTO->getHtml();
            $email['subject'] = $sendListDTO->getTitle();
            $email['sender'] = $sendListDTO->getSender();

            // Loop through all subscribers
            foreach ($sendList['subscribers'] as $subscriber) {
                // if this subscriber has an email set send the mail to that email
                if (isset($subscriber['email'])) {
                    array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $email['body'], $email['subject'], $subscriber['email'], $email['sender'])['@id']);
                }
                // if this subscriber has a resource set, check what kind of resource it is and handle accordingly
                if (isset($subscriber['resource'])) {
                    $results = $this->sendToResource($results, $subscriber['resource'], $email);
                }
            }
        } else {
            throw new  Exception('This sendList has no subscribers! '.$sendList['id']);
        }

        $sendListDTO->setResult($results);

        return $sendListDTO;
    }

    private function sendToResource(array $results, string $resource, array $email) {
        if ($this->commonGroundService->isResource($resource)) {
            $resource = $this->commonGroundService->getResource($resource);

            // Check the resource type
            switch ($resource['@type']) {
                case 'Group':
                    // If it is an (wac/)group resource
                    $results = $this->sendToGroup($results, $resource, $email);
                    break;
                default:
                    throw new  Exception('This resource is of a type that cannot be used to send emails to! '.$resource);
            }
        } else {
            throw new  Exception('This resource is no commonground resource! '.$resource);
        }

        return $results;
    }

    private function sendToGroup(array $results, string $resource, array $email) {
        foreach ($resource['memberships'] as $membership) {
            // If this membership is accepted
            if (isset($membership['dateAcceptedGroup']) or isset($membership['dateAcceptedUser'])) {
                // Get the user & Get CC/contact of the user if it exists
                $user = $this->commonGroundService->getResource($membership['userUrl']);
                if (isset($user['person']) and $this->commonGroundService->isResource($user['person'])) {
                    $person = $this->commonGroundService->getResource($user['person']);
                    // Get email of the contact and send email to it
                    if (isset($person['emails'][0]['email'])) {
                        array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $email['body'], $email['subject'], $person['emails'][0]['email'], $email['sender'])['@id']);
                    } elseif (strpos($user['username'], '@') and strpos($user['username'], '.')) {
                        array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $email['body'], $email['subject'], $user['username'], $email['sender'])['@id']);
                    } else {
                        throw new  Exception('This person ['.$user['person'].'] of User ['.$membership['userUrl'].'] has no email! ');
                    }
                } elseif (strpos($user['username'], '@') and strpos($user['username'], '.')) {
                    array_push($results, $this->idVaultService->sendMail('dd100c45-2814-41d6-bb17-7b95f062f784', $email['body'], $email['subject'], $user['username'], $email['sender'])['@id']);
                } else {
                    throw new  Exception('This user ['.$membership['userUrl'].'] of Membership ['.$membership['@id'].'] has no person! ');
                }
            }
        }
        return $results;
    }
}
