<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Field;

abstract class Field implements FieldInterface
{
    private Type $type;

    private string $name;

    private ?string $nameAlt;

    private ?int $flags;

    private ?string $justification;

    private ?string $value;

    private ?string $valueDefault;

    public function __construct(
        Type $type,
        string $name,
        ?string $nameAlt = null,
        ?int $flags = null,
        ?string $justification = null,
        ?string $value = null,
        ?string $valueDefault = null,
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->nameAlt = $nameAlt;
        $this->flags = $flags;
        $this->justification = $justification;
        $this->value = $value;
        $this->valueDefault = $valueDefault;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAlt(): ?string
    {
        return $this->nameAlt;
    }

    public function getFlags(): ?int
    {
        return $this->flags;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getValueDefault(): ?string
    {
        return $this->valueDefault;
    }
}
