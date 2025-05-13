<?php

namespace Qdequippe\PHPDFtk;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final readonly class Pdftk
{
    public function __construct(
        private ?string $executablePath = null,
    ) {

    }

    /**
     * @param string $inputFilePath Input PDF file path
     * @param string $outputFilePath Output PDF file path
     * @return void
     */
    public function fillForm(string $inputFilePath, string $formDataFilePath, string $outputFilePath, bool $flatten = false): void
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $command = [$executablePath, $inputFilePath, 'fill_form', $formDataFilePath, 'output', $outputFilePath];

        if ($flatten) {
            $command[] = 'flatten';
        }

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function findExecutablePath(): string
    {
        $executableFinder = new ExecutableFinder();
        $executablePath = $executableFinder->find('pdftk');

        if (null === $executablePath) {
            throw new \RuntimeException('Pdftk not found');
        }

        return $executablePath;
    }
}
