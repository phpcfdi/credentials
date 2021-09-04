<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use Exception;
use Throwable;

require __DIR__ . '/../../vendor/autoload.php';

exit(call_user_func(
    function ($cmd, $cerFile): int {
        try {
            if (in_array($cerFile, ['-h', '--help', ''], true)) {
                echo 'Show certificate information', PHP_EOL;
                echo "Syntax: $cmd certificate-file", PHP_EOL;
                if ('' === $cerFile) {
                    throw new Exception('No certificate file was set');
                }
                return 0;
            }
            $certificate = Certificate::openFile($cerFile);
            echo json_encode([
                'file' => $cerFile,
                'rfc' => $certificate->rfc(),
                'serial' => $certificate->serialNumber()->bytes(),
                'valid since' => $certificate->validFromDateTime()->format('c'),
                'valid until' => $certificate->validToDateTime()->format('c'),
                'legalname' => $certificate->legalName(),
                'satType' => $certificate->satType()->value(),
                'parsed' => $certificate->parsed(),
            ], JSON_PRETTY_PRINT), PHP_EOL;
            return 0;
        } catch (Throwable $exception) {
            file_put_contents('php://stderr', 'ERROR: ' . $exception->getMessage() . PHP_EOL, FILE_APPEND);
            return 1;
        }
    },
    $argv[0] ?? basename(__FILE__),
    $argv[1] ?? ''
));
