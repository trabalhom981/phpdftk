<?php

namespace Qdequippe\PHPDFtk\Field;

final class Text extends Field
{
    public function __construct(string $name, ?string $nameAlt = null, ?int $flags = null, ?string $justification = null, ?string $value = null)
    {
        parent::__construct(Type::Text, $name, $nameAlt, $flags, $justification, $value);
    }
}
