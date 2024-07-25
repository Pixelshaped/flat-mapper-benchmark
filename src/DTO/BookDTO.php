<?php

namespace App\DTO;

use App\Service\BookDisplayableInterface;
use Pixelshaped\FlatMapperBundle\Mapping\Identifier;
use Pixelshaped\FlatMapperBundle\Mapping\ReferenceArray;

class BookDTO implements BookDisplayableInterface
{
    public function __construct(
        #[Identifier]
        public int $id,
        public string $title,
        public string $isbn,
        #[ReferenceArray(AuthorDTO::class)]
        public array $authors,
        #[ReferenceArray(ReviewDTO::class)]
        public array $reviews,
    ){}

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function getReviews(): iterable
    {
        return $this->reviews;
    }

    public function getAuthors(): iterable
    {
        return $this->authors;
    }
}