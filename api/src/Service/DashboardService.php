<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getAll()
    {
    }
}
