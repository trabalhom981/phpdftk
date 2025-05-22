<?php

namespace Qdequippe\PHPDFtk\Tests;

use PHPUnit\Framework\TestCase;
use Qdequippe\PHPDFtk\Field\Type;
use Qdequippe\PHPDFtk\Pdftk;

final class PdftkTest extends TestCase
{
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
}
