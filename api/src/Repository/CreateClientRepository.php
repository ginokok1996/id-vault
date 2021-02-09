<?php

namespace App\Repository;

use App\Entity\CreateClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CreateClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreateClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreateClient[]    findAll()
 * @method CreateClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreateClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreateClient::class);
    }

    // /**
    //  * @return CreateClient[] Returns an array of CreateClient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CreateClient
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
