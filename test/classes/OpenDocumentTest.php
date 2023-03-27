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
        $document = OpenDocument::create('application/vnd.oasis.opendocument.text', '<data>');
        $this->assertNotFalse($document);

        $tmpFile = tempnam('./', 'open-document-test');
        $this->assertNotFalse($tmpFile);
        $this->assertNotFalse(file_put_contents($tmpFile, $document), 'The temp file should be written');

        $zipExtension = new ZipExtension(new ZipArchive());
        $this->assertSame([
            'error' => '',
            'data' => 'application/vnd.oasis.opendocument.text',
        ], $zipExtension->getContents($tmpFile));

        $this->assertSame(['error' => '', 'data' => '<data>'], $zipExtension->getContents($tmpFile, '/content\.xml/'));

        $dateTimeCreation = (new DateTime())->format('Y-m-d\TH:i');
        $this->assertStringContainsString(
            // Do not use a full version or seconds could be out of sync and cause flaky test failures
            '<meta:creation-date>' . $dateTimeCreation,
            $zipExtension->getContents($tmpFile, '/meta\.xml/')['data'],
        );

        $this->assertSame(5, $zipExtension->getNumberOfFiles($tmpFile));
        // Unset to close any file that were left open.
        unset($zipExtension);
        $this->assertTrue(unlink($tmpFile));
    }
}
