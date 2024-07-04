<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AuthorFixtures extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(): void
    {
        $this->createMany(BookFixtures::BOOK_NUMBER/10, Author::class, function () {
            $author = new Author();
            $author->setFirstName($this->faker->firstName());
            $author->setLastName($this->faker->lastName());

            for($i = 0; $i < random_int(1, 10); $i++) {
                $author->addBook(
                    $this->getRandomReference(Book::class)
                );
            }

            return $author;
        });
    }

    public function getDependencies(): array
    {
        return [
            BookFixtures::class
        ];
    }
}
