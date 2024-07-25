<?php

namespace App\DTO;

use App\Service\ReviewDisplayableInterface;
use Pixelshaped\FlatMapperBundle\Mapping\Identifier;
use Pixelshaped\FlatMapperBundle\Mapping\Scalar;

class ReviewDTO implements ReviewDisplayableInterface
{
    public function __construct(
        #[Identifier('review_id')]
        public int $id,
        #[Scalar('review_rating')]
        public int $rating,
    ){}

    public function getRating(): ?int
    {
        return $this->rating;
    }
}