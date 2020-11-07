<?php

namespace App\Repository;

use App\Entity\ListDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ListDTO|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListDTO|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListDTO[]    findAll()
 * @method ListDTO[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListDTORepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListDTO::class);
    }

    // /**
    //  * @return ListDTO[] Returns an array of ListDTO objects
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
    public function findOneBySomeField($value): ?ListDTO
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
