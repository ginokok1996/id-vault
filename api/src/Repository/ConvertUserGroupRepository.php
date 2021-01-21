<?php

namespace App\Repository;

use App\Entity\ConvertUserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConvertUserGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConvertUserGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConvertUserGroup[]    findAll()
 * @method ConvertUserGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConvertUserGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConvertUserGroup::class);
    }

    // /**
    //  * @return ConvertUserGroup[] Returns an array of ConvertUserGroup objects
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
    public function findOneBySomeField($value): ?ConvertUserGroup
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
