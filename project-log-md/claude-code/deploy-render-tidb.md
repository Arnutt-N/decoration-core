# Deploy Decoration Core — Render + TiDB Cloud

> คู่มือการ deploy ระบบเครื่องราชอิสริยาภรณ์ขึ้น production
> - **Frontend**: Render Static Site (Vue 3 SPA)
> - **Backend**: Render Docker Web Service (PHP 8.3 + Apache)
> - **Database**: TiDB Cloud (MySQL-compatible, port 4000 + SSL)

---

## สถาปัตยกรรม Production

```
[User Browser]
      │
      ▼
┌─────────────────────────┐
│  Render Static Site     │
│  (Frontend - Vue 3 SPA) │
│  /api/* → rewrite       │──────┐
│  /*    → /index.html    │      │
└─────────────────────────┘      │
                                 ▼
                    ┌──────────────────────┐
                    │  Render Docker       │
                    │  (Backend - PHP/Apache)│
                    │  port 80 → api.php   │
                    └──────────┬───────────┘
                               │ PDO (port 4000 + SSL)
                               ▼
                    ┌──────────────────────┐
                    │  TiDB Cloud          │
                    │  decoration_core DB  │
                    │  (MySQL-compatible)  │
                    └──────────────────────┘
```

---

## ขั้นตอนที่ 1: ตั้งค่า TiDB Cloud

### 1.1 สร้าง Cluster

