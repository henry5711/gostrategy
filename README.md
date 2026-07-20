# Users API
# Estimado lector este proyecto tiene dos ramas main donde se cumple con los requerimientos 
# y otra llamada laravel-magic donde se cumplen tambien los requerimientos pero usando todas las bondades de laravel
API REST en Laravel para listar, consultar, registrar, editar, eliminar
lógicamente y recuperar `Users`, usando las herramientas nativas del
framework: **Sanctum** para el token de autenticación, **FormRequest** para las
validaciones,
**API Resources**, **SoftDeletes** para el trash, un **scope de filtro**
dinámico para las consultas, y el **logger default** de Laravel para los logs de
entrada/salida.

## Requerimientos mínimos

- PHP >= 8.3
- Composer 2.x
- MySQL 8 (o compatible)
- Node.js 18+ y npm (solo necesario si se va a compilar el front-end del starter kit; no es requerido para consumir la API)

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurar en `.env` los datos de conexión a MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gostategy
DB_USERNAME=root
DB_PASSWORD=
```

Correr migraciones y seeders (crea un usuario de prueba, regiones y comunas de ejemplo):

```bash
php artisan migrate:fresh --seed
```

Levantar el servidor:

```bash
php artisan serve
```

## Colección de Postman

En la raíz del proyecto: `Users-API.postman_collection.json`. Importarla en
Postman y ajustar la variable de colección `base_url` (default
`http://localhost:8000`). El request de Login guarda el token automáticamente en
la variable `token`, y el de "Registrar User" guarda el `id` creado en
`User_id` para los demás requests.

## Variables de entorno relevantes

| Variable                     | Descripción                                                                  | Default |
|-------------------------------|------------------------------------------------------------------------------|---------|
| `APP_DEBUG`                   | Debe estar siempre en `false`.                                                | `false` |
| `APP_ENV`                     | Si es `production`, la API deja de guardar los logs de salida (solo entrada). | `local` |
| `SANCTUM_TOKEN_EXPIRATION`    | Minutos de vida del token de autenticación (`config/sanctum.php`).            | `60`    |

## Usuario de prueba

El seeder `DatabaseSeeder` crea:

- **Usuario**: `test@example.com` / `password` (usado para el login de la API).
- **Regiones y comunas** de ejemplo (`RegionSeeder`), necesarias para registrar un
  `User`. Cada región/commune tiene un `name` obligatorio y una `description`
  opcional (nullable, sin usar por ahora).


## Autenticación (Sanctum)

El login emite un **Personal Access Token de Sanctum** (`$user->createToken(...)`),
no un token armado a mano. La expiración la controla `config('sanctum.expiration')`
(`SANCTUM_TOKEN_EXPIRATION` en `.env`) y Sanctum la valida automáticamente en el
guard `auth:sanctum` — un token vencido, inválido o ausente responde `401` sin
necesidad de ningún middleware propio para verificarlo.

Se envía en cada request como:

```
Authorization: Bearer {token}
```

## Endpoints

Las respuestas de los endpoints de `User` (`UserController`) siguen el
formato:

```json
{
  "success": true,
  "message": "...",
  "data": { }
}
```

### 1. Login — `POST /api/auth/login`

Valida con `App\Http\Requests\Auth\LoginRequest` (FormRequest).

Body:
```json
{
  "email": "test@example.com",
  "password": "password"
}
```

Éxito (`200`):
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "1|9UeAEJIT5sgojEGKMXX0GvlKKM7L586Rqe...",
    "expires_at": "2026-07-18 23:27:44"
  }
}
```

Error (`401`): credenciales inválidas. Error (`422`, shape default de Laravel):
campos faltantes/formato inválido.

### 2. Listar Users — `GET /api/Users`
Éxito (`200`):
```json
{
  "success": true,
  "message": "Users encontrados.",
  "data": [
    { "id": 2, "attributes": {"name": "Henry", "last_name": "Romero", "address": null}, "relationships": {"region": {"id": 1, "name": "Andes"}, "commune": {"id": 1, "name": "Tachira"}} }
  ]
}
```

### 3. Consultar User — `GET /api/Users/{id}`
```json
{
  "success": true,
  "message": "User encontrado.",
  "data": {
    "id": 2,
    "attributes": { "name": "Henry", "last_name": "Romero", "address": null },
    "relationships": {
      "region": { "id": 1, "name": "Andes" },
      "commune": { "id": 1, "name": "Tachira" }
    }
  }
}
```

### 4. Registrar User — `POST /api/Users`
Body:
```json
{
  "dni": "26404237",
  "email": "henry@example.com",
  "password": "unaClaveSegura123",
  "name": "Henry",
  "last_name": "Romero",
  "address": "mi casa",
  "region_id": 1,
  "commune_id": 1
}
```

### 5. Eliminar User (lógico) — `DELETE /api/Users/{id}`


### 6. Editar User — `POST /api/Users/{id}/update`

Body (solo los campos que se quieren cambiar):
```json
{
  "address": "Nueva dirección 123",
  "region_id": 1,
  "commune_id": 2
}
```

- No se puede editar un User que está en trash (responde `404 Registro no existe`).
- Si se envía `email`, se valida que no esté usado por otro User
  (`Rule::unique('users','email')->ignore($id)`).
- Si se envía `region_id` o `commune_id`, se exige el otro y se valida la relación
  entre ambos, igual que en el registro.

### 7. Recuperar/activar User — `POST /api/Users/{id}/restore`

Usa `SoftDeletes::restore()` — saca al User del trash (`deleted_at = null`).
Solo tiene efecto sobre un User que esté en trash:

- Si el `id` no existe: `404 Registro no existe`.
- Si existe pero no está en trash: `422`, no tiene sentido recuperarlo.
- Si estaba en trash: `200 success: true`.


## Tests

```bash
php artisan test --filter=Api
```

Cubre: login exitoso/fallido vía Sanctum, password guardado hasheado, registro
requiere password, registro con región/commune válida e inválida, listado sin
filtros y filtrado por status/dni, consulta por id, consulta de un User en
trash, eliminación lógica (soft delete), doble eliminación (`"Registro no
existe"`), edición (incluyendo sobre un User en trash), recuperación
(incluyendo sobre uno que no está en trash), y acceso sin token o con token
expirado.
