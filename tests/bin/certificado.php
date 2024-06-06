<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use Exception;
use Throwable;

require __DIR__ . '/../../vendor/autoload.php';

exit(call_user_func(
    function (string $cmd, string $cerFile): int {
        if (in_array($cerFile, ['-h', '--help'], true)) {
            echo 'Show certificate information', PHP_EOL;
            echo "Syntax: $cmd certificate-file", PHP_EOL;
            return 0;
        }
        try {
            if ('' === $cerFile) {
                throw new Exception('No certificate file was set');
            }
            $certificate = Certificate::openFile($cerFile);
            $serialNumber = $certificate->serialNumber();
            echo json_encode([
                'file' => $cerFile,
                'rfc' => $certificate->rfc(),
                'serial' => [
                    'hexadecimal' => $serialNumber->hexadecimal(),
                    'decimal' => $serialNumber->decimal(),
                    'bytes' => $serialNumber->bytesArePrintable() ? $serialNumber->bytes() : '',
                ],
                'valid since' => $certificate->validFromDateTime()->format('c'),
                'valid until' => $certificate->validToDateTime()->format('c'),
                'legalname' => $certificate->legalName(),
                'satType' => $certificate->satType()->value(),
                'parsed' => $certificate->parsed(),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), PHP_EOL;
            return 0;
        } catch (Throwable $exception) {
            file_put_contents('php://stderr', 'ERROR: ' . $exception->getMessage() . PHP_EOL, FILE_APPEND);
            // file_put_contents('php://stderr', print_r($exception, true) . PHP_EOL, FILE_APPEND);
            return 1;
        }
    },
    $argv[0] ?? basename(__FILE__),
    $argv[1] ?? ''
));
