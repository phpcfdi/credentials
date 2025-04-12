# phpcfdi/credentials

[![Source Code][badge-source]][source]
[![Packagist PHP Version Support][badge-php-version]][php-version]
[![Discord][badge-discord]][discord]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Reliability][badge-reliability]][reliability]
[![Maintainability][badge-maintainability]][maintainability]
[![Code Coverage][badge-coverage]][coverage]
[![Violations][badge-violations]][violations]
[![Total Downloads][badge-downloads]][downloads]

> Library to use eFirma (fiel) and CSD (sellos) from SAT

:us: The documentation of this project is in spanish as this is the natural language for intended audience.

:mexico: La documentación del proyecto está en español porque ese es el lenguaje principal de los usuarios.

Esta librería ha sido creada para poder trabajar con los archivos CSD y FIEL del SAT. De esta forma,
se simplifica el proceso de firmar, verificar firma y obtener datos particulares del archivo de certificado
así como de la llave pública.

- El CSD (Certificado de Sello Digital) es utilizado para firmar Comprobantes Fiscales Digitales.

- La FIEL (o eFirma) es utilizada para firmar electrónicamente documentos (generalmente usando XML-SEC) y
  está reconocida por el gobierno mexicano como una manera de firma legal de una persona física o moral.

Con esta librería no es necesario convertir los archivos generados por el SAT a otro formato,
se pueden utilizar tal y como el SAT los entrega.

## Instalación

