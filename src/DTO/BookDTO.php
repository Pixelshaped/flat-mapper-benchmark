<?php

namespace App\DTO;

use Pixelshaped\FlatMapperBundle\Attributes\Identifier;
use Pixelshaped\FlatMapperBundle\Attributes\ReferencesArray;

class BookDTO
{
    public function __construct(
        #[Identifier]
        public int $id,
        public string $title,
        public string $isbn,
        #[ReferencesArray(AuthorDTO::class)]
        public array $authors,
    ){}
}