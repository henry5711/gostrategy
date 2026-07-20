# Customers API


# Estimado lector este proyecto tiene dos ramas main donde se cumple con los requerimientos 
# y otra llamada laravel-magic donde se cumplen tambien los requerimientos pero usando todas las bondades de laravel


API REST en Laravel para registrar, consultar y eliminar lógicamente `Customers`, con
autenticación por token propio y validaciones/autenticación
implementadas a través de middlewares.

## Colección de Postman

En la raíz del proyecto: `Customers-API.postman_collection.json`. Importarla en
Postman e importar/editar la variable de colección `base_url` (default
`http://localhost:8000`). Incluye Login (guarda el token automáticamente en la
variable `token` vía test script) y los 3 servicios de Customer, incluyendo los
casos de error (dni duplicado, commune no relacionada, sin token, doble eliminación).

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

## Variables de entorno relevantes

| Variable                 | Descripción                                                                 | Default |
|---------------------------|-----------------------------------------------------------------------------|---------|
| `APP_DEBUG`               | Debe estar siempre en `false`.                                              | `false` |
| `APP_ENV`                 | Si es `production`, la API deja de guardar los logs de salida (solo entrada). | `local` |
| `AUTH_TOKEN_TTL_MINUTES`  | Minutos de vida del token de autenticación.                                  | `60`    |

## Usuario de prueba

El seeder `DatabaseSeeder` crea:

- **Usuario**: `test@example.com` / `password` (usado para el login de la API).
- **Regiones y comunas** de ejemplo (`RegionSeeder`), necesarias para registrar un `Customer`.

## Autenticación

Todos los servicios de `Customer` requieren un token obtenido mediante el servicio de
login. El token se genera como:

```
sha1(email . fecha_hora_login('YmdHis') . random_int(200, 500))
```

y se persiste en la tabla `auth_tokens` con una fecha de expiración
(`AUTH_TOKEN_TTL_MINUTES` desde el login). Se envía en cada request como:

```
Authorization: Bearer {token}
```

Un token vencido o inexistente responde `401` sin dar acceso al servicio
(`App\Http\Middleware\Api\VerifyApiToken`).

## Endpoints

Todas las respuestas siguen el formato:

```json
{
  "success": true,
  "message": "...",
  "data": { }
}
```

### 1. Login — `POST /api/auth/login`

Middlewares: `validate.login` (campos requeridos) → `api.log`.

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
    "token": "b1946ac92492d2347c6235b4d2611184...",
    "expires_at": "2026-07-18 17:00:00"
  }
}
```

Error (`401`): credenciales inválidas. Error (`422`): campos faltantes/formato inválido.

### 2. Registrar Customer — `POST /api/customers`

Middlewares: `api.token` (requiere `Authorization: Bearer {token}`) →
`validate.customer.store` (campos obligatorios + existencia y relación de
`region_id`/`commune_id` + dni/email no duplicados) → `api.log`.

Body:
```json
{
  "dni": "26404237",
  "email": "henryrom57@example.com",
  "name": "Henry",
  "last_name": "Romero",
  "address": "mi casa",
  "region_id": 1,
  "commune_id": 1
}
```

Éxito (`201`): `success: true` con el customer creado (incluye `region` y `commune`).
Error (`422`): campos faltantes, región/commune inexistente, commune no pertenece a la
región indicada, o dni/email ya registrado.

### 3. Consultar Customer — `GET /api/customers?dni=...` o `?email=...`

Middlewares: `api.token` → `validate.customer.query` (requiere `dni` o `email`) →
`api.log`.

Solo devuelve customers con `status = A` (activo), usando los scopes del modelo
`Customer::active()->filter($request)`. Si está `I` (desactivo) o `trash`
(eliminado), responde `404` como no encontrado.

Éxito (`200`):
```json
{
  "success": true,
  "message": "Customer encontrado.",
  "data": {
    "name": "Henry",
    "last_name": "Romero",
    "address": null,
    "region": "Andes",
    "commune": "Tachira"
  }
}
```



### 4. Eliminar Customer (lógico) — `DELETE /api/customers/{id}`

Middlewares: `api.token` → `api.log`.

- Si el customer está `A` o `I`: se marca como `trash` y responde `200 success: true`.
- Si el customer ya está `trash`, o el `id` no existe: `404 Registro no existe`.

### 5. Actualizar Customer — `POST /api/customers/{id}/update`

Middlewares: `api.token` → `validate.customer.update` (valida los campos que vengan) →
`api.log`.

Body (solo se envían los campos que se quieren cambiar; el identificador va en la URL,
no en el body):
```json
{
  "address": "Nueva dirección 123",
  "region_id": 1,
  "commune_id": 2
}
```

- No se puede actualizar un customer que está en `trash` (responde `404 Registro no existe`).
- Si se envía `email`, se valida que no esté usado por otro customer.
- Si se envía `region_id` o `commune_id`, se exige el otro y se valida la relación
  entre ambos, igual que en el registro.

### 6. Recuperar/activar Customer — `POST /api/customers/{id}/restore`

Middlewares: `api.token` → `api.log`.

Saca al customer del `trash` y lo vuelve a dejar `A` (activo). Solo tiene efecto sobre
un customer que esté en `trash`:

- Si el `id` no existe: `404 Registro no existe`.
- Si existe pero no está en `trash` (ya está `A` o `I`): `422`, no tiene sentido
  recuperarlo.
- Si estaba en `trash`: `200 success: true`, pasa a `A`.



## Logs de entrada/salida

Cada request a los endpoints de la API queda registrado en la tabla `request_logs`
(`App\Http\Middleware\Api\LogApiRequest`):

- **Entrada**: siempre se registra (método, ruta, IP, payload sin `password`).
- **Salida**: se registra solo si `APP_ENV` es distinto de `production`. En
  producción solo se guardan los logs de entrada.

## Tests

```bash
php artisan test --filter=Api
```

Cubre: login exitoso/fallido, validación de campos, registro con región/commune
válida e inválida, consulta de customer activo y trashed, eliminación lógica,
doble eliminación (`"Registro no existe"`), y acceso sin token o con token expirado.
