# CHANGELOG

:us: changes are documented in spanish as it help intented audience to follow changes

:mexico: los cambios están documentados en español para mejor entendimiento

Nos apegamos a [SEMVER](SEMVER.md), revisa la información para entender mejor el control de versiones.

## Version 1.1.1 2020-01-22

- Weak Break Compatibility Change: `PemExtractor::__construct($contents)` se podría construir con un parámetro de
  cualquier tipo de datos y al intentar usar el objeto inevitablemente iba a generar un `TypeError`. Se cambió la
  firma del constructor a `PemExtractor::__construct(string $contents)`, así fallaría desde construir el objeto y
  no al usar cualquiera de sus métodos.
- Se actualiza la licencia a 2020.
- Se actualiza de `phpstan/phpstan-shim: ^0.11` a `phpstan/phpstan: ^0.12`.
- Se actualiza la integración continua en Travis y Scrutinizer.

## Version 1.1.0 2019-11-19

- Se puede crear una llave privada en formato `PKCS#8 DER` encriptada o desprotegida.
  Con este cambio se pueden leer las llaves tal y como las envía el SAT. Gracias @eislasq.
- Si la llave privada no estaba en formato `PEM` se hace una conversión de `PKCS#8 DER` a `PKCS#8 PEM`.
- Se agrega el método `PrivateKey::changePassPhrase` que devuelve una llave privada con la nueva contraseña.
- Se documenta la apertida de certificados y llaves privadas en diferentes formatos.
- Se limpia el entorno de desarrollo y se publica en el paquete distribuible la carpeta de documentación.
- Se hacen refactorizaciones menores para un mejor uso de memoria y rendimiento.

## Version 1.0.1 2019-09-18

- Agregar métodos a `PrivateKey` para poder exponer la llave privada en formato PEM y la frase de paso.
- Traducir documentación en `docs/` a español

## Version 1.0.0 2019-08-13

- Primera versión funcional
- Los proyectos `phpcfdi/xml-cancelacion` y `phpcfdi/sat-ws-descarga-masiva` tienen este proyecto como dependencia,
  la implementación en ambos proyectos dan algunas pistas de cómo mejorar este proyecto.
