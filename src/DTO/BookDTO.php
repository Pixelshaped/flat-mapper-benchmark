<?php

namespace App\DTO;

use App\Service\BookDisplayableInterface;
use Pixelshaped\FlatMapperBundle\Attributes\Identifier;
use Pixelshaped\FlatMapperBundle\Attributes\ReferencesArray;

class BookDTO implements BookDisplayableInterface
{
    public function __construct(
        #[Identifier]
        public int $id,
        public string $title,
        public string $isbn,
        #[ReferencesArray(AuthorDTO::class)]
        public array $authors,
        #[ReferencesArray(ReviewDTO::class)]
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