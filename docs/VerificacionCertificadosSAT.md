# Verificación de certificados SAT

Los certificados del SAT se pueden ser verificados contra los certificados raíz
y así asegurar que el certificado fue emitido por el SAT.

Al no tener un servicio público de verificación, el SAT comparte sus certificados raíz desde 
<http://omawww.sat.gob.mx/tramitesyservicios/Paginas/certificado_sello_digital.htm>.

Para ello necesitaremos de `openssl`. El procedimiento general consiste en:

1. Descargar los certificados raíz de producción
2. Convertir los certificados DER en PEM
3. Adaptar la carpeta para reconocerla como un directorio de Certificate Authority (CA)
4. Comparar el certificado PEM contra los certificados raíz.

Lo mejor sería que el SAT tuviera un servicio público de consulta de certificados, incluso saber si un
certificado ha sido revocado, el problema es que sí tienen el servicio, pero está restringido a agencias
gubernamentales <https://www.gob.mx/cms/uploads/attachment/file/36607/ANEXO-UNICO_Req-de-uso-de-OCSP.pdf>

## Verificación local de certificado

Con el siguiente comando se hace la verificación de un certificado

```shell
openssl verify -no_check_time -CApath sat_ca_prod mi_certificado.pem
```

Donde:

- `-no_check_time`: No verificar que los certificados raíz sean válidos en el tiempo.
- `-CAPath sat_ca_prod`: Lugar en donde están los certificados raíz ya procesados.
- `mi_certificado.pem`: Certificado en formato PEM a validar

## Creación de la carpeta de certificados raíz

Una vez que tengas los archivos raíz descomprimidos puedes ejecutar estos comandos para que la carpeta sea
usable para el comando `openssl verify` o `openssl ocsp`.

* Exportar en formato PEM los certificados que no están como tal:

Para cada archivo `.cer` o `.crt` en el directorio `sat_ca_prod` ejecuta `openssl` para exportar de formato DER a PEM. 

* Crear enlaces simbólicos a los archivos por el número de hash

El comando `openssl verify -CApath sat_ca_prod`, busca que el directorio especificado en `-CApath` tenga
los archivos por número de hash, si no están así entonces no se tomarán en cuenta. 

```shell
openssl rehash sat_ca_prod
```

### Script para crear toda la estructura de producción y pruebas

El siguiente script básico de bash ejecuta todos los comandos que se requieren para descargar, exportar y poder
utilizar como `CApath` los certificados raíz ofrecidos por el SAT:

```bash
#!/bin/bash -e

CA_PROD_SOURCE="http://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Cert_Prod.zip"
CA_PROD_DEST="ca_production"
CA_TEST_SOURCE="http://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Certificados_P.zip"
CA_TEST_DEST="ca_testing"

function extract_certificates() {
    local url="$1"
    local source="$(basename "$url")"
    local extractto="${source%.*}"
    local ca_folder="$2"
    rm -f "$source"
    wget "$url" -O "$source"
    rm -rf "$ca_folder" "$extractto"
    mkdir -p "$ca_folder" "$extractto"
    unzip "$source" -d "$extractto"

    find "$extractto" -type f \( -name "*.cer" -o -name "*.crt" \) | while read certificate; do
      rename_or_convert_certificate "$certificate" "$ca_folder"
    done
    rm -rf "$extractto"
    
    openssl rehash "$ca_folder"
}

function rename_or_convert_certificate {
    local source="$1"
    local sourcebasename="$(basename "$source")"
    local destination="$2/${sourcebasename%.*}.pem"
    if [ "text/plain" == "$(file "$source" -b --mime-type)" ]; then
        echo "Copy $source -> $destination"
        cp "$source" "$destination"
        return;
    fi
    echo "Convert $source -> $destination"
    openssl x509 -inform DER -outform PEM -in "$source" -out "$destination"
}

extract_certificates "$CA_PROD_SOURCE" "$CA_PROD_DEST"
extract_certificates "$CA_TEST_SOURCE" "$CA_TEST_DEST"
```

## Verificación a través de OCSP

A pesar de que el SAT anuncia que su servicio OCSP es privado, en realidad sí se encuentra públicamente disponible.

El siguiente comando sirve para verificar un certificado (FIEL o CSD) emitido por el SAT.

```shell
OPENSSL_CONF=/etc/ssl/openssl_custom.cnf \
  openssl ocsp -issuer ca_production/AC4_SAT.cer.pem -cert certificate.cer \
  -text -CApath ca_production -url https://cfdi.sat.gob.mx/edofiel
```

Y entrega una respuesta como:

```text
Response verify OK
certificate.cer: revoked
	This Update: May 23 14:44:07 2023 GMT
	Next Update: May 23 14:45:07 2023 GMT
	Reason: unspecified
	Revocation Time: May 18 19:02:47 2023 GMT
```

### `OPENSSL_CONF=/etc/ssl/openssl_custom.cnf`

El sitio del SAT no tiene la seguridad adecuada y las nuevas versiones de OpenSSL 3.x no permiten hacer la consulta.

En 2023-05-23 se encontró que utilizaba `TLSv1.2, Cipher is DHE-RSA-AES256-GCM-SHA384 ... Server Temp Key: DH, 1024 bits`
y no es considerado seguro en el nivel 2 de OpenSSL:
*RSA, DSA and DH keys shorter than 2048 bits and ECC keys shorter than 224 bits are prohibited*.

Por lo que hay que degradar la configuración a `SECLEVEL=1`, generalmente agregando la siguiente información:

```ini
[openssl_init]
ssl_conf = ssl_sect

[ssl_sect]
system_default = system_default_sect

[system_default_sect]
CipherString = DEFAULT@SECLEVEL=1
```

### `-issuer ca_production/AC4_SAT.cer.pem`

El certificado padre del SAT, si se está usando el certificado incorrecto el comando fallará y
mostrará un mensaje de error como este:

```text
Responder Error: trylater (3)
```

### `-cert certificate.cer`

El certificado que se desea revisar, no es necesario convertirlo a formato PEM.

### `-url https://cfdi.sat.gob.mx/edofiel`

Dirección del servicio OSCP del SAT.

### `-CApath ca_production`

Dirección donde están los certificados de confianza del SAT.

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
