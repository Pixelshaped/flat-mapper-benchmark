<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Review;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ReviewFixtures extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(): void
    {
        $this->createMany(BookFixtures::BOOK_NUMBER * 3, Review::class, function () {
            $review = new Review();
            $review->setNote(random_int(1, 5));
            $review->setBook(
                $this->getRandomReference(Book::class)
            );
            return $review;
        });
    }

    public function getDependencies(): array
    {
        return [
            BookFixtures::class
        ];
    }
}
