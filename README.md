# Sistema de Registro de Visitas — Clínica Médica MIA

Panel web (Laravel 13) para consultar, editar y reportar las visitas registradas por el
personal de Hospital Central, Torre de Consultorios y Cafetería.

## Dependencia externa: la app móvil

**Este proyecto no genera los datos de visitas — los lee.** Una app móvil separada (con su
propio backend) es quien registra las visitas y sube las fotos de los visitantes. Esta app
web solo consulta/edita esa misma base de datos y sirve las fotos desde el servidor de esa
app móvil.

Esto significa que **dos variables de `.env` deben apuntar al mismo host de LAN**:

- `DB_HOST` — la base de datos MySQL `registro_visitas` (tablas `visita`, `visita_familiar`,
  `visita_proveedor`, `visita_postulante`, `visita_torre` — sin modelos Eloquent ni
  migraciones en este repo porque no son responsabilidad de este proyecto).
- `MOBILE_UPLOADS_URL` — el mismo servidor, sirviendo las fotos de `foto_persona`/`foto_ine`
  (columnas que solo guardan la ruta relativa, ej. `/uploads/x.jpg`).

Si esa IP de LAN cambia (ya ha pasado), **ambas variables hay que actualizarlas juntas** —
si no, la app truena al conectar a la BD, o las fotos de visitantes simplemente no cargan
(sin ningún error visible más allá del ícono de imagen rota).

## Desarrollo local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configura DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD y MOBILE_UPLOADS_URL
# apuntando al servidor de la app móvil (ver arriba).
npm run build
php artisan serve
```

## Comandos útiles

- `composer test` — limpia config, corre lint (`pint --test`), tipos (`phpstan`/`larastan`) y `php artisan test`.
- `composer lint` / `composer lint:check` — formatea/verifica estilo PHP con Pint.
- `npm run lint` / `npm run format` — ESLint/Prettier para el lado React (usado solo en páginas de autenticación/ajustes, no en el dashboard de visitas).
