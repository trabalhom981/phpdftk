<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Exception;

use Symfony\Component\Process\Process;

final class ProcessFailedException extends \RuntimeException
{
    public static function fromProcess(Process $process): self
    {
        return new ProcessFailedException(
            message: $process->getErrorOutput(),
            code: $process->getExitCode() ?? 0,
        );
    }
}
