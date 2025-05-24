<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Field;

enum Type: string
{
    case Text = 'Text';
    case Button = 'Button';
    case Choice = 'Choice';
}
