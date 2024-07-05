<?php

namespace App\Service;

use Doctrine\Common\Collections\Collection;

/**
 * This is just a dummy service that calls the same accessors on Book entity or BookDTO in our benchs
 */
class BookDisplayer
{
    public function display(BookDisplayableInterface $book): string
    {
        $returnStr = $book->getTitle() . ' - '. $book->getIsbn();

        if(!empty($book->getAuthors())) {
            $authors = $book->getAuthors() instanceof Collection ? $book->getAuthors()->toArray() : $book->getAuthors();
            $authorsNames = array_map(fn($author) => $author->getFullName(), $authors);
            $returnStr .= ' (';
            $returnStr .= implode(', ', $authorsNames);
            $returnStr .= ')';
        }

        $sum = $count = 0;
        foreach($book->getReviews() as $review){
            $sum += $review->getRating();
            $count++;
        }
        if($count) {
            $returnStr .= ' '.round($sum / $count).' stars !';
        }

        return $returnStr;
    }
}