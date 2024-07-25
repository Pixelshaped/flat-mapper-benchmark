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
 * This is the main and most relevant comparison: FlatMapper truly shines when using nested DTOs versus entities.
 * Using other DTO mappers based on entities will eventually result in lower performance than `benchDoctrineEntities`.
 */
class NestedBench extends AbstractBench
{
    private BookRepository $bookRepository;

    private EntityManagerInterface $entityManager;
    private FlatMapper $flatMapper;

    private BookDisplayer $bookDisplayer;

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

    public function benchDoctrineEntitiesWithAssociationHydrator()
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