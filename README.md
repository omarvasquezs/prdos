# Polleria P'rdos — Plataforma (Laravel + Vue 3)

Este repositorio contiene una plantilla integrada con Laravel 12 (backend) y Vue 3 (frontend) preparada para desarrollo local con DDEV y Vite.

## 🚀 Inicio rápido

Desarrollo local (DDEV + Vite):

```bash
ddev start      # Iniciar DDEV
ddev dev        # Iniciar Vite dev server con hot-reload
```

URLs importantes:
- App: https://prdos.ddev.site
- Login: https://prdos.ddev.site/login
- Dashboard: https://prdos.ddev.site/dashboard
- Vite Dev Server (HMR): https://prdos.ddev.site:5173

Compilación para producción (en el servidor de despliegue / VPS o CI):

```bash
npm ci && npm run build
```

> Nota: en desarrollo local usamos `ddev dev` para HMR; la compilación final de assets debe hacerse en el servidor o en el pipeline de CI con `npm run build`.

---

## 📋 Comandos útiles (DDEV)

- `ddev dev` — Inicia Vite (equivalente a `ddev npm run dev -- --host`)
- `ddev cc` — Limpia cachés de Laravel (equiv. `ddev artisan optimize:clear`)
- `ddev fresh` — Ejecuta `migrate:fresh --seed` para resetear la BD

Los comandos personalizados de DDEV están en `.ddev/commands/host/`.

---

## 📁 Estructura principal

```
resources/
├── js/
│   ├── App.vue
│   ├── app.js
│   ├── router.js
│   ├── bootstrap.js
│   ├── layouts/
│   │   └── AdminLayout.vue
│   ├── pages/
│   │   ├── DashboardPage.vue
│   │   └── TestPage.vue
│   └── stores/
│       └── auth.js
└── views/
	├── layouts/
	│   ├── auth.blade.php
	│   └── app.blade.php
	├── login.blade.php
	└── dashboard.blade.php
```

---

## 🔐 Autenticación

- Sistema session-based con Laravel.
- Rutas públicas: `/login` — Rutas protegidas: `/dashboard`, `/test`.
- Flujo: login (Vue) → POST `/login` (Laravel) → sesión creada → Vue obtiene `/api/user`.

Usuarios de prueba (seeders):
- `test` / `password`
- `admin` / `admin123`

---

## 🛠️ Comandos importantes

Desarrollo y build:

```bash
# Desarrollo (local)
ddev dev

# Build para producción (ejecutar en VPS o CI)
npm ci && npm run build
```

Base de datos:

```bash
ddev artisan migrate
ddev fresh        # migrate:fresh --seed
ddev artisan db:seed
```

Limpieza de caches:

```bash
ddev cc           # optimize:clear
ddev artisan config:clear
ddev artisan view:clear
```

---

## ⚙️ Dependencias principales

- Laravel 12
- Vue 3 + Composition API
- Vue Router
- Pinia
- Vite
- Bootstrap 5 (estilos)
- Axios

---

## 🐛 Troubleshooting

- Si el título del navegador sigue mostrando "Laravel": asegúrate de haber actualizado `APP_NAME` en `.env` y limpiado la cache de config:

```bash
ddev artisan config:clear
ddev artisan cache:clear
```

- Si Vite no compila:

```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

## ✅ Notas de despliegue

- Recomendado en VPS / CI:

```bash
npm ci
npm run build
```

- Luego copiar `public/build/` y assets estáticos según tu flujo (o usar pipeline que haga `rsync` / `scp` o un deploy automático).

---

## 📄 Licencia

MIT
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>
