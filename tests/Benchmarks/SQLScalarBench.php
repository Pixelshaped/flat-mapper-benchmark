<?php

namespace App\Tests\Benchmarks;

use App\DTO\BookScalarDTO;
use Doctrine\ORM\EntityManagerInterface;
use Pixelshaped\FlatMapperBundle\FlatMapper;

/**
 * This is a bonus test to show how close in terms of performance FlatMapper is from manually mapping data to scalar DTOs with raw SQL results
 */
class SQLScalarBench extends AbstractBench
{
    private EntityManagerInterface $entityManager;
    private FlatMapper $flatMapper;
    public function benchFlatMapperWithSQL()
    {
        $query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
        $result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
    }

    public function benchManualMappingWithSQL()
    {
        $query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
        $resultSet = [];
        foreach($query->iterateAssociative() as $row) {
            $resultSet[] = new BookScalarDTO(...$row);
        }
    }

    public function setUp(): void
    {
        $container = $this->container();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->flatMapper = $container->get(FlatMapper::class);
    }
}