1. ไปที่ [tidbcloud.com](https://tidbcloud.com) → สร้างบัญชี/เข้าสู่ระบบ
2. **Create Cluster** → เลือก **Serverless** (free tier)
3. ตั้งชื่อ cluster เช่น `decoration-core`
4. เลือก region: `ap-southeast-1` (Singapore) — ใกล้ประเทศไทยที่สุด
5. รอ cluster พร้อมใช้งาน (~1-2 นาที)

### 1.2 สร้าง Database + User

1. ในหน้า cluster → **Connect** → จดข้อมูล:
   - **Host**: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` (ตัวอย่าง)
   - **Port**: `4000`
   - **User**: ตามที่ TiDB กำหนด (เช่น `2abc123.root`)
   - **Password**: สร้างรหัสผ่านใหม่

2. เชื่อมต่อผ่าน MySQL client:
   ```bash
   mysql -u '2abc123.root' -h gateway01.ap-southeast-1.prod.aws.tidbcloud.com -P 4000 --ssl-mode=VERIFY_IDENTITY -p
   ```

3. สร้าง database:
   ```sql
   CREATE DATABASE decoration_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE decoration_core;
   ```

### 1.3 Import Schema + Seed Data

รัน SQL files ตามลำดับ:

```bash
TIDB_HOST="gateway01.ap-southeast-1.prod.aws.tidbcloud.com"
TIDB_USER="2abc123.root"
TIDB_PORT=4000

# รันทีละไฟล์ตามลำดับ
for f in 00-base-tables.sql 01-decoration-schema.sql 02-seed-data.sql 04-schema-enhancement.sql 03-dev-seed.sql; do
  echo "--- Importing $f ---"
  mysql -u "$TIDB_USER" -h "$TIDB_HOST" -P $TIDB_PORT --ssl-mode=VERIFY_IDENTITY -p decoration_core < "database/$f"
done
```

> **หมายเหตุ**: `04-schema-enhancement.sql` ต้องรันก่อน `03-dev-seed.sql` เพราะ seed data ใช้คอลัมน์ที่เพิ่มใน migration

### 1.4 ตรวจสอบ

```sql
USE decoration_core;
SHOW TABLES;
SELECT COUNT(*) FROM personnel;      -- ควรได้ 15
SELECT COUNT(*) FROM decoration_changpuak_levels;  -- ควรได้ 12
```

---

## ขั้นตอนที่ 2: เตรียม Backend สำหรับ Production

### 2.1 สร้าง Dockerfile (production)

สร้างไฟล์ `backend/Dockerfile` (แยกจาก `Dockerfile.dev`):

```dockerfile
# =========================================================================
# Production Dockerfile — Decoration Core Backend
# =========================================================================
FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPcache สำหรับ production
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.max_accelerated_files=10000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=0" > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy source code
COPY . .

# สร้าง uploads directory
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

# PassEnv — ให้ Apache ส่ง env vars เข้า PHP
RUN echo "PassEnv MYSQL_HOST MYSQL_PORT MYSQL_DATABASE MYSQL_USER MYSQL_PASSWORD MYSQL_SSL JWT_SECRET" \
    >> /etc/apache2/conf-enabled/passenv.conf

# UTF-8 encoding
RUN printf "AddDefaultCharset UTF-8\n" > /etc/apache2/conf-enabled/charset.conf \
    && printf "default_charset = \"UTF-8\"\n" > /usr/local/etc/php/conf.d/charset.ini

EXPOSE 80
```

> **สำคัญ**: `PassEnv` จำเป็นมาก — ถ้าไม่มี Apache จะไม่ส่ง environment variables เข้า PHP ทำให้ `getenv('MYSQL_HOST')` ได้ค่าว่าง

### 2.2 สร้าง .dockerignore

สร้างไฟล์ `backend/.dockerignore`:

```
Dockerfile*
.git
.env*
uploads/*
*.md
```

---

## ขั้นตอนที่ 3: สร้าง render.yaml

สร้างไฟล์ `render.yaml` ที่ root ของ project:

```yaml
services:
  # =============================================
  # Frontend — Vue 3 Static Site
  # =============================================
  - type: web
    name: decoration-core
    runtime: static
    branch: main
    rootDir: frontend
    buildCommand: npm install && npm run build
    staticPublishPath: dist
    buildFilter:
      paths:
        - frontend/**
        - render.yaml
    envVars:
      - key: VITE_API_URL
        value: /api
    routes:
      - type: rewrite
        source: /api/*
        destination: https://decoration-backend.onrender.com/*
      - type: rewrite
        source: /*
        destination: /index.html

  # =============================================
  # Backend — PHP 8.3 Docker
  # =============================================
  - type: web
    name: decoration-backend
    runtime: docker
    branch: main
    rootDir: backend
    dockerfilePath: Dockerfile
    dockerContext: .
    healthCheckPath: /
    buildFilter:
      paths:
        - backend/**
        - database/**
        - render.yaml
    envVars:
      - key: MYSQL_HOST
        sync: false
      - key: MYSQL_PORT
        value: "4000"
      - key: MYSQL_DATABASE
        value: decoration_core
      - key: MYSQL_USER
        sync: false
      - key: MYSQL_PASSWORD
        sync: false
      - key: MYSQL_SSL
        value: "true"
      - key: JWT_SECRET
        generateValue: true
```

### ความหมายของ `sync: false`

- `sync: false` = ต้องกรอกค่าเองใน Render Dashboard (ไม่เก็บใน git)
- ใช้สำหรับ secrets: `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`
- `generateValue: true` = Render สร้าง random string ให้อัตโนมัติ (JWT_SECRET)

---

## ขั้นตอนที่ 4: อัพเดท CORS ใน api.php

เพิ่ม Render URL ใน `$allowedOrigins`:

```php
$allowedOrigins = [
    'http://localhost:5174',
    'http://localhost:8081',
    'https://decoration-core.onrender.com',   // Frontend
    'https://decoration-backend.onrender.com', // Backend (ถ้า frontend เรียกตรง)
];
```

> ชื่อ `decoration-core` และ `decoration-backend` ต้องตรงกับ `name` ใน `render.yaml`

---

## ขั้นตอนที่ 5: Deploy บน Render

### 5.1 สร้าง Services

1. ไปที่ [dashboard.render.com](https://dashboard.render.com)
2. **New** → **Blueprint** → เชื่อม GitHub repo `Arnutt-N/decoration-core`
3. Render จะอ่าน `render.yaml` แล้วสร้าง 2 services อัตโนมัติ:
   - `decoration-core` (Static Site)
   - `decoration-backend` (Docker Web Service)

### 5.2 ตั้งค่า Environment Variables

ไปที่ **decoration-backend** → **Environment** → กรอกค่า:

| Key | Value | หมายเหตุ |
|-----|-------|----------|
| `MYSQL_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | จาก TiDB Cloud Connect |
| `MYSQL_PORT` | `4000` | ตั้งไว้ใน render.yaml แล้ว |
| `MYSQL_DATABASE` | `decoration_core` | ตั้งไว้ใน render.yaml แล้ว |
| `MYSQL_USER` | `2abc123.root` | จาก TiDB Cloud |
| `MYSQL_PASSWORD` | `(รหัสผ่าน TiDB)` | จาก TiDB Cloud |
| `MYSQL_SSL` | `true` | ตั้งไว้ใน render.yaml แล้ว |
| `JWT_SECRET` | `(auto-generated)` | Render สร้างให้ |

### 5.3 Deploy

1. Push code ขึ้น `main` branch
2. Render จะ auto-deploy ทั้ง frontend + backend
3. รอ build เสร็จ (~2-5 นาที)

### 5.4 ตรวจสอบ

```bash
# ทดสอบ Backend
curl -s https://decoration-backend.onrender.com/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin","password":"admin123"}'

# ควรได้ {"token":"eyJ...","user":{...}}

# ทดสอบ Frontend
# เปิด https://decoration-core.onrender.com ในเบราว์เซอร์
```

---

## ขั้นตอนที่ 6: Auto-Deploy ด้วย GitHub Actions (ทางเลือก)

สร้างไฟล์ `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Render

on:
  push:
    branches: [main]

jobs:
  deploy:
    name: Trigger Render Deploy
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - name: Deploy to Render
        if: ${{ secrets.RENDER_DEPLOY_HOOK_URL != '' }}
        run: |
          curl -X POST "$RENDER_DEPLOY_HOOK_URL" \
            --fail --silent --show-error
        env:
          RENDER_DEPLOY_HOOK_URL: ${{ secrets.RENDER_DEPLOY_HOOK_URL }}
```

### ตั้งค่า Deploy Hook:
1. Render Dashboard → **decoration-backend** → **Settings** → **Deploy Hook** → Copy URL
2. GitHub repo → **Settings** → **Secrets and variables** → **Actions** → เพิ่ม:
   - Name: `RENDER_DEPLOY_HOOK_URL`
   - Value: URL จาก Render

---

## Troubleshooting

### ปัญหาที่พบบ่อย

| ปัญหา | สาเหตุ | แก้ไข |
|--------|--------|-------|
| Backend 503 "Database connection failed" | TiDB credentials ผิด หรือ SSL ไม่เปิด | ตรวจสอบ env vars ใน Render, ต้อง `MYSQL_SSL=true` |
| ภาษาไทยเพี้ยน/เป็น ??? | ไม่มี `charset.conf` หรือ ไม่ใส่ `--default-character-set=utf8mb4` ตอน import | ตรวจ Dockerfile มี UTF-8 config, import SQL ด้วย `--default-character-set=utf8mb4` |
| Frontend API 404 | Render rewrite rule ไม่ตรง | ตรวจ `render.yaml` routes: `/api/*` → backend URL |
| Frontend ขาว/blank | SPA routing ไม่ทำงาน | ต้องมี rewrite `/*` → `/index.html` |
| CORS error | Backend ไม่อนุญาต origin | เพิ่ม Render frontend URL ใน `$allowedOrigins` ของ `api.php` |
| env vars ว่างใน PHP | Apache ไม่ส่ง env vars | ต้องมี `PassEnv` ใน Dockerfile |
| Render build ช้า/fail | npm install ล้มเหลว | ลบ platform-specific deps (rollup-win32, tailwindcss-oxide-win32, lightningcss-win32) ออกจาก `package.json` ก่อน deploy |

### ลบ Windows-specific dependencies ก่อน deploy

`package.json` มี Windows-only packages ที่ Render (Linux) ไม่ต้องการ:

```json
// ลบ 3 บรรทัดนี้ออก (หรือย้ายไป optionalDependencies)
"@rollup/rollup-win32-x64-msvc": "^4.60.0",
"@tailwindcss/oxide-win32-x64-msvc": "^4.2.2",
"lightningcss-win32-x64-msvc": "^1.32.0",
```

---

## สรุป Checklist

- [ ] สร้าง TiDB Cloud cluster (Serverless, ap-southeast-1)
- [ ] สร้าง `decoration_core` database + import SQL 5 ไฟล์
- [ ] สร้าง `backend/Dockerfile` (production version)
- [ ] สร้าง `backend/.dockerignore`
- [ ] สร้าง `render.yaml` ที่ root
- [ ] อัพเดท CORS ใน `api.php`
- [ ] ลบ Windows-specific deps จาก `package.json`
- [ ] Push ขึ้น GitHub `main` branch
- [ ] สร้าง Blueprint บน Render → เชื่อม repo
- [ ] กรอก env vars (MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD)
- [ ] ทดสอบ Backend API: `curl https://decoration-backend.onrender.com/auth/login`
- [ ] ทดสอบ Frontend: เปิด `https://decoration-core.onrender.com`
- [ ] (ทางเลือก) ตั้งค่า GitHub Actions deploy hook

---

> เอกสารนี้สร้างจากการวิเคราะห์ codebase ของ decoration-core และอ้างอิงรูปแบบ deploy ของ smart-port project
> สร้างเมื่อ: 25 มี.ค. 2569
