<?php

namespace App\Tests\Benchmarks;

use App\DTO\BookDTO;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookDisplayer;
use Doctrine\ORM\EntityManagerInterface;
use Pixelshaped\FlatMapperBundle\FlatMapper;
use SyliusLabs\AssociationHydrator\AssociationHydrator;


/**
 * This is the main and most relevant comparison: FlatMapper truly shines when using nested DTOs versus nested entities.
 */
class NestedBench extends AbstractBench
{
    private BookRepository $bookRepository;

    private EntityManagerInterface $entityManager;
    private FlatMapper $flatMapper;

    private BookDisplayer $bookDisplayer;

    /**
     * In this benchmark we're using FlatMapper to map a DTO that has the same number of fields than the entity it mimics (in benchDoctrineEntities). It's the fairest comparison. In real life situations entities tend to have a lot of unneeded properties.
     */
    public function benchFlatMapperDTOs()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb->select('book.id, book.title, book.isbn, authors.id as author_id, authors.firstName as author_first_name, authors.lastName as author_last_name, reviews.id as review_id, reviews.rating as review_rating')
            ->leftJoin('book.authors', 'authors')
            ->leftJoin('book.reviews', 'reviews')
            ->getQuery()
            ->getResult();
        $result = $this->flatMapper->map(BookDTO::class, $result);
        foreach ($result as $book) {
            $this->bookDisplayer->display($book);
        }
    }

    /**
     * This is the base Doctrine benchmark. Using other DTO mappers based on entities will display similar performance.
     */
    public function benchDoctrineEntities()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb
            ->leftJoin('book.authors', 'authors')
            ->addSelect('authors')
            ->leftJoin('book.reviews', 'reviews')
            ->addSelect('reviews')
            ->getQuery()
            ->getResult();
        foreach ($result as $book) {
            $this->bookDisplayer->display($book);
        }
    }

    /**
     * This benchmarks Doctrine when the user forgets to add JOIN statements and Doctrine has to make N+1 queries. It's not a fair comparison but illustrates how Doctrine behaves in such a situation (on one hand: it will always land on its feet, on the other hand: at what cost?)
     */
    public function benchDoctrineEntitiesWithN1()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb
            ->getQuery()
            ->getResult();
        foreach ($result as $book) {
            $this->bookDisplayer->display($book);
        }
    }

    /**
     * Out of curiosity I added a benchmark for Sylius Association-Hydrator. It's a widely used bundle that relies on PARTIAL queries (which restrict its usage to ORM2).
     */
    public function benchDoctrineEntitiesWithSyliusAssociationHydrator()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb
            ->getQuery()
            ->getResult();

        $sylusAssociationHydrator = new AssociationHydrator(
            $this->entityManager,
            $this->entityManager->getClassMetadata(Book::class)
        );

        $sylusAssociationHydrator->hydrateAssociations($result, [
            'authors',
            'reviews',
        ]);

        foreach ($result as $book) {
            $this->bookDisplayer->display($book);
        }
    }

    public function setUp(): void
    {
        $container = $this->container();
        $this->bookRepository = $container->get(BookRepository::class);
        $this->flatMapper = $container->get(FlatMapper::class);
        $this->bookDisplayer = $container->get(BookDisplayer::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }
}