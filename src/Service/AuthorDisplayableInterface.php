<?php

namespace App\Service;

interface AuthorDisplayableInterface
{
    public function getFullName(): ?string;
}