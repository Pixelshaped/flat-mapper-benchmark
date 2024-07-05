<?php

namespace App\DTO;

use App\Service\ReviewDisplayableInterface;
use Pixelshaped\FlatMapperBundle\Attributes\Identifier;
use Pixelshaped\FlatMapperBundle\Attributes\InboundPropertyName;

class ReviewDTO implements ReviewDisplayableInterface
{
    public function __construct(
        #[Identifier('review_id')]
        public int $id,
        #[InboundPropertyName('review_rating')]
        public int $rating,
    ){}

    public function getRating(): ?int
    {
        return $this->rating;
    }
}