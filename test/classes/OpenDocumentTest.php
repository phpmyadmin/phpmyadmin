<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use DateTime;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\ZipExtension;
use ZipArchive;

use function file_put_contents;
use function tempnam;
use function unlink;

/**
 * @covers \PhpMyAdmin\OpenDocument
 * @requires extension zip
 */
class OpenDocumentTest extends AbstractTestCase
{
    public function testCreateDocument(): void
    {
        $document = OpenDocument::create(
            'application/vnd.oasis.opendocument.text',
            '<data>'
        );
        self::assertNotFalse($document);

        $tmpFile = tempnam('./', 'open-document-test');
        self::assertNotFalse($tmpFile);
        self::assertNotFalse(file_put_contents($tmpFile, $document), 'The temp file should be written');

        $zipExtension = new ZipExtension(new ZipArchive());
        self::assertSame([
            'error' => '',
            'data' => 'application/vnd.oasis.opendocument.text',
        ], $zipExtension->getContents($tmpFile));

        self::assertSame([
            'error' => '',
            'data' => '<data>',
        ], $zipExtension->getContents($tmpFile, '/content\.xml/'));

        $dateTimeCreation = (new DateTime())->format('Y-m-d\TH:i');
        self::assertStringContainsString(
            // Do not use a full version or seconds could be out of sync and cause flaky test failures
            '<meta:creation-date>' . $dateTimeCreation,
            $zipExtension->getContents($tmpFile, '/meta\.xml/')['data']
        );

        self::assertSame(5, $zipExtension->getNumberOfFiles($tmpFile));
        // Unset to close any file that were left open.
        unset($zipExtension);
        self::assertTrue(unlink($tmpFile));
    }
}
