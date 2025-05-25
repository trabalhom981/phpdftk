PHPDFTK
=======

[![CI](https://github.com/qdequippe/phpdftk/actions/workflows/ci.yml/badge.svg)](https://github.com/qdequippe/phpdftk/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/qdequippe/phpdftk/v/stable)](https://packagist.org/packages/qdequippe/phpdftk)
[![License](https://poser.pugx.org/qdequippe/phpdftk/license)](https://packagist.org/packages/qdequippe/phpdftk)
[![codecov](https://codecov.io/gh/qdequippe/phpdftk/graph/badge.svg?token=MLOR43BV97)](https://codecov.io/gh/qdequippe/phpdftk)

PHP wrapper for [PDFtk](https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/).

Inspired by [Kevsoft.PDFtk](https://github.com/kevbite/Kevsoft.PDFtk) and [pypdftk](https://github.com/revolunet/pypdftk).

## Installation

To install, use composer:

```
composer require qdequippe/phpdftk
```

## Requirements

- [PDFtk Server](https://www.pdflabs.com/tools/pdftk-server/) installed (see below installation based on your OS)

### Linux (Ubuntu)

See https://doc.ubuntu-fr.org/pdftk (similar on other distributions).

```
apt-get install pdftk
```

### MacOS

```
brew install pdftk-java
```

## Usage

For example, to fill input PDF’s form fields with the data from an FDF file or XFDF file.

```php
$pdftk = new \Qdequippe\PHPDFtk\PDFtk();

$filledPdf = $pdftk->fillForm(
    pdfFilePath: 'path_to_pdf.pdf',
    formDataFilePath: 'path_to_form_data.fdf', // or XFDF file,
    flatten: true,
);

// $filledPdf = PDF filled with form data
```

## Testing

```
./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](./LICENSE) for more information.

## Resources

- https://doc.ubuntu-fr.org/pdftk
- https://www.pdflabs.com
- https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit
