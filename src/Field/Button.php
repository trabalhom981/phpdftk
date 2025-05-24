<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Field;

final class Button extends Field
{
    /**
     * @var string[]
     */
    private array $stateOption;

    /**
     * @param string[] $stateOption
     */
    public function __construct(
        string $name,
        ?string $nameAlt = null,
        ?int $flags = null,
        ?string $justification = null,
        ?string $value = null,
        array $stateOption = [],
    ) {
        parent::__construct(Type::Button, $name, $nameAlt, $flags, $justification, $value);

        $this->stateOption = $stateOption;
    }

    /**
     * @return string[]
     */
    public function getStateOption(): array
    {
        return $this->stateOption;
    }
}
