<?php

namespace App\Repository;

use App\Entity\AcceptInvite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AcceptInvite|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptInvite|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptInvite[]    findAll()
 * @method AcceptInvite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptInviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptInvite::class);
    }

    // /**
    //  * @return AcceptInvite[] Returns an array of AcceptInvite objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AcceptInvite
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
