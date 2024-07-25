<?php

namespace App\DTO;

use App\Service\AuthorDisplayableInterface;
use Pixelshaped\FlatMapperBundle\Mapping\Identifier;
use Pixelshaped\FlatMapperBundle\Mapping\Scalar;

class AuthorDTO implements AuthorDisplayableInterface
{
    public function __construct(
        #[Identifier('author_id')]
        public int $id,
        #[Scalar('author_first_name')]
        public string $firstName,
        #[Scalar('author_last_name')]
        public string $lastName,
    ){}

    public function getFullName(): ?string
    {
        return $this->firstName.' '.$this->lastName;
    }
}