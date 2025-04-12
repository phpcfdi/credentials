# CHANGELOG

## Acerca de SemVer

Usamos [Versionado Semántico 2.0.0](SEMVER.md) por lo que puedes usar esta librería sin temor a romper tu aplicación.

## Cambios no liberados en una versión

Pueden aparecer cambios no liberados que se integran a la rama principal, pero no ameritan una nueva liberación de
versión, aunque sí su incorporación en la rama principal de trabajo. Generalmente, se tratan de cambios en el desarrollo.

## Listado de cambios

### Versión 1.3.0 2025-04-12

- Se mejoran las declaraciones de tipos.
- Se elimina la compatiblidad con PHP 7.3, PHP 7.4 y PHP 8.0.
- Se actualiza la acción de GitHub a `Update to SonarSource/sonarqube-scan-action@v5`.

### Versión 1.2.3 2025-03-30

Se corrigieron los problemas asociados a la compatibilidad de PHP 8.4.

- Se agregó explícitamente el operador de tipos *nullable* `?`.
- Se actualizó la dependencia `eclipxe/enum` a una versión compatible con PHP 8.4.

Se actualiza el año de licencia a 2025.

Se hicieron cambios menores al código sugeridos por PHPStan y PSalm.

Adicionalmente, se hacen los siguientes cambios internos:

- Se agrega PHP 8.4 a la matriz de pruebas del flujo de trabajo `build`.
- Se ejecuta la mayoría de los trabajos de los flujos de trabajo usando PHP 8.4.
- Se actualizan las herramientas de desarrollo.

### Versión 1.2.2 2024-06-06

Se corrigió el problema de no crear correctamente el número de serie cuando incluía caracteres en mayúsculas.
Anteriormente, se hacía una conversión a minúsculas, ahora se expresa en mayúsculas.

Se agrega el método `SerialNumber::bytesArePrintable(): bool` para identificar que el número de serie de un certificado
contiene solamente caracteres imprimibles en su representación como *bytes*, como en el caso de los números de serie
utilizados por el SAT.

Se refactorizan los métodos `SerialNumber::createFromBytes()` y `SerialNumber::bytes()` para usar las funciones
de PHP `bin2hex` y `hex2bin` respectivamente.

Se agrega documentación en el archivo `README.md` explicando la interpretación del número de serie como hexadecimal,
decimal y *bytes*. Así como el uso específico del SAT.

Se actualiza el año de licencia a 2024.

Se garantiza la compatibilidad con PHP 8.3.

Adicionalmente, se hacen los siguientes cambios internos:

- Se remueven los archivos `test/_files` de la detección de lenguaje de GitHub.
- En los flujos de trabajo de GitHub.
  - Se permite la ejecución manual.
  - Se agrega PHP 8.3 a la matriz de pruebas.
  - Se ejecutan los trabajos en PHP 8.3.
  - Se actualizan las acciones de GitHub a la versión 4.
  - En el trabajo `php-cs-fixer` se remueve la variable de entorno `PHP_CS_FIXER_IGNORE_ENV`.
- Se corrige `.php-cs-fixer.dist.php` sustituyendo `function_typehint_space` por `type_declaration_spaces`. 
- Se actualizan las herramientas de desarrollo.

### Versión 1.2.1 2023-05-24

PHPStan detectó un uso inapropiado de conversión de objeto a cadena de caracteres.
Esta conversión es innecesaria, por lo que se eliminó.

Se agregó información básica de cómo verificar un certificado emitido por el SAT usando OCSP.

Se actualizaron las herramientas de desarrollo.

### Versión 1.2.0 2023-02-24

Se agrega la funcionalidad para exportar (`PfxExporter`) y leer (`PfxReader`) una credencial con formato PKCS#12 (PFX).
Gracias `@celli33` por tu contribución.

Los siguientes cambios ya estaban incluidos en la rama principal:

#### Mantenimiento 2023-02-22

Los siguientes cambios son de mantenimiento:

- Se actualiza el año en el archivo de licencia. ¡Feliz 2023!
- Se agrega una prueba para comprobar certificados *Teletex*.
  Ver https://github.com/nodecfdi/credentials/commit/cd8f1827e06a5917c41940e82b8d696379362d5d.
