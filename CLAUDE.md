# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Quick Start

```bash
# Backend (PHP in Docker, uses smart-port's MySQL)
docker compose -f docker-compose.dev.yaml up -d
# Backend API: http://localhost:8002

# Frontend (Vue 3 + Vite)
cd frontend && npm install && npm run dev
# Frontend: http://localhost:5174 (proxies /api → localhost:8002)

# Database init (first time, or after schema changes)
ROOT_PASS=$(docker exec smartport-db printenv MYSQL_ROOT_PASSWORD)
docker exec -i smartport-db mysql -uroot -p"$ROOT_PASS" --default-character-set=utf8mb4 -e "DROP DATABASE IF EXISTS decoration_core; CREATE DATABASE decoration_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
for f in 00-base-tables.sql 01-decoration-schema.sql 02-seed-data.sql 03-dev-seed.sql; do
  docker exec -i smartport-db mysql -uroot -p"$ROOT_PASS" --default-character-set=utf8mb4 decoration_core < "database/$f"
done

# Tests
cd frontend && npm test          # Vitest (once)
cd frontend && npm run test:watch # Vitest (watch)
```

**Demo login:** `admin` / `admin123`

## Architecture

Two-tier app: PHP REST API backend + Vue 3 SPA frontend. No backend framework — pure PHP with Apache mod_rewrite.

### Backend (`backend/`)

Single entry point pattern: `.htaccess` rewrites all requests → `api.php`.

- **api.php** — Gateway: CORS headers, JWT auth check, route dispatch via `switch($path[0])`
- **config.php** — Lazy PDO connection to MySQL. Reads `MYSQL_*` env vars. SSL toggle for TiDB Cloud vs local.
- **auth.php** — Custom JWT (HS256) without external libraries. 8-hour expiry. `generateJWT()`, `validateJWT()`, `getAuthHeader()`
- **helpers.php** — `jsonResponse()`, `getJsonInput()`, `paginate()`, `formatThaiDate()`, `getLevelName()`, `calculateServiceYears()`
- **routes/** — Each file exports a `handle*($pdo, $method, $path)` function
- **engines/** — Business logic (e.g., `DirekEligibility.php`)

Auth bypass: `/auth/login`, `/login`, and `OPTIONS` requests skip JWT validation.

### Frontend (`frontend/src/`)

Vue 3 Composition API + Pinia + Vue Router 4.

- **composables/useApi.js** — Fetch wrapper that auto-injects `Authorization: Bearer` from auth store. Handles 401 → logout redirect.
- **stores/auth.js** — JWT in localStorage, validates `exp` claim on hydration. `login()`, `demoLogin()`, `logout()`.
- **stores/ui.js** — Toast notifications, sidebar toggle.
- **layouts/AppLayout.vue** — Dark sidebar (bg-gray-800) + white topbar + page content with route transitions.
- **pages/** — Organized by domain: `decoration/` (4 pages), `direk/` (6 pages), `chakrabardi/` (3 pages), plus Login, Dashboard, Files, Users, Settings.
- **components/** — 8 shared components: AppSidebar, AppTopbar, StatCard, StatusBadge, PaginationBar, SkeletonLoader, EmptyState, ToastContainer.

### Vite Proxy

Frontend `/api/*` requests proxy to `http://localhost:8002` with the `/api` prefix stripped. Backend sees clean paths like `/auth/login`, `/dashboard`.

## Docker Setup

Backend runs in Docker on the **same network as smart-port** project (external network `smart-port_smartport-net`). Database is the existing `smartport-db` MySQL 8 container — decoration-core creates its own `decoration_core` database inside it.

```
smartport-db (MySQL 8, port 3306) ← decoration-backend connects via Docker network
                                  ← also serves smart-port's civil_service_mgmt DB
```

Backend source is volume-mounted (`./backend:/var/www/html`) — PHP changes take effect immediately without rebuild.

## Database

MySQL 8 in `smartport-db` container. Database: `decoration_core`, charset: `utf8mb4`.

SQL files execute in order by filename prefix:
1. `00-base-tables.sql` — personnel, organization, position, users (shared HR tables)
2. `01-decoration-schema.sql` — 17 domain tables (decoration levels, criteria, requests, history for all 3 types)
3. `02-seed-data.sql` — Static reference data (12 ชั้นตรา, 7 ชั้นดิเรก, criteria rules, roles, settings)
4. `03-dev-seed.sql` — 15 personnel, 3 volunteers, sample requests

**Important:** When piping SQL via `docker exec`, always use `--default-character-set=utf8mb4` or Thai text will corrupt VARCHAR columns.

## Three Decoration Types

The system manages three distinct royal decoration workflows:

1. **ช้างเผือก-มงกุฎไทย** (`/decorations`, tables: `decoration_changpuak_*`) — 12-level system for civil servants based on position level and years of service
2. **ดิเรกคุณาภรณ์** (`/direk`, tables: `direk_*`) — 7-level system for volunteers/external persons, based on donations or meritorious work
3. **เหรียญจักรพรรดิมาลา** (`/chakrabardi`, tables: `chakrabardi_*`) — Single medal for 25+ years of government service

Each has its own request workflow: draft → submitted → screening → approved → granted/rejected.

## Thai-Specific Conventions

- All UI text is Thai. Font: Noto Sans Thai (loaded from Google Fonts).
- Dates display in Buddhist Era (พ.ศ. = AD + 543). Use `formatThaiDate()` in `helpers.php`.
- Position level codes: K1-K5 (วิชาการ), O1-O3 (ทั่วไป), D1-D2 (อำนวยการ), M1-M2 (บริหาร).
- Decoration abbreviations: ม.ป.ช., ป.ช., ท.ช., etc. — stored in `decoration_changpuak_levels.abbreviation`.

## Styling

UI design system is identical to smart-port project (`D:\hrProject\smart-port`):
- Tailwind CSS 4 with `@tailwindcss/vite` plugin (no tailwind.config.js — uses `@theme` in style.css)
- Primary color: sky/cyan palette. Government: slate palette.
- Icons: Lucide Vue Next. Charts: Chart.js + vue-chartjs.
- Dark sidebar, white content area, glassmorphism login page.
