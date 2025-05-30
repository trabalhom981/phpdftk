<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk;

use Qdequippe\PHPDFtk\Exception\ExecutableNotFoundException;
use Qdequippe\PHPDFtk\Exception\ProcessFailedException;
use Qdequippe\PHPDFtk\Field\Button;
use Qdequippe\PHPDFtk\Field\Choice;
use Qdequippe\PHPDFtk\Field\FieldInterface;
use Qdequippe\PHPDFtk\Field\Text;
use Qdequippe\PHPDFtk\Report\Bookmark;
use Qdequippe\PHPDFtk\Report\Info;
use Qdequippe\PHPDFtk\Report\PageMedia;
use Qdequippe\PHPDFtk\Report\Report;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final readonly class PDFtk
{
    public function __construct(
        private ?string $executablePath = null,
    ) {}

    private function getExecutablePath(): string
    {
        if (null !== $this->executablePath) {
            return $this->executablePath;
        }

        $executableFinder = new ExecutableFinder();
        $executablePath = $executableFinder->find('pdftk');

        if (null === $executablePath) {
            throw new ExecutableNotFoundException();
        }

        return $executablePath;
    }

    /**
     * @param scalar[] $command
     */
    private function runCommand(array $command): string
    {
        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            throw ProcessFailedException::fromProcess($process);
        }

        return $process->getOutput();
    }

    /**
     * Fills the input PDF’s form fields with the data from an FDF file or XFDF file.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param string $formDataFilePath Filepath to the form data
     * @param bool $flatten Use this option to merge an input PDF’s interactive form fields (and their data) with the PDF’s pages.
     *
     * @return string PDF filled with form data
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function fillForm(string $pdfFilePath, string $formDataFilePath, bool $flatten = true): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'fill_form', $formDataFilePath, 'output', '-'];

        if ($flatten) {
            $command[] = 'flatten';
        }

        return $this->runCommand($command);
    }

    /**
     * Reads a single input PDF file and reports form field statistics.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param bool $utf8 Output is encoded as UTF-8
     *
     * @return FieldInterface[]
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function dumpDataFields(string $pdfFilePath, bool $utf8 = true): array
    {
        $executablePath = $this->getExecutablePath();

        $operation = $utf8 ? 'dump_data_fields_utf8' : 'dump_data_fields';

        $command = [$executablePath, $pdfFilePath, $operation, 'output', '-'];

        $output = $this->runCommand($command);

        $fields = [];

        $fieldsData = explode("---", trim($output));
        $fieldsData = array_filter($fieldsData);
        $fieldsData = explode("---", trim($output));
        $fieldsData = array_filter($fieldsData);
        foreach ($fieldsData as $fieldData) {
            $fieldParts = explode("\n", $fieldData);
            $fieldParts = array_filter($fieldParts);
            /** @var array<string, string|string[]> $parts */
            $parts = ['FieldStateOption' => []];
            foreach ($fieldParts as $fieldPart) {
                [$fieldPartName, $fieldPartValue] = explode(": ", $fieldPart);

                if ('FieldStateOption' !== $fieldPartName) {
                    $parts[$fieldPartName] = $fieldPartValue;

                    continue;
                }

                /** @var string[] $options */
                $options = is_array($parts['FieldStateOption']) ? $parts['FieldStateOption'] : [];
                $options[] = $fieldPartValue;
                $parts['FieldStateOption'] = $options;
            }

            $field = null;
            /** @var string $name */
            $name = $parts['FieldName'];
            /** @var string|null $nameAlt */
            $nameAlt = $parts['FieldNameAlt'] ?? null;
            /** @var int|null $flags */
            $flags = $parts['FieldFlags'] ? (int) $parts['FieldFlags'] : null;
            /** @var string|null $justification */
            $justification = $parts['FieldJustification'] ?? null;
            /** @var string|null $value */
            $value = $parts['FieldValue'] ?? null;
            /** @var string[] $stateOption */
            $stateOption = $parts['FieldStateOption'] ?? [];

            switch ($parts['FieldType']) {
                case 'Text':
                    $field = new Text(
                        name: $name,
                        nameAlt: $nameAlt,
                        flags: $flags,
                        justification: $justification,
                        value: $value,
                    );
                    break;
                case 'Button':
                    $field = new Button(
                        name: $name,
                        nameAlt: $nameAlt,
                        flags: $flags,
                        justification: $justification,
                        value: $value,
                        stateOption: $stateOption,
                    );
                    break;
                case 'Choice':
                    /** @var string|null $valueDefault */
                    $valueDefault = $parts['FieldValueDefault'] ?? null;
                    $field = new Choice(
                        name: $name,
                        nameAlt: $nameAlt,
                        flags: $flags,
                        justification: $justification,
                        value: $value,
                        valueDefault: $valueDefault,
                        stateOption: $stateOption,
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
     * @param string[] $pdfFilePaths Filepath to PDF files.
     * @param string[] $pageRanges An array of page ranges specifying the pages to concatenate.
     *
     * @return string PDF concatenated from input PDFs
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function cat(array $pdfFilePaths, array $pageRanges = []): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath];
        array_push($command, ...$pdfFilePaths);
        $command[] = 'cat';
        array_push($command, ...$pageRanges);
        $command[] = 'output';
        $command[] = '-';

        return $this->runCommand($command);
    }

    /**
     * Reads a single input PDF file and reports its metadata, bookmarks (outlines), page metrics (media, rotation and labels) and other data.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param bool $utf8 Output is encoded as UTF-8
     *
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function dumpData(string $pdfFilePath, bool $utf8 = true): Report
    {
        $executablePath = $this->getExecutablePath();

        $operation = $utf8 ? 'dump_data_utf8' : 'dump_data';

        $command = [$executablePath, $pdfFilePath, $operation];

        $output = $this->runCommand($command);

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
                ++$infoCount;

                continue;
            }

            if ('BookmarkBegin' === $line) {
                $currentSection = 'bookmark';
                ++$bookmarkCount;

                continue;
            }

            if ('PageMediaBegin' === $line) {
                $currentSection = 'pageMedia';
                ++$pageMediaCount;

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
                pageNumber: (int) $bookmarkData['BookmarkPageNumber'],
            );
        }

        $pageMedias = [];
        foreach ($pageMediasData as $pageMediaData) {
            $rectArr = explode(' ', $pageMediaData['PageMediaRect']);
            $rect = [];
            foreach ($rectArr as $rectValue) {
                $rect[] = (int) $rectValue;
            }

            $dimensionsArr = explode(' ', $pageMediaData['PageMediaDimensions']);
            $dimensions = [];
            foreach ($dimensionsArr as $dimensionValue) {
                $dimensions[] = (int) $dimensionValue;
            }

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
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function generateFdf(string $pdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'generate_fdf', 'output', '-'];

        return $this->runCommand($command);
    }

    /**
     * Splits a single input PDF document into individual pages. Also creates a report named doc_data.txt which is the same as the output from dump_data.
     *
     * @param string $pdfFilePath Filepath to a PDF file
     * @param string|null $outputDir Output directory (default: system temp dir)
     * @param string $pageNamePrefixOutput Prefix for the output page filenames (default: page_)
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function burst(string $pdfFilePath, string $outputDir = null, string $pageNamePrefixOutput = 'page_'): void
    {
        $executablePath = $this->getExecutablePath();

        $outputDir ??= sys_get_temp_dir();

        $command = [$executablePath, $pdfFilePath, 'burst', 'output', sprintf('%s/%s%%02d.pdf', $outputDir, $pageNamePrefixOutput)];

        $this->runCommand($command);
    }

    /**
     * Removes compression from a PDF file to create an uncompressed version.
     *
     * @param string $pdfFilePath Filepath to the input PDF file to be uncompressed.
     *
     * @return string Uncompressed PDF content.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function uncompress(string $pdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'output', '-', 'uncompress'];

        return $this->runCommand($command);
    }

    /**
     * Compresses the specified PDF file and returns the compressed output.
     *
     * @param string $pdfFilePath The file path of the PDF to be compressed.
     *
     * @return string The compressed content of the PDF file.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function compress(string $pdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'output', '-', 'compress'];

        return $this->runCommand($command);
    }

    /**
     * Repairs the specified PDF file and returns the repaired output.
     *
     * @param string $pdfFilePath The file path of the PDF to be repaired.
     *
     * @return string The repaired content of the PDF file.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function repair(string $pdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'output', '-'];

        return $this->runCommand($command);
    }

    /**
     * Applies a background PDF to the specified PDF file and returns the resulting output.
     *
     * @param string $pdfFilePath The file path of the PDF to which the background is to be applied.
     * @param string $backgroundPdfFilePath The file path of the background PDF to be used.
     *
     * @return string The resulting output of the PDF with the background applied.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function background(string $pdfFilePath, string $backgroundPdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'background', $backgroundPdfFilePath, 'output', '-'];

        return $this->runCommand($command);
    }

    /**
     * Applies a stamp to the specified PDF file and returns the stamped output.
     *
     * @param string $pdfFilePath The file path of the PDF to which the stamp will be applied.
     * @param string $stampPdfFilePath The file path of the PDF containing the stamp to be applied.
     *
     * @return string The stamped content of the PDF file.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function stamp(string $pdfFilePath, string $stampPdfFilePath): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'stamp', $stampPdfFilePath, 'output', '-'];

        return $this->runCommand($command);
    }

    /**
     * Rotates specified pages of the provided PDF file and returns the output.
     *
     * @param string $pdfFilePath The file path of the PDF to be processed.
     * @param array<string> $pageRanges An array of page ranges specifying the pages to rotate.
     *
     * @return string The modified content of the PDF file with rotated pages.
     *
     * @throws ProcessFailedException If the process execution is unsuccessful.
     */
    public function rotate(string $pdfFilePath, array $pageRanges): string
    {
        $executablePath = $this->getExecutablePath();

        $command = [$executablePath, $pdfFilePath, 'rotate'];
        array_push($command, ...$pageRanges);
        $command[] = 'output';
        $command[] = '-';

        return $this->runCommand($command);
    }
}
