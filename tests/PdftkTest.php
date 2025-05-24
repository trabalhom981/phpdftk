<?php

namespace Qdequippe\PHPDFtk\Tests;

use PHPUnit\Framework\TestCase;
use Qdequippe\PHPDFtk\Field\Type;
use Qdequippe\PHPDFtk\Pdftk;
use Qdequippe\PHPDFtk\ProcessFailedException;

final class PdftkTest extends TestCase
{
    public function testProcessFailed(): void
    {
        // Arrange
        $pdftk = new Pdftk();

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
        $pdftk = new Pdftk();

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
        $pdftk = new Pdftk();

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
                    $countChoice++;
                    break;
                case Type::Text:
                    $countText++;
                    break;
                case Type::Button:
                    $countButton++;
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
        $pdftk = new Pdftk();

        // Act
        $result = $pdftk->cat(__DIR__ . '/data/form.pdf', __DIR__ . '/data/sample.pdf');

        // Assert
        $filename = sys_get_temp_dir() . '/result.pdf';
        file_put_contents($filename, $result);
        $report = $pdftk->dumpData($filename);

        $this->assertSame(2, $report->getNumberOfPages());
    }

    public function testDumpData(): void
    {
        // Arrange
        $pdftk = new Pdftk();

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
        $pdftk = new Pdftk();

        // Act
        $fdf = $pdftk->generateFdf(__DIR__ . '/data/form.pdf');

        // Assert
        $this->assertStringEqualsFile(__DIR__ . '/data/expected.fdf', $fdf);
    }

    public function testBurst(): void
    {
        // Arrange
        $pdftk = new Pdftk();

        // Act
        $pdftk->burst(__DIR__ . '/data/sample-multi-pages.pdf', 'build');

        // Assert
        $this->assertFileExists('build/page_01.pdf');
        $this->assertFileExists('build/page_02.pdf');
        $this->assertFileExists('build/page_03.pdf');
    }

    public function testUncompress(): void
    {
        // Arrange
        $pdftk = new Pdftk();

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
        $pdftk = new Pdftk();

        // Act
        $compressed = $pdftk->compress(__DIR__ . '/data/sample.pdf');

        // Assert
        $this->assertStringEqualsFile(
            __DIR__ . '/data/sample_compressed.pdf',
            $compressed,
        );
    }
}