- Se agrega un archivo de documentación: *Ejemplo de creación de una credencial con verificaciones previas*.
- Se corrige la insignia de construcción del proyecto `[bagde-build]`.
- Se sustituye la referencia `[homepage]` a `[project]` en el archivo `CONTRIBUTING.md`.
- Se actualizan los archivos de configuración de estilo de código.
- Se actualizan los flujos de trabajo de GitHub:
  - Los trabajos de PHP se ejecutan en la versión 8.2.
  - Se actualizan las acciones de GitHub a la versión 3.
  - Se agrega PHP 8.2 a la matriz de pruebas.
  - Se cambia la directiva `::set-output` a `$GITHUB_OUTPUT`.
  - Se corrige el trabajo `phpcs` eliminando las rutas fijas.
- Se actualizan las versiones de las herramientas de desarrollo.

### Versión 1.1.4 2022-01-31

- Se mejora la forma en como son procesados los tipos de datos del certificado.
- Se actualiza el año de licencia. ¡Feliz 2022!
- Se actualizan las herramientas de desarrollo, en especial PHPStan 1.4.4.
- Se hacen las correcciones a los problemas detectados por PHPStan.
- Se mejoran las pruebas y se incrementa la cobertura de código.
- Se actualiza el flujo de *CI* llevando los pasos a trabajos y se agrega PHP 8.1.
- Se actualiza el nombre del grupo de mantenedores de phpCfdi.
- Se agrega la plataforma SonarQube vía <https://sonarcloud.io>.
- Se elimina la integración con Scrutinizer CI. ¡Gracias Scrutinizer CI!

### Versión 1.1.3 2021-09-03

- La versión menor de PHP es 7.3.
- Se actualiza PHPUnit a 9.5.
- Se migra de Travis-CI a GitHub Workflows. Gracias Travis-CI.
- Se instalan las herramientas de desarrollo usando `phive` en lugar de `composer`.
- Se cambia la rama principal a `main`.
- Se actualiza el archivo de licencia al año 2021.
- Se cambia la documentación a español.

### Versión 1.1.2 2020-12-20

- Desde esta versión se soporta PHP 8.0. Se hicieron cambios porque en la nueva versión de PHP la librería
  `openssl` ya no devuelve recursos y se deprecaron las funciones de liberación de recursos.
- Se agregó la capacidad de abrir un archivo con el path `c:\archivos\certificado.cer`.
- Se agregó información de cómo poder verificar un certificado usando la API del Gobierno de Colima.

### Version 1.1.1 2020-01-22

- Weak Break Compatibility Change: `PemExtractor::__construct($contents)` se podría construir con un parámetro de
  cualquier tipo de datos y al intentar usar el objeto inevitablemente iba a generar un `TypeError`. Se cambió la
  firma del constructor a `PemExtractor::__construct(string $contents)`, así fallaría desde construir el objeto y
  no al usar cualquiera de sus métodos.
- Se actualiza la licencia a 2020.
- Se actualiza de `phpstan/phpstan-shim: ^0.11` a `phpstan/phpstan: ^0.12`.
- Se actualiza la integración continua en Travis y Scrutinizer.
- Se actualizan los badges al nuevo estilo de phpCfdi.

### Version 1.1.0 2019-11-19

- Se puede crear una llave privada en formato `PKCS#8 DER` encriptada o desprotegida. 
  Con este cambio se pueden leer las llaves tal y como las envía el SAT. Gracias @eislasq.
- Si la llave privada no estaba en formato `PEM` se hace una conversión de `PKCS#8 DER` a `PKCS#8 PEM`.
- Se agrega el método `PrivateKey::changePassPhrase` que devuelve una llave privada con la nueva contraseña.
- Se documenta la apertida de certificados y llaves privadas en diferentes formatos.
- Se limpia el entorno de desarrollo y se publica en el paquete distribuible la carpeta de documentación.
- Se hacen refactorizaciones menores para un mejor uso de memoria y rendimiento.

### Version 1.0.1 2019-09-18

- Agregar métodos a `PrivateKey` para poder exponer la llave privada en formato PEM y la frase de paso.
- Traducir documentación en `docs/` a español

### Version 1.0.0 2019-08-13

- Primera versión funcional
- Los proyectos `phpcfdi/xml-cancelacion` y `phpcfdi/sat-ws-descarga-masiva` tienen este proyecto como dependencia,
  la implementación en ambos proyectos dan algunas pistas de cómo mejorar este proyecto.
