<?php

namespace App\Tests\Benchmarks;

use App\DTO\BookScalarDTO;
use App\Repository\BookRepository;
use Pixelshaped\FlatMapperBundle\FlatMapper;

/**
 * In these tests we can see that all methods to map DQL results to a DTO only supporting scalar properties are almost as effective.
 * Doctrine DTO has a little edge as it creates the DTOs during the results hydration, where other methods have to do it in another loop.
 * FlatMapper is a little slower as it uses named parameters internally instead of mapping data to the constructor in the same order as it has been queried, but it's not significant.
 */
class DQLScalarBench extends AbstractBench
{
    private BookRepository $bookRepository;
    private FlatMapper $flatMapper;
    public function benchFlatMapperWithDQL()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb->select('book.id, book.title, book.isbn')
            ->getQuery()
            ->toIterable();
        $result = $this->flatMapper->map(BookScalarDTO::class, $result);
    }

    public function benchDoctrineDTOs()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb->select(sprintf('NEW %s(book.id, book.title, book.isbn)', BookScalarDTO::class))
            ->getQuery()
            ->getResult();
    }

    public function benchManualMappingWithDQL()
    {
        $qb = $this->bookRepository->createQueryBuilder('book');
        $result = $qb->select('book.id, book.title, book.isbn')
            ->getQuery()
            ->toIterable();

        $resultSet = [];
        foreach ($result as $productEdit) {
            $resultSet[] = new BookScalarDTO(...$productEdit);
        }
    }

    public function setUp(): void
    {
        $container = $this->container();
        $this->bookRepository = $container->get(BookRepository::class);
        $this->flatMapper = $container->get(FlatMapper::class);
    }
}