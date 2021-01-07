<?php

namespace App\Repository;

use App\Entity\CreateGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CreateGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreateGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreateGroup[]    findAll()
 * @method CreateGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreateGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreateGroup::class);
    }

    // /**
    //  * @return CreateGroup[] Returns an array of CreateGroup objects
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
    public function findOneBySomeField($value): ?CreateGroup
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
