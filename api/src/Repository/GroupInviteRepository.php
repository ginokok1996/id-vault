<?php

namespace App\Repository;

use App\Entity\GroupInvite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GroupInvite|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupInvite|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupInvite[]    findAll()
 * @method GroupInvite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupInviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupInvite::class);
    }

    // /**
    //  * @return GroupInvite[] Returns an array of GroupInvite objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GroupInvite
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
