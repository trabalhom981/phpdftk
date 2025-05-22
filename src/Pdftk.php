<?php

namespace Qdequippe\PHPDFtk;

use Qdequippe\PHPDFtk\Field\Button;
use Qdequippe\PHPDFtk\Field\Choice;
use Qdequippe\PHPDFtk\Field\FieldInterface;
use Qdequippe\PHPDFtk\Field\Text;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final readonly class Pdftk
{
    public function __construct(
        private ?string $executablePath = null,
    ) {}

    /**
     * Fills the input PDF’s form fields with the data from an FDF file or XFDF file.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param string $formDataFilePath Filepath to the form data
     * @param bool $flatten Use this option to merge an input PDF’s interactive form fields (and their data) with the PDF’s pages.
     *
     * @return string PDF filled with form data
     */
    public function fillForm(string $pdfFilePath, string $formDataFilePath, bool $flatten = false): string
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'fill_form', $formDataFilePath, 'output', '-'];

        if ($flatten) {
            $command[] = 'flatten';
        }

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * @param string $pdfFilePath
     * @return FieldInterface[]
     */
    public function dumpDataFields(string $pdfFilePath): array
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'dump_data_fields_utf8', 'output', '-'];

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        $fields = [];

        $fieldsData = (explode("---", trim($output)));
        $fieldsData = array_filter($fieldsData);
        foreach ($fieldsData as $fieldData) {
            $fieldParts = explode("\n", $fieldData);
            $fieldParts = array_filter($fieldParts);
            $parts = [];
            foreach ($fieldParts as $fieldPart) {
                [$fieldPartName, $fieldPartValue] = explode(": ", $fieldPart);

                if ('FieldStateOption' !== $fieldPartName) {
                    $parts[$fieldPartName] = $fieldPartValue;

                    continue;
                }

                $parts[$fieldPartName][] = $fieldPartValue;
            }

            $field = null;
            switch ($parts['FieldType']) {
                case 'Text':
                    $field = new Text(
                        name: $parts['FieldName'],
                        nameAlt: $parts['FieldNameAlt'] ?? null,
                        flags: $parts['FieldFlags'] ?? null,
                        justification: $parts['FieldJustification'] ?? null,
                        value: $parts['FieldValue'] ?? null,
                    );
                    break;
                case 'Button':
                    $field = new Button(
                        name: $parts['FieldName'],
                        nameAlt: $parts['FieldNameAlt'] ?? null,
                        flags: $parts['FieldFlags'] ?? null,
                        justification: $parts['FieldJustification'] ?? null,
                        value: $parts['FieldValue'] ?? null,
                        stateOption: $parts['FieldStateOption'] ?? [],
                    );
                    break;
                case 'Choice':
                    $field = new Choice(
                        name: $parts['FieldName'],
                        nameAlt: $parts['FieldNameAlt'] ?? null,
                        flags: $parts['FieldFlags'] ?? null,
                        justification: $parts['FieldJustification'] ?? null,
                        value: $parts['FieldValue'] ?? null,
                        valueDefault: $parts['FieldValueDefault'] ?? null,
                        stateOption: $parts['FieldStateOption'] ?? [],
                    );
                    break;
            }

            if (null === $field) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
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
