<?php

namespace Qdequippe\Phpdftk\Tests;

use PHPUnit\Framework\TestCase;
use Qdequippe\Phpdftk\Pdftk;

final class PdftkTest extends TestCase
{
    public function testFormFill(): void
    {
        // Arrange
        $pdftk = new Pdftk();
        $inputFilePath = __DIR__.'/data/form.pdf';
        $formDataFilePath = __DIR__.'/data/form_date.xfdf';
        $outputFilePath = sys_get_temp_dir().'/phppdf-output.pdf';
        $expectedOutput = file_get_contents(__DIR__.'/data/output.pdf');

        // Act
        $pdftk->fillForm(
            inputFilePath: $inputFilePath,
            formDataFilePath: $formDataFilePath,
            outputFilePath: $outputFilePath,
        );

        // Assert
        $this->assertFileExists($outputFilePath);
        $this->assertStringEqualsFile($outputFilePath, $expectedOutput);
    }
}
