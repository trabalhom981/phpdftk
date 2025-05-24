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
     * Reads a single input PDF file and reports form field statistics.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param bool $utf8 Output is encoded as UTF-8
     *
     * @return FieldInterface[]
     */
    public function dumpDataFields(string $pdfFilePath, bool $utf8 = true): array
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $operation = $utf8 ? 'dump_data_fields_utf8' : 'dump_data_fields';

        $command = [$executablePath, $pdfFilePath, $operation, 'output', '-'];

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        $fields = [];

        $fieldsData = explode("---", trim($output));
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

    /**
     * Assembles ("catenates") pages from input PDFs to create a new PDF.
     *
     * @param string ...$pdfFilePaths Filepath to PDF files.
     *
     * @return string PDF concatenated from input PDFs
     */
    public function cat(string ...$pdfFilePaths): string
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $command = [$executablePath];

        foreach ($pdfFilePaths as $pdfFilePath) {
            $command[] = $pdfFilePath;
        }

        $command[] = 'cat';
        $command[] = 'output';
        $command[] = '-';

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Reads a single input PDF file and reports its metadata, bookmarks (a/k/a outlines), page metrics (media, rotation and labels) and other data.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param bool $utf8 Output is encoded as UTF-8
     *
     * @return Report
     */
    public function dumpData(string $pdfFilePath, bool $utf8 = true): Report
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $operation = $utf8 ? 'dump_data_utf8' : 'dump_data';

        $command = [$executablePath, $pdfFilePath, $operation];

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        $lines = explode("\n", trim($output));
        $pdfID0 = null;
        $pdfID1 = null;
        $numberOfPages = null;
        $bookmarksData = [];
        $infosData = [];
        $pageMediasData = [];

        $currentSection = null;

        $infoCount = 0;
        $bookmarkCount = 0;
        $pageMediaCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if ('InfoBegin' === $line) {
                $currentSection = 'info';
                $infoCount++;

                continue;
            }

            if ('BookmarkBegin' === $line) {
                $currentSection = 'bookmark';
                $bookmarkCount++;

                continue;
            }

            if ('PageMediaBegin' === $line) {
                $currentSection = 'pageMedia';
                $pageMediaCount++;

                continue;
            }

            if (str_starts_with($line, 'PdfID0')) {
                [,$value] = explode(':', $line, 2);
                $pdfID0 = trim($value);

                continue;
            }

            if (str_starts_with($line, 'PdfID1')) {
                [,$value] = explode(':', $line, 2);
                $pdfID1 = trim($value);

                continue;
            }

            if (str_starts_with($line, 'NumberOfPages')) {
                [,$value] = explode(':', $line, 2);
                $numberOfPages = (int) trim($value);

                continue;
            }

            if ('info' === $currentSection) {
                [$key, $value] = explode(':', $line, 2);
                $infosData[$infoCount][$key] = trim($value);
            }

            if ('bookmark' === $currentSection) {
                [$key, $value] = explode(':', $line, 2);
                $bookmarksData[$bookmarkCount][$key] = trim($value);
            }

            if ('pageMedia' === $currentSection) {
                [$key, $value] = explode(':', $line, 2);
                $pageMediasData[$pageMediaCount][$key] = trim($value);
            }
        }

        $infos = [];
        foreach ($infosData as $infoData) {
            $infos[] = new Info(
                key: $infoData['InfoKey'],
                value: $infoData['InfoValue'],
            );
        }

        $bookmarks = [];
        foreach ($bookmarksData as $bookmarkData) {
            $bookmarks[] = new Bookmark(
                title: $bookmarkData['BookmarkTitle'],
                level: (int) $bookmarkData['BookmarkLevel'],
                pageNumber: $bookmarkData['BookmarkPageNumber'],
            );
        }

        $pageMedias = [];
        foreach ($pageMediasData as $pageMediaData) {
            $rect = explode(' ', $pageMediaData['PageMediaRect']);
            array_walk($rect, static fn(&$value) => $value = (int) $value);

            $dimensions = explode(' ', $pageMediaData['PageMediaDimensions']);
            array_walk($dimensions, static fn(&$value) => $value = (int) $value);

            $pageMedias[] = new PageMedia(
                number: (int) $pageMediaData['PageMediaNumber'],
                rotation: (int) $pageMediaData['PageMediaRotation'],
                rect: $rect,
                dimensions: $dimensions,
            );
        }

        return new Report(
            infos: $infos,
            pdfID0: $pdfID0,
            pdfID1: $pdfID1,
            numberOfPages: $numberOfPages,
            bookmarks: $bookmarks,
            pageMedias: $pageMedias,
        );
    }

    /**
     * Reads a single input PDF file and generates an FDF file suitable for fill_form.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     *
     * @return string FDF file generated from input PDF
     */
    public function generateFdf(string $pdfFilePath): string
    {
        $executablePath = $this->executablePath ?? $this->findExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'generate_fdf', 'output', '-'];

        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
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
