<?php

namespace Qdequippe\PHPDFtk;

final readonly class Report
{
    /**
     * @var Info[]
     */
    private array $infos;

    private ?int $numberOfPages;

    private ?string $pdfID0;

    private ?string $pdfID1;

    /**
     * @var Bookmark[]
     */
    private array $bookmarks;

    /**
     * @var PageMedia[]
     */
    private array $pageMedias;

    /**
     * @param Info[] $infos
     * @param Bookmark[] $bookmarks
     * @param PageMedia[] $pageMedias
     */
    public function __construct(
        array $infos = [],
        ?string $pdfID0 = null,
        ?string $pdfID1 = null,
        ?int $numberOfPages = null,
        array $bookmarks = [],
        array $pageMedias = [],
    ) {
        $this->numberOfPages = $numberOfPages;
        $this->bookmarks = $bookmarks;
        $this->pageMedias = $pageMedias;
        $this->pdfID0 = $pdfID0;
        $this->pdfID1 = $pdfID1;
        $this->infos = $infos;
    }

    public function getInfos(): array
    {
        return $this->infos;
    }

    public function getNumberOfPages(): ?int
    {
        return $this->numberOfPages;
    }

    /**
     * @return Bookmark[]
     */
    public function getBookmarks(): array
    {
        return $this->bookmarks;
    }

    /**
     * @return PageMedia[]
     */
    public function getPageMedias(): array
    {
        return $this->pageMedias;
    }

    public function getPdfID0(): ?string
    {
        return $this->pdfID0;
    }

    public function getPdfID1(): ?string
    {
        return $this->pdfID1;
    }
}
