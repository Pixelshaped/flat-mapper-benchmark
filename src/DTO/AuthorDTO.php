<?php

namespace App\DTO;

use Pixelshaped\FlatMapperBundle\Attributes\Identifier;
use Pixelshaped\FlatMapperBundle\Attributes\InboundPropertyName;

class AuthorDTO
{
    public function __construct(
        #[Identifier('author_id')]
        public int $id,
        #[InboundPropertyName('author_first_name')]
        public string $firstName,
        #[InboundPropertyName('author_last_name')]
        public string $lastName,
    ){}
}