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

    public function handle(SendList $sendList)
    {
        $results = [];
        $resource = $this->commonGroundService->getResource($sendList->getResource());

        $results[0] = 'test';

        $sendList->setResult($results);
        $this->em->persist($sendList);
        $this->em->flush();

        return $sendList;
    }
}
