<?php

namespace App\Tests\Benchmarks;

use App\DTO\BookScalarDTO;
use Doctrine\ORM\EntityManagerInterface;
use PhpBench\Attributes\BeforeMethods;
use Pixelshaped\FlatMapperBundle\FlatMapper;

#[BeforeMethods('setUp')]
class SQLBench extends AbstractBench
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