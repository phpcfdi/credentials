# phpcfdi/credentials Tareas pendientes

- [X] Encontrar como diferenciar entre un archivo CSD y un archivo FIEL
  R: Se identifica por el campo OU (Organization Unit / Sucursal) del certificado, si está vacío es FIEL,
  si tiene contenido es CSD. 

- [ ] Verificar si un certificado fue realmente emitido por el SAT
  Ver [VerificacionCertificadosSAT](VerificacionCertificadosSAT.md)

- [ ] Usar excepciones específicas en lugar de genéricas
