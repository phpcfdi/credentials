# Ejemplo de creación de una credencial con verificaciones previas

Al momento de crear una *credencial* (`Credential`), es posible que queramos verificar la creación con
una lista detallada de errores. Si bien esto puede significar una doble verificación, es posible implementarlo
con el siguiente código de ejemplo:

```php
<?php

declare(strict_types=1);

use Exception;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\SatTypeEnum;
use PhpCfdi\Credentials\PrivateKey;
use Throwable;

function createCredential(
    string $certificateFile,
    string $privateKeyFile,
    string $passPhrase,
    string $expectedRfc,
     SatTypeEnum $expectedType
): Credential {
    try {
        $certificate = Certificate::openFile($certificateFile);
    } catch (Throwable $exception) {
        throw new Exception('El archivo de certificado no se pudo abrir.', 0, $exception);
    }
    if ($certificate->rfc() !== $expectedRfc) {
        throw new Exception(sprintf('El certificado no pertenece al RFC %s.', $expectedRfc));
    }
    if ($certificate->validOn()) {
        throw new Exception('El certificado no es vigente en este momento.');
    }
    if ($expectedType->isFiel() && ! $certificate->satType()->isFiel()) {
        throw new Exception('El certificado no corresponde a una eFirma/FIEL.');
    }
    if ($expectedType->isCsd() && ! $certificate->satType()->isCsd()) {
        throw new Exception('El certificado no corresponde a un CSD.');
    }

    try {
        $privateKey = PrivateKey::openFile($privateKeyFile, $passPhrase);
    } catch (Throwable $exception) {
        throw new Exception('El archivo de llave privada no se pudo abrir, el archivo o la contraseña son incorrectos.', 0, $exception);
    }
    if (! $privateKey->belongsTo($certificate)) {
        throw new Exception('La llave privada no es par del certificado.');
    }

    return new Credential($certificate, $privateKey);
}
```
