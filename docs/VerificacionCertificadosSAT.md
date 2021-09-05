# Verificación de certificados SAT

Los certificados del SAT se pueden ser verificados contra los certificados raíz
y así asegurar que el certificado fue emitido por el SAT.

Al no tener un servicio público de verificación, el SAT comparte sus certificados raíz desde 
<http://omawww.sat.gob.mx/tramitesyservicios/Paginas/certificado_sello_digital.htm>.

Para ello necesitaremos de `openssl`. El procedimiento general consiste en:

1. Descargar los certificados raíz de producción
1. Convertir los certificados DER en PEM
1. Adaptar la carpeta para reconocerla como un directorio de Certificate Authority (CA)
1. Comparar el certificado PEM contra los certificados raíz.

Lo mejor sería que el SAT tuviera un servicio público de consulta de certificados, incluso saber si un
certificado ha sido revocado, el problema es que sí tienen el servicio, pero está restringido a agencias
gubernamentales <https://www.gob.mx/cms/uploads/attachment/file/36607/ANEXO-UNICO_Req-de-uso-de-OCSP.pdf>

## Verificación de certificado

Con el siguiente comando se hace la verificación de un certificado

```shell
openssl verify -no_check_time -CApath sat_ca_prod - mi_certificado.pem
```

Donde:

- `-no_check_time`: No verificar que los certificados raíz sean válidos en el tiempo.
- `-CAPath sat_ca_prod`: Lugar en donde están los certificados raíz ya procesados.
- `- mi_certificado.pem`: Certificado en formato PEM a validar

## Creación de la carpeta de certificados raíz

Una vez que tengas los archivos raíz descomprimidos puedes ejecutar estos comandos para que la carpeta sea
usable para el comando `openssl verify`.

* Exportar en formato PEM los certificados que no están como tal:

Para cada archivo `.cer` en el directorio `sat_ca_prod` ejecuta `openssl` para exportar de formato PEM a DER. 

```shell
find sat_ca_prod/ -type f -maxdepth 0 -name "*.cer" -exec \
     openssl x509 -inform DER -outform PEM -in "{}" -out "{}.pem" \
\;
```

* Crear enlaces simbólicos a los archivos por el número de hash

El comando `openssl verify -CApath sat_ca_prod`, busca que el directorio especificado en `-CApath` tenga
los archivos por número de hash, si no están así entonces no se tomarán en cuenta. 

```shell
openssl rehash sat_ca_prod
```

## Script para crear toda la estructura de producción y pruebas

El siguiente script básico de bash ejecuta todos los comandos que se requieren para descargar, exportar y poder
utilizar como `CApath` los certificados raíz ofrecidos por el SAT:

```bash
#!/bin/bash -e

CA_PROD_SOURCE="https://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Cert_Prod.zip"
CA_PROD_DEST="ca_production"
CA_TEST_SOURCE="https://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Certificados_P.zip"
CA_TEST_DEST="ca_testing"

function extract() {
    local url="$1"
    local source="$(basename "$url")"
    local ca_folder="$2"
    rm -f "$source"
    wget "$url" -O "$source"
    rm -rf $ca_folder
    mkdir -p "$ca_folder"
    unzip "$source" -d "$ca_folder"
    find . -type f -name "*.cer" -exec openssl x509 -inform DER -outform PEM -in "{}" -out "{}.pem" \;
    find "$ca_folder" -type d -exec openssl rehash "{}" \;
}

extract "$CA_PROD_SOURCE" "$CA_PROD_DEST"
extract "$CA_TEST_SOURCE" "$CA_TEST_DEST"
```

## Verificación de certificados a través de la página del Gobierno de Colima

El Gobierno de Colima expone una API JSON en <https://apisnet.col.gob.mx/wsSignGob> que sirve para el propósito
de verificar el estado de un certificado.

El principal inconveniente del servicio es que no establece la fecha de revocación.
Por lo que el estado del certificado solo es relativo al momento de la consulta.  

Ejemplo de consumo:

```shell
curl -X POST -F 'certificado=@/path/to/certificate.cer' \
  https://apisnet.col.gob.mx/wsSignGob/apiV1/Valida/Certificado
```

Ejemplo de respuesta:

```json
{
    "RESTService": {
        "Message": "Certificado Aceptado ante el SAT"
    },
    "Response": {
        "OCSPStatus": "Revocado"
    }
}
```
