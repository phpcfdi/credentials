# CHANGELOG

## Acerca de SemVer

Usamos [Versionado Semántico 2.0.0](SEMVER.md) por lo que puedes usar esta librería sin temor a romper tu aplicación.

## Cambios no liberados en una versión

Pueden aparecer cambios no liberados que se integran a la rama principal, pero no ameritan una nueva liberación de
versión, aunque sí su incorporación en la rama principal de trabajo. Generalmente se tratan de cambios en el desarrollo.

## Listado de cambios

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
