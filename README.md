# WEBTE2 CAS Simulations

Final WEBTE2 project built with Laravel, GNU Octave, Chart.js, Canvas animations, OpenAPI documentation, dynamic PDF export, request logs, CSV export, anonymous animation statistics, and Docker.

## Features

- Bilingual UI: Slovak and English.
- Protected REST API for GNU Octave commands through `X-CAS-API-Key`.
- CSRF-protected browser routes for the frontend, so the API key is never exposed in JavaScript.
- CAS console with CodeMirror syntax highlighting and per-user variable persistence.
- Inverted pendulum and ball-and-beam simulations calculated by Octave and rendered with synchronized Chart.js graphs and Canvas animations.
- CAS and simulation logs with CSV export.
- Anonymous animation usage statistics with configurable repeat-counting interval.
- OpenAPI JSON, Swagger UI, and dynamic PDF API documentation.
- Docker setup with PHP-FPM, Nginx, MariaDB, Node/Vite, GNU Octave, and `octave-control`.

## Visual Design

The frontend uses a custom Laravel Blade and Tailwind CSS implementation. The dashboard structure is visually inspired by the TailAdmin community edition, an MIT-licensed Tailwind admin dashboard template:

```text
https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template
```

The dark laboratory aesthetic, glow accents, and glass-like control panels are inspired by DarkUI's dark-first Tailwind component style:

```text
https://darkui.dev/
```

No TailAdmin or DarkUI source files are bundled directly in this project. The UI was adapted into the existing Blade views and project stylesheet so the application remains lightweight and aligned with the assignment.

## Requirements

Recommended path:

- Docker
- Docker Compose

Local path:

- PHP `^8.4`
- Composer 2
- Node.js and npm
- GNU Octave
- Octave `control` package
- MariaDB/MySQL or SQLite

## Setup With Docker

```bash
docker compose up --build
```

The app is served at:

```text
http://127.0.0.1:8000
```

The Docker entrypoint installs Composer and npm dependencies, builds frontend assets, generates `APP_KEY` when needed, waits for MariaDB, and runs migrations.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

On Windows PowerShell, use `npm.cmd` if script execution blocks `npm.ps1`.

## Environment

Important `.env` values:

```dotenv
CAS_API_KEY=change_me_secret_key
OCTAVE_PATH=octave
OCTAVE_TIMEOUT_SECONDS=10
SIMULATION_DELAY_MS=50
STATISTICS_INTERVAL_MINUTES=10
```

Database defaults in Docker:

```dotenv
DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=webte2
DB_USERNAME=webte2
DB_PASSWORD=webte2
```

## Pages

- `/` home
- `/cas-console` CAS console
- `/pendulum` inverted pendulum simulation
- `/ball-beam` ball and beam simulation
- `/logs` request logs and CSV export
- `/statistics` animation usage statistics
- `/api-docs` Swagger UI
- `/openapi.json` public OpenAPI JSON for Swagger UI

## API

External clients must send:

```http
X-CAS-API-Key: change_me_secret_key
```

Protected API endpoints:

- `POST /api/cas/execute`
- `POST /api/simulations/pendulum`
- `POST /api/simulations/ball-beam`
- `GET /api/logs`
- `GET /api/logs/export`
- `GET /api/statistics`
- `GET /api/statistics/{animation}`
- `GET /api/openapi`
- `GET /api/docs/pdf`

Browser frontend routes use normal Laravel web middleware and CSRF:

- `POST /cas-console/execute`
- `POST /simulations/pendulum/run`
- `POST /simulations/ball-beam/run`

## Database

Main project tables:

- `cas_logs`: command name, request payload, status, output, error, IP address, anonymous user token, timestamps.
- `animation_usages`: animation type, anonymous user token, IP address, city, country, usage time.

The anonymous user token is stored in the `anonymous_user_token` cookie.

## Division Of Work

- Person 1: Laravel backend, REST API, Octave services, logging, statistics, OpenAPI, dynamic PDF, Docker.
- Person 2: frontend UI, CAS console, Chart.js graphs, Canvas animations, responsive polish, localization, final documentation.

## Verification

```bash
php artisan test
npm run build
php artisan route:list
```

Manual smoke tests:

- Switch SK/EN and stay on the same page.
- Run CAS command `1 + 1`.
- Run `a = 1 + 1`, then `a + 2` to verify CAS variable persistence.
- Try a blocked command such as `system("ls")`.
- Run both simulations.
- Check Play, Pause, Reset, and speed controls.
- Confirm graph cursor and Canvas animation move together.
- Export logs as CSV.
- Open statistics details.
- Open Swagger UI, authorize with the API key, and download the dynamic PDF through `/api/docs/pdf`.

## Submission Notes

Generated PDF files and built runtime artifacts should not be committed unless explicitly required by the submission package. The submitted project should include source code, configuration example, Docker files, database migration files, technical documentation, and the demo video.
