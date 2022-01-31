<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use PhpCfdi\Credentials\Tests\TestCase;
use RuntimeException;
use UnexpectedValueException;

class LocalFileOpenTraitTest extends TestCase
{
    public function testOpenWithFlatPath(): void
    {
        $filename = $this->filePath('FIEL_AAA010101AAA/password.txt');
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->assertStringEqualsFile($filename, $specimen->localFileOpen($filename));
    }

    public function testOpenWithFileSchemeOnPath(): void
    {
        $filename = 'file://' . $this->filePath('FIEL_AAA010101AAA/password.txt');
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->assertStringEqualsFile($filename, $specimen->localFileOpen($filename));
    }

    public function testOpenEmptyFile(): void
    {
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The file to open is empty');
        $specimen->localFileOpen('');
    }

    public function testOpenWithDubleSchemeOnPath(): void
    {
        $filename = 'file://http://example.com/index.htm';
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid scheme to open file');
        $specimen->localFileOpen($filename);
    }

    public function testOpenWithDirectory(): void
    {
        $filename = __DIR__;
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File content is empty');
        $specimen->localFileOpen($filename);
    }

    public function testOpenWithNonExistentPath(): void
    {
        $filename = __DIR__ . '/nonexistent';
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to locate the file to open');
        $specimen->localFileOpen($filename);
    }

    /**
     * This test ensures that the correct exception is thrown
     *
     * @param string $filename
     * @testWith ["c:/certs/file.txt"]
     *           ["file://c:/certs/file.txt"]
     *           ["c:\\certs\\file.txt"]
     *           ["file://c:\\certs\\file.txt"]
     */
    public function testOpenWithWindowsPath(string $filename): void
    {
        $specimen = new LocalFileOpenTraitSpecimen();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to locate the file to open');
        $specimen->localFileOpen($filename);
    }
}
