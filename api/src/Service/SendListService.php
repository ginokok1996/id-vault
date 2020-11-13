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
        // TODO:get the correct organization for this SendList somehow:
        $newSendList['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => '360e17fb-1a98-48b7-a2a8-212c79a5f51a']);

        // Create a new sendList in BS
        //array_push($results, $this->commonGroundService->createResource($newSendList, ['component' => 'bs', 'type' => 'send_lists']));

        // TODO:remove this:
        array_push($results, 'test');
        array_push($results, $newSendList);

        $sendList->setResult($results);

        return $sendList;
    }

    public function addUserToList(SendList $sendList)
    {
        $results = [];

        // Get info from the DTO SendList
        //$subscriber['sendLists'] = $sendList->getResource();

        // Update or create a subscriber in BS
        //array_push($results, $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers']));

        // TODO:remove this:
        array_push($results, 'test2');
        array_push($results, $subscriber);

        $sendList->setResult($results);

        return $sendList;
    }
}
