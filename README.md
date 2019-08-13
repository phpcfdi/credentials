# phpcfdi/credentials

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Scrutinizer][badge-quality]][quality]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]

> Library to use eFirma (fiel) and CSD (sellos) from SAT

:us: The documentation of this project is in spanish as this is the natural language for intented audience.

:mexico: La documentación del proyecto está en español porque ese es el lenguaje principal de los usuarios.

Esta librería ha sido creada para poder trabajar con los archivos CSD y FIEL del SAT. De esta forma,
se simplifica el proceso de firmar, verificar firma y obtener datos particulares del archivo de certificado
así como de la llave pública.

* El CSD (Certificado de Sello Digital) es utilizado para firmar Comprobantes Fiscales Digitales.

* La FIEL (o eFirma) es utilizada para firmar electrónicamente documentos (generalmente usando XML-SEC) y
está reconocida por el gobierno mexicano como una manera de firma legal de una persona física o moral.


## Instalación

Usa [composer](https://getcomposer.org/)

```shell
composer require phpcfdi/credentials
```


## Ejemplo básico de uso

```php
<?php declare(strict_types=1);

$cerFile = 'fiel/certificado.cer'; // PEM o DER
$pemKeyFile = 'fiel/privatekey.pem'; // en formato PEM
$passPhrase = '12345678a'; // contraseña para abrir la llave privada

$fiel = PhpCfdi\Credentials\Credential::openFiles($cerFile, $pemKeyFile, $passPhrase);

$sourceString = 'texto a firmar';
// alias de privateKey/sign/verify
$signature = $fiel->sign($sourceString);
echo base64_encode($signature), PHP_EOL;

// alias de certificado/publicKey/verify
$verify = $fiel->verify($sourceString, $signature);
var_dump($verify); // bool(true)

// objeto certificado
$certificado = $fiel->certificate();
echo $certificado->rfc(), PHP_EOL; // el RFC del certificado
echo $certificado->legalName(), PHP_EOL; // el nombre del propietario del certificado
echo $certificado->branchName(), PHP_EOL; // el nombre de la sucursal (en CSD, en FIEL está vacía)
echo $certificado->serialNumber()->bytes(), PHP_EOL; // número de serie del certificado

```


## Compatilibilidad

Esta librería se mantendrá compatible con al menos la versión con
[soporte activo de PHP](http://php.net/supported-versions.php) más reciente.

También utilizamos [Versionado Semántico 2.0.0](https://semver.org/lang/es/) por lo que puedes usar esta librería
sin temor a romper tu aplicación.


## Contribuciones

Las contribuciones con bienvenidas. Por favor lee [CONTRIBUTING][] para más detalles
y recuerda revisar el archivo de tareas pendientes [TODO][] y el [CHANGELOG][].


## Copyright and License

The phpcfdi/credentials library is copyright © [Carlos C Soto](http://eclipxe.com.mx/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/phpcfdi/credentials/blob/master/CONTRIBUTING.md
[changelog]: https://github.com/phpcfdi/credentials/blob/master/docs/CHANGELOG.md
[todo]: https://github.com/phpcfdi/credentials/blob/master/docs/TODO.md

[source]: https://github.com/phpcfdi/credentials
[release]: https://github.com/phpcfdi/credentials/releases
[license]: https://github.com/phpcfdi/credentials/blob/master/LICENSE
[build]: https://travis-ci.org/phpcfdi/credentials?branch=master
[quality]: https://scrutinizer-ci.com/g/phpcfdi/credentials/
[coverage]: https://scrutinizer-ci.com/g/phpcfdi/credentials/code-structure/master/code-coverage
[downloads]: https://packagist.org/packages/phpcfdi/credentials

[badge-source]: http://img.shields.io/badge/source-phpcfdi/credentials-blue?style=flat-square
[badge-release]: https://img.shields.io/github/release/phpcfdi/credentials?style=flat-square
[badge-license]: https://img.shields.io/github/license/phpcfdi/credentials?style=flat-square
[badge-build]: https://img.shields.io/travis/phpcfdi/credentials/master?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/phpcfdi/credentials/master?style=flat-square
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/phpcfdi/credentials/master/src?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/phpcfdi/credentials?style=flat-square
