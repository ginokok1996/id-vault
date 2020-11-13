<?php

namespace App\Repository;

use App\Entity\SendList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SendList|null find($id, $lockMode = null, $lockVersion = null)
 * @method SendList|null findOneBy(array $criteria, array $orderBy = null)
 * @method SendList[]    findAll()
 * @method SendList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SendListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SendList::class);
    }

    // /**
    //  * @return SendList[] Returns an array of SendList objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SendList
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
