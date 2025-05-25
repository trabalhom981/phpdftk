<?php

declare(strict_types=1);

namespace Qdequippe\PHPDFtk\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Qdequippe\PHPDFtk\Exception\ProcessFailedException;
use Qdequippe\PHPDFtk\Field\Type;
use Qdequippe\PHPDFtk\PDFtk;

#[CoversClass(PDFtk::class)]
final class PDFtkTest extends TestCase
{
    public function testProcessFailed(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Assert
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessageMatches('/^Error: Unable to find file/');

        // Act
        $pdftk->fillForm(
            pdfFilePath: 'non-existing-file.pdf',
            formDataFilePath: 'non-existing-file.fdf',
        );
    }

    public function testFormFill(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        $inputFilePath = __DIR__ . '/data/form.pdf';
        $formDataFilePath = __DIR__ . '/data/form_date.xfdf';

        // Act
        $data = $pdftk->fillForm(
            pdfFilePath: $inputFilePath,
            formDataFilePath: $formDataFilePath,
        );

        // Assert
        $this->assertStringEqualsFile(__DIR__ . '/data/output.pdf', $data);
    }

    public function testDumpDataFields(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        $inputFilePath = __DIR__ . '/data/form.pdf';

        // Act
        $fields = $pdftk->dumpDataFields(
            pdfFilePath: $inputFilePath,
        );

        // Assert
        $this->assertCount(7, $fields);
        $countButton = 0;
        $countText = 0;
        $countChoice = 0;

        foreach ($fields as $field) {
            switch ($field->getType()) {
                case Type::Choice:
                    ++$countChoice;
                    break;
                case Type::Text:
                    ++$countText;
                    break;
                case Type::Button:
                    ++$countButton;
                    break;
            }
        }

        $this->assertEquals(3, $countButton);
        $this->assertEquals(3, $countText);
        $this->assertEquals(1, $countChoice);
    }

    public function testCat(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $result = $pdftk->cat(pdfFilePaths: [__DIR__ . '/data/form.pdf', __DIR__ . '/data/sample.pdf']);

        // Assert
        $filename = sys_get_temp_dir() . '/result.pdf';
        file_put_contents($filename, $result);
        $report = $pdftk->dumpData($filename);

        $this->assertSame(2, $report->getNumberOfPages());
    }

    public function testExtractSpecificPagesFromSingleFile(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $result = $pdftk->cat(
            pdfFilePaths: [__DIR__ . '/data/sample-multi-pages.pdf'],
            pageRanges: ['1', '3']
        );

        // Assert
        $filename = sys_get_temp_dir() . '/result_cat_multiple_page.pdf';
        file_put_contents($filename, $result);
        $report = $pdftk->dumpData($filename);

        $this->assertSame(2, $report->getNumberOfPages());
    }

    public function testDumpData(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $report = $pdftk->dumpData(__DIR__ . '/data/sample.pdf');

        // Assert
        $this->assertSame(1, $report->getNumberOfPages());
        $this->assertSame('f7d77b3d22b9f92829d49ff5d78b8f28', $report->getPdfID1());
        $this->assertSame('f7d77b3d22b9f92829d49ff5d78b8f28', $report->getPdfID0());
        $this->assertCount(4, $report->getInfos());
        $this->assertCount(1, $report->getBookmarks());
        $this->assertCount(1, $report->getPageMedias());
    }

    public function testGenerateFdf(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $fdf = $pdftk->generateFdf(__DIR__ . '/data/form.pdf');

        // Assert
        $this->assertStringEqualsFile(__DIR__ . '/data/expected.fdf', $fdf);
    }

    public function testBurst(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $pdftk->burst(__DIR__ . '/data/sample-multi-pages.pdf', );

        // Assert
        $this->assertFileExists(sys_get_temp_dir() . '/page_01.pdf');
        $this->assertFileExists(sys_get_temp_dir() . '/page_02.pdf');
        $this->assertFileExists(sys_get_temp_dir() . '/page_03.pdf');
    }

    public function testUncompress(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $uncompressed = $pdftk->uncompress(__DIR__ . '/data/sample.pdf');

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/sample_uncompressed.pdf',
            $uncompressed,
        );
    }

    public function testCompress(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $compressed = $pdftk->compress(__DIR__ . '/data/sample.pdf');

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/sample_compressed.pdf',
            $compressed,
        );
    }

    public function testRepair(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $repaired = $pdftk->repair(__DIR__ . '/data/sample.pdf');

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/sample_repaired.pdf',
            $repaired,
        );
    }

    public function testBackground(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $result = $pdftk->background(
            __DIR__ . '/data/form.pdf',
            __DIR__ . '/data/sample.pdf'
        );

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/background.pdf',
            $result,
        );
    }

    public function testStamp(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $result = $pdftk->stamp(
            __DIR__ . '/data/form.pdf',
            __DIR__ . '/data/sample.pdf'
        );

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/stamp.pdf',
            $result,
        );
    }

    public function testRotate(): void
    {
        // Arrange
        $pdftk = new PDFtk();

        // Act
        $result = $pdftk->rotate(
            __DIR__ . '/data/sample-multi-pages.pdf',
            ['1east', '2-end'],
        );

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/rotate.pdf',
            $result,
        );
    }
}
