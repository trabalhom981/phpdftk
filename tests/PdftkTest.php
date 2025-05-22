<?php

namespace Qdequippe\PHPDFtk\Tests;

use PHPUnit\Framework\TestCase;
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
}
