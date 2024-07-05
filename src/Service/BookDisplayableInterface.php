<?php

namespace App\Service;

interface BookDisplayableInterface
{
    public function getTitle(): ?string;
    public function getIsbn(): ?string;
    public function getReviews(): iterable;
    public function getAuthors(): iterable;
}