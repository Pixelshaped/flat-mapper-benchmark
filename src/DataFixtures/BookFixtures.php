<?php

namespace App\DataFixtures;

use App\Entity\Book;

class BookFixtures extends BaseFixture
{
    public const BOOK_NUMBER = 10000;
    protected function loadData(): void
    {
        $this->createMany(self::BOOK_NUMBER, Book::class, function () {
            $book = new Book();
            $book->setTitle($this->faker->title());
            $book->setIsbn($this->faker->isbn13());
            return $book;
        });
    }
}
