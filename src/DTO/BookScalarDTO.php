<?php

namespace App\DTO;

use Pixelshaped\FlatMapperBundle\Mapping\Identifier;

class BookScalarDTO
{
    public function __construct(
        #[Identifier]
        public int $id,
        public string $title,
        public string $isbn,
    ){}
}