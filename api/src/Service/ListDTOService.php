<?php

namespace App\Service;

use App\Entity\ListDTO;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ListDTOService
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

    public function handle(ListDTO $listDTO)
    {
        $results = [];
        $resource = $this->commonGroundService->getResource($listDTO->getResource());

        $results[0] = 'test';

        $listDTO->setResult($results);
        $this->em->persist($listDTO);
        $this->em->flush();

        return $listDTO;
    }
}
