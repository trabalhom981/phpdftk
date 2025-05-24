<?php

namespace Qdequippe\PHPDFtk\Field;

final class Choice extends Field
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
        ?string $valueDefault = null,
        array $stateOption = [],
    ) {
        parent::__construct(
            Type::Choice,
            $name,
            $nameAlt,
            $flags,
            $justification,
            $value,
            $valueDefault
        );

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
