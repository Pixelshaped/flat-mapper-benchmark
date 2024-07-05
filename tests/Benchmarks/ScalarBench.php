<?php

namespace App\Tests\Benchmarks;

use App\DTO\BookScalarDTO;
use App\Repository\BookRepository;
use PhpBench\Attributes\BeforeMethods;
use Pixelshaped\FlatMapperBundle\FlatMapper;

#[BeforeMethods('setUp')]
class ScalarBench extends AbstractBench
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