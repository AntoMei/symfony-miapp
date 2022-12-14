<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
    * @return Post[] Returns an array of Post objects
    */
    public function findByTextPaginated(int $page, string $searchTerm)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere("p.content LIKE :val")
            ->setParameter('val', '%'.$searchTerm.'%')
            ->orderBy('p.publishedAt', 'DESC');

        return (new Paginator($qb))->paginate($page);
    }


//    /**
//     * @return Post[] Returns an array of Post objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
    * @return Post[] Returns an array of Post objects
    */
    public function findAllPaginated(int $page): Paginator
    {
        $qb =  $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')            
        ;
        //Devolvemos los resutados de la página
        return (new Paginator($qb))->paginate($page);
    }


    /**
    * @return Post[] Returns an array of Post objects
    */
    public function findRecents()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * Returns an array of Post objects
    */
    public function findAll()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}