Usa [composer](https://getcomposer.org/)

```shell
composer require phpcfdi/credentials
```

## Ejemplo básico de uso

```php
<?php declare(strict_types=1);

$cerFile = 'fiel/certificado.cer';
$pemKeyFile = 'fiel/private-key.key';
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

## Acerca de los archivos de certificado y llave privada

Los archivos de certificado vienen en formato `X.509 DER` y los de llave privada en formato `PKCS#8 DER`.
Ambos formatos no se pueden interpretar directamente en PHP (con `ext-openssl`), sin embargo, sí lo pueden hacer
en el formato compatible [`PEM`](https://en.wikipedia.org/wiki/Privacy-Enhanced_Mail).

Esta librería tiene la capacidad de hacer esta conversión internamente (sin `openssl`), pues solo consiste en codificar
a `base64`, en renglones de 64 caracteres y con cabeceras específicas para certificado y llave privada.

De esta forma, para usar el certificado `AAA010101AAA.cer` o la llave privada `AAA010101AAA.key` provistos por
el SAT, no es necesario convertirlos con `openssl` y la librería los detectará correctamente.

### Crear un objeto de certificado `Certificate`

El objeto `Certificate` no se creará si contiene datos no válidos.

El SAT entrega el certificado en formato `X.509 DER`, por lo que internamente se puede convertir a `X.509 PEM`.
También es frecuente usar el formato `X.509 DER base64`, por ejemplo, en el atributo `Comprobante@Certificado`
o en las firmas XML, por este motivo, los formatos soportados para crear un objeto `Certificate` son
`X.509 DER`, `X.509 DER base64` y `X.509 PEM`.

- Para abrir usando un archivo local: `$certificate = Certificate::openFile($filename);`
- Para abrir usando una cadena de caracteres: `$certificate = new Certificate($content);`
  - Si `$content` es un certificado en formato `X.509 PEM` con cabeceras ese se utiliza.
  - Si `$content` está totalmente en `base64`, se interpreta como `X.509 DER base64` y se formatea a `X.509 PEM`
  - En otro caso, se interpreta como formato `X.509 DER`, por lo que se formatea a `X.509 PEM`.

### Crear un objeto de llave privada `PrivateKey`

El objeto `PrivateKey` no se creará si contiene datos no válidos.

En SAT entrega la llave en formato `PKCS#8 DER`, por lo que internamente se puede convertir a `PKCS#8 PEM`
(con la misma contraseña) y usarla desde PHP.

Una vez abierta la llave también se puede cambiar o eliminar la contraseña, creando así un nuevo objeto `PrivateKey`.

- Para abrir usando un archivo local: `$key = PrivateKey::openFile($filename, $passPhrase);`
- Para abrir usando una cadena de caracteres: `$key = new PrivateKey($content, $passPhrase);`
  - Si `$content` es una llave privada en formato `PEM` (`PKCS#8` o `PKCS#5`) se utiliza.
  - En otro caso, se interpreta como formato `PKCS#8 DER`, por lo que se formatea a `PKCS#8 PEM`.

Notas de tratamiento de archivos `DER`:

- Al convertir `PKCS#8 DER` a `PKCS#8 PEM` se determina si es una llave encriptada si se estableció
  una contraseña, si no se estableció se tratará como una llave plana (no encriptada).
- No se sabe reconocer de forma automática si se trata de un archivo `PKCS#5 DER` por lo que este
  tipo de llave se deben convertir *manualmente* antes de intentar abrirlos, su cabecera es `RSA PRIVATE KEY`.
- A diferencia de los certificados que pueden interpretar un formato `DER base64`, la lectura de llave
  privada no hace esta distinción, si desea trabajar con un formato sin caracteres especiales use `PEM`.

Para entender más de los formatos de llaves privadas se puede consultar la siguiente liga:
<https://github.com/kjur/jsrsasign/wiki/Tutorial-for-PKCS5-and-PKCS8-PEM-private-key-formats-differences>

## Acerca de los números de serie

Los certificados contienen un número de serie expresado en notación hexadecimal, por ejemplo, el número
de serie `27 2B` se refiere al certificado número `10027` expresado en decimal.

Para el SAT, sin embargo, se reconoce el número de serie no como el estándar en hexadecimal.
El SAT pide que el número de serie reflejado sea **la expresión hexadecimal convertida a ASCII**.
Luego entonces, el certificado con número de serie `3330303031303030303030333030303233373038`
lo identifica como `30001000000300023708`.

Esta práctica del SAT no es estándar, y no es comúnmente observada. Sin embargo, así ha decidido que se
interpreten el dato de "número de serie" referido en sus certificados emitidos, por ejemplo en el atributo
`Comprobante@NoCertificado`.

Como ejemplo contrario: En el firmado de documentos XML utilizado en el servicio web de descarga masiva,
sí se utiliza la notación decimal (el número hexadecimal convertido a decimal), en lugar de la notación de bytes.

La notación de bytes es problemática porque no todos los caracteres son imprimibles o
cuentan una representación gráfica. La notación hexadecimal es ligeramente problemática
porque tiene muchas variantes como el uso de mayúsculas y minúsculas o el prefijo `0x`.
La notación decimal no tiene problema, se trata simplemente de un entero muy grande,
tan grande que debe tratarse como una cadena de caracteres.

Espero que en algún futuro el SAT reconsidere y utilice una notación decimal, para referirnos al número de serie.

## Leer y exportar archivos PFX

Esta librería soporta obtener el objeto `Credential` desde un archivo PFX (PKCS #12) y vicerversa.

Para exportar el archivo PFX:

```php
<?php declare(strict_types=1);

use PhpCfdi\Credentials\Pfx\PfxExporter;

$credential = PhpCfdi\Credentials\Credential::openFiles(
  'certificate/certificado.cer',
  'certificate/private-key.key',
  'password'
);

$pfxExporter = new PfxExporter($credential);

// crea el binary string usando la contraseña dada
$pfxContents = $pfxExporter->export('pfx-passphrase');

// guarda el archivo pfx a la ruta local dada usando la contraseña dada
$pfxExporter->exportToFile('credential.pfx', 'pfx-passphrase');
```

Para leer el archivo PFX y obtener un objeto `Credential`:

```php
<?php declare(strict_types=1);

use PhpCfdi\Credentials\Pfx\PfxReader;

$pfxReader = new PfxReader();

// crea un objeto Credential dado el contenido de un archivo pfx
$credential = $pfxReader->createCredentialFromContents('contenido-del-archivo', 'pfx-passphrase');

// crea un objeto Credential dada la ruta local de un archivo pfx
$credential = $pfxReader->createCredentialFromFile('pfxFilePath', 'pfx-passphrase');
```

## Compatibilidad

Esta librería se mantendrá compatible con al menos la versión con
[soporte activo de PHP](https://www.php.net/supported-versions.php) más reciente.

También utilizamos [Versionado Semántico 2.0.0](docs/SEMVER.md) por lo que puedes usar esta librería
sin temor a romper tu aplicación.

## Contribuciones

Las contribuciones con bienvenidas. Por favor lee [CONTRIBUTING][] para más detalles
y recuerda revisar el archivo de tareas pendientes [TODO][] y el archivo [CHANGELOG][].

## Copyright and License

The `phpcfdi/credentials` library is copyright © [PhpCfdi](https://www.phpcfdi.com/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/phpcfdi/credentials/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/phpcfdi/credentials/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/phpcfdi/credentials/blob/main/docs/TODO.md

[source]: https://github.com/phpcfdi/credentials
[php-version]: https://packagist.org/packages/phpcfdi/credentials
[discord]: https://discord.gg/aFGYXvX
[release]: https://github.com/phpcfdi/credentials/releases
[license]: https://github.com/phpcfdi/credentials/blob/main/LICENSE
[build]: https://github.com/phpcfdi/credentials/actions/workflows/build.yml?query=branch:main
[reliability]:https://sonarcloud.io/component_measures?id=phpcfdi_credentials&metric=Reliability
[maintainability]: https://sonarcloud.io/component_measures?id=phpcfdi_credentials&metric=Maintainability
[coverage]: https://sonarcloud.io/component_measures?id=phpcfdi_credentials&metric=Coverage
[violations]: https://sonarcloud.io/project/issues?id=phpcfdi_credentials&resolved=false
[downloads]: https://packagist.org/packages/phpcfdi/credentials

[badge-source]: https://img.shields.io/badge/source-phpcfdi/credentials-blue?logo=github
[badge-discord]: https://img.shields.io/discord/459860554090283019?logo=discord
[badge-php-version]: https://img.shields.io/packagist/php-v/phpcfdi/credentials?logo=php
[badge-release]: https://img.shields.io/github/release/phpcfdi/credentials?logo=git
[badge-license]: https://img.shields.io/github/license/phpcfdi/credentials?logo=open-source-initiative
[badge-build]: https://img.shields.io/github/actions/workflow/status/phpcfdi/credentials/build.yml?branch=main&logo=github-actions
[badge-reliability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_credentials&metric=reliability_rating
[badge-maintainability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_credentials&metric=sqale_rating
[badge-coverage]: https://img.shields.io/sonar/coverage/phpcfdi_credentials/main?logo=sonarcloud&server=https%3A%2F%2Fsonarcloud.io
[badge-violations]: https://img.shields.io/sonar/violations/phpcfdi_credentials/main?format=long&logo=sonarcloud&server=https%3A%2F%2Fsonarcloud.io
[badge-downloads]: https://img.shields.io/packagist/dt/phpcfdi/credentials?logo=packagist
