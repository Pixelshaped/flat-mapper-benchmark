<?php

namespace App\Service;

interface ReviewDisplayableInterface
{
    public function getRating(): ?int;
}