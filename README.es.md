# Laravel API Scaffold

[![Tests](https://github.com/caminodeldev/laravel-api-scaffold/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/caminodeldev/laravel-api-scaffold/actions/workflows/tests.yml)

Genera scaffolding de APIs Laravel limpio, seguro y configurable a partir de tablas existentes en base de datos.

**Idiomas:** [English](README.md) | [Español](README.es.md)

> Este paquete genera código Laravel revisable. No reemplaza el diseño de autorización, reglas de negocio, revisión humana, pruebas ni validaciones de preparación productiva.

## Qué hace este paquete

`caminodeldev/laravel-api-scaffold` inspecciona una tabla existente y genera archivos Laravel con una estructura API por capas:

- Modelo Eloquent.
- Controlador API.
- Clases FormRequest.
- API Resource.
- Clase Service.
- Archivo dedicado de rutas generadas.
- Scaffold básico de Feature test.

El código generado es intencionalmente simple y explícito. Debe revisarse, ajustarse y versionarse como código normal de aplicación.

## Qué no hace este paquete

Este paquete no:

- Funciona como motor CRUD en runtime.
- Infiere reglas de autorización de negocio.
- Crea migraciones de base de datos.
- Reemplaza policies, gates, middleware ni validaciones de dominio.
- Garantiza que el código generado esté listo para producción sin revisión.
- Soporta todos los motores de base de datos en `v0.1.1`.

## Estado de release

Release preparado actualmente:

```text
v0.1.1
```

Este release se enfoca en un scaffold API seguro y orientado primero a MySQL, con generación read-only como default recomendado y flags explícitos para escritura y eliminación.

Revisa [`CHANGELOG.md`](CHANGELOG.md) para las notas de release.

## Requisitos

- PHP 8.2 o superior.
- Laravel 10, 11, 12 o 13.
- Una conexión de base de datos configurada.
- Soporte MySQL para el MVP actual.

La compatibilidad con Laravel 13 queda declarada en las restricciones Composer y CI incluye PHP 8.4. Siempre ejecuta la suite de pruebas del paquete y valida el código generado dentro de tu aplicación Laravel objetivo antes de usarlo en producción.

## Instalación

Instala el paquete con Composer:

```bash
composer require caminodeldev/laravel-api-scaffold
```

Laravel package discovery registra el ServiceProvider automáticamente.

Publica el archivo de configuración:

```bash
php artisan vendor:publish --tag=api-scaffold-config
```

Esto crea:

```text
config/api-scaffold.php
```

## Instalación para desarrollo local

Cuando pruebes el paquete antes de publicarlo en Packagist, agrega un repositorio local tipo `path` en la aplicación Laravel que lo consumirá.

Ejemplo:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-api-scaffold",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Luego requiérelo localmente:

```bash
composer require caminodeldev/laravel-api-scaffold:@dev
```

Refresca autoload:

```bash
composer dump-autoload
```

## Flujo inicial recomendado

Comienza inspeccionando la tabla:

```bash
php artisan scaffold:inspect users --connection=mysql
```

Previsualiza la API generada sin escribir archivos:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

Genera una API read-only segura:

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

`--read-only` es explícito para legibilidad. Read-only también es el comportamiento por defecto cuando no usas `--crud`.

El scaffold read-only genera rutas solo para listar y mostrar registros:

```http
GET /api/v1/users
GET /api/v1/users/{user}
```

El prefijo `/api` asume que el archivo generado se importa desde `routes/api.php` de Laravel. Si cargas el archivo de rutas generadas desde otro lugar, ajusta `routes.prefix` en `config/api-scaffold.php`.

## Archivos generados

Para una tabla `users`, el scaffold read-only por defecto crea:

```text
app/Models/User.php
app/Http/Controllers/frontend/v1/UserController.php
app/Http/Requests/Frontend/User/IndexUserRequest.php
app/Http/Resources/UserResource.php
app/Services/UserService.php
routes/scaffolded-api.php
tests/Feature/UserApiTest.php
```

Cuando se usa `--crud`, el paquete también genera FormRequests de escritura:

```text
app/Http/Requests/Frontend/User/StoreUserRequest.php
app/Http/Requests/Frontend/User/UpdateUserRequest.php
```

El controlador generado delega la lógica de consulta a un service, valida entrada con FormRequests, retorna datos mediante Resource y usa bloques `try/catch` controlados.

## Comandos Artisan disponibles

El paquete registra actualmente estos comandos:

| Comando | Propósito |
| --- | --- |
| `scaffold:inspect` | Inspecciona una tabla y muestra metadatos detectados. |
| `scaffold:model` | Genera solo el modelo Eloquent de una tabla. |
| `scaffold:api` | Genera el scaffold API de una tabla. |

### `scaffold:inspect`

```bash
php artisan scaffold:inspect users --connection=mysql
```

Firma:

```text
scaffold:inspect
  {table}
  {--connection=}
```

Úsalo antes de generar archivos para confirmar que el paquete lee la metadata esperada de la tabla.

### `scaffold:model`

```bash
php artisan scaffold:model users --connection=mysql
```

Firma:

```text
scaffold:model
  {table}
  {--connection=}
  {--model=}
  {--dry-run}
  {--force}
```

Ejemplos:

```bash
php artisan scaffold:model users --connection=mysql --dry-run
php artisan scaffold:model users --connection=mysql --model=AccountUser
php artisan scaffold:model users --connection=mysql --force
```

### `scaffold:api`

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

Firma:

```text
scaffold:api
  {table}
  {--connection=}
  {--model=}
  {--route-resource=}
  {--read-only}
  {--crud}
  {--with-delete}
  {--dry-run}
  {--force}
```

Previsualizar sin escribir archivos:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

Generar operaciones de escritura explícitas:

```bash
php artisan scaffold:api users --connection=mysql --crud
```

Generar soporte de eliminación explícitamente:

```bash
php artisan scaffold:api users --connection=mysql --crud --with-delete
```

`--with-delete` requiere `--crud`.

Usa una URI de recurso personalizada cuando no quieras exponer directamente el nombre de tabla:

```bash
php artisan scaffold:api cha_solicitudes --connection=mysql --model=Solicitud --route-resource=solicitudes
```

Por defecto, la URI del recurso se deriva desde el nombre de tabla, no desde la pluralización inglesa del modelo. Por ejemplo, `solicitudes` con `--model=Solicitud` genera `solicitudes`, no `solicituds`.

Sobrescribir archivos generados existentes explícitamente:

```bash
php artisan scaffold:api users --connection=mysql --read-only --force
```

## Estrategia de rutas

Las rutas generadas se escriben en:

```text
routes/scaffolded-api.php
```

El paquete puede agregar opcionalmente un bloque de import en `routes/api.php`:

```php
// <laravel-api-scaffold routes>
if (file_exists(__DIR__ . '/scaffolded-api.php')) {
    require __DIR__ . '/scaffolded-api.php';
}
// </laravel-api-scaffold routes>
```

Este import es idempotente y configurable en `config/api-scaffold.php`.

Los bloques de rutas generadas dentro de `routes/scaffolded-api.php` se administran por recurso. Al regenerar el mismo recurso, el paquete reemplaza el bloque gestionado existente en vez de agregar un duplicado. Las rutas manuales fuera de los marcadores generados se preservan.

Por defecto, el paquete usa `v1` como prefijo porque `routes/api.php` normalmente ya está montado bajo `/api` por Laravel. Si tu proyecto carga el archivo generado desde otro lugar, cambia `routes.prefix` a `api/v1` o al prefijo que necesites.

Los Feature tests generados usan rutas nombradas de Laravel, como `route('users.index')`, en vez de paths hardcodeados `/v1/...`. Esto mantiene los tests alineados con el prefijo real de Laravel, normalmente `/api/v1/...` cuando se cargan desde `routes/api.php`.

Los smoke tests generados para `index` también mockean el método de paginación del Service generado. Así el test valida el cableado ruta/controller sin fallar cuando la base de datos de testing de la aplicación consumidora todavía no tiene migraciones ejecutadas.

## Configuración general

Namespace default de controladores:

```php
App\Http\Controllers\frontend\v1
```

Namespace default de requests:

```php
App\Http\Requests\Frontend
```

Los paths y namespaces de salida son configurables en:

```text
config/api-scaffold.php
```

El controlador generado usa claves configurables para el envelope de respuesta:

```php
'envelope' => [
    'keys' => [
        'code' => 'code',
        'message' => 'message',
        'data' => 'data',
    ],
],
```

Puedes adaptarlas a tu organización:

```php
'envelope' => [
    'keys' => [
        'code' => 'codigoRetorno',
        'message' => 'glosaRetorno',
        'data' => 'respuesta',
    ],
],
```

## Defaults de seguridad y preparación productiva

La seguridad es la restricción principal de diseño del paquete. El scaffold es deliberadamente conservador y busca generar código revisable, no decisiones de autorización listas para producción.

Lee las notas completas en [`SECURITY.md`](SECURITY.md).

Defaults seguros incluidos en el MVP:

- Las APIs read-only son el default recomendado.
- Las operaciones de escritura requieren `--crud`.
- La generación de delete requiere `--crud` y `--with-delete`.
- Los Resources generados excluyen columnas comunes con aspecto de secreto.
- Los modelos generados excluyen columnas comunes con aspecto de secreto desde `$fillable`.
- Los modelos generados ocultan columnas comunes con aspecto de secreto.
- Los controladores generados retornan mensajes de error controlados.
- Las rutas generadas usan middleware configurable.
- `--dry-run` permite revisar archivos planificados antes de escribir.
- Los archivos existentes no se sobrescriben salvo que uses `--force`.

Antes de usar código generado en producción, revisa al menos:

- Middleware de autenticación.
- Reglas de autorización, policies o gates.
- Reglas de los FormRequests generados.
- Campos expuestos por los Resources generados.
- Búsqueda, filtros y paginación.
- Operaciones de escritura y reglas de mass-assignment.
- Logs y manejo de excepciones.
- Índices de base de datos y límites de paginación.

## Estado del MVP

Alcance actual del MVP:

- Inspección de tablas MySQL.
- Generación segura de modelos Eloquent.
- Generación de APIs read-only.
- Generación opcional CRUD mediante flags explícitos.
- Generación de FormRequests.
- Generación de Resources excluyendo campos sensibles.
- Generación de capa Service.
- Generación de controladores delgados con `try/catch`.
- Generación de archivo dedicado de rutas.
- Import idempotente en `routes/api.php`.
- Reemplazo por recurso de bloques gestionados en `routes/scaffolded-api.php`.
- Modo dry-run.
- Modo force overwrite.
- Scaffold básico de Feature test generado usando rutas nombradas y mock del Service en `index`.

Próximos pasos planificados después de `v0.1.1`:

- Soporte PostgreSQL.
- Generación opcional de policies.
- Modo diff real.
- Feature tests generados más robustos para flujos CRUD.
- Extracción opcional del response envelope a un paquete dedicado.
- Matriz CI ampliada para validación específica por versión de framework.

## Desarrollo

Instalar dependencias:

```bash
composer install
```

Validar metadata Composer:

```bash
composer validate
```

Refrescar autoload:

```bash
composer dump-autoload
```

Ejecutar tests:

```bash
composer test
```

La suite actual del paquete valida:

- Detección de columnas sensibles.
- Escritura de archivos y comportamiento dry-run.
- Inspección MySQL con fixtures.
- Resolución de nombres.
- URI de rutas generadas, parámetro de ruta y comportamiento de Feature test.
- Consistencia del envelope de respuesta del controlador.

## Versionamiento

Los releases se crean con tags Git. El paquete no define un campo `version` hardcodeado en `composer.json`; Packagist y Composer resuelven las versiones desde los tags del repositorio.

Tag recomendado para el patch release:

```bash
git tag -a v0.1.1 -m "v0.1.1"
git push origin v0.1.1
```

## Archivo de distribución

El paquete incluye `.gitattributes` para dejar archivos solo de desarrollo fuera de los archives distribuidos por Composer.

## Licencia

MIT
