# phpcfdi/credentials To Do List 

## Tareas pendientes

- [ ] Verificar si un certificado fue realmente emitido por el SAT
  Ver [VerificacionCertificadosSAT](VerificacionCertificadosSAT.md).

- [ ] Usar excepciones específicas en lugar de genéricas.

## Tareas completadas

- Migrar de Travis-CI a GitHub Workflows.

- Encontrar cómo diferenciar entre un archivo CSD y un archivo FIEL. 
  R: Se identifica por el campo OU (Organization Unit / Sucursal) del certificado,
  si está vacío es FIEL, si tiene contenido es CSD. 
