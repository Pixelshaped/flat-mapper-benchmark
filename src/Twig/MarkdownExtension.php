<?php

namespace App\Twig;

use MaddHatter\MarkdownTable\Builder as MarkdownTableBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MarkdownExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('markdown_table', $this->renderMarkdownTable(...))
        ];
    }

    public function renderMarkdownTable(array $rows, ?array $headers = null, ?array $align = null) : string
    {
        $builder = new MarkdownTableBuilder();

        $builder->rows($rows);

        if(null !== $headers) {
            $builder->headers($headers);
        }

        if(null !== $align) {
            $builder->align($align);
        }

        return $builder->render();
    }
}