<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Report;

final readonly class PageMedia
{
    private int $number;

    private ?float $rotation;

    /**
     * @var int[]
     */
    private array $rect;

    /**
     * @var int[]
     */
    private array $dimensions;

    /**
     * @param int[] $rect
     * @param int[] $dimensions
     */
    public function __construct(int $number, ?float $rotation = null, array $rect = [], array $dimensions = [])
    {
        $this->number = $number;
        $this->rotation = $rotation;
        $this->rect = $rect;
        $this->dimensions = $dimensions;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getRotation(): ?float
    {
        return $this->rotation;
    }

    /**
     * @return int[]
     */
    public function getRect(): array
    {
        return $this->rect;
    }

    /**
     * @return int[]
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
