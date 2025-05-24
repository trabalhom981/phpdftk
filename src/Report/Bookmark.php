<?php

namespace Qdequippe\PHPDFtk\Report;

final class Bookmark
{
    private ?string $title;

    private ?int $level;

    private ?int $pageNumber;

    public function __construct(?string $title = null, ?int $level = null, ?int $pageNumber = null)
    {
        $this->title = $title;
        $this->level = $level;
        $this->pageNumber = $pageNumber;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }
}
