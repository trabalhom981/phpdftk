<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Field;

interface FieldInterface
{
    public function getName(): string;

    public function getNameAlt(): ?string;

    public function getType(): Type;

    public function getFlags(): ?int;

    public function getJustification(): ?string;

    public function getValue(): ?string;
}
