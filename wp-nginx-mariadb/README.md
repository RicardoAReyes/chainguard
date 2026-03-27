# WordPress + Nginx + MariaDB

A containerized WordPress stack built entirely on [Chainguard](https://chainguard.dev) hardened images, with a Node.js-powered theme build pipeline, vulnerability scanning via Grype, and a full observability stack (Prometheus + Grafana).

Also includes a **Ghost CMS** image built from scratch using [melange](https://github.com/chainguard-dev/melange) + [apko](https://github.com/chainguard-dev/apko) — packaging Ghost entirely on Wolfi packages with no upstream base image.

## Stack

### Application

| Container | Image | Role |
|---|---|---|
| `app` | `cgr.dev/chainguard-private/wordpress:latest-dev` | PHP-FPM / WordPress |
| `nginx` | `cgr.dev/chainguard-private/nginx:latest` | Reverse proxy (port 8000) |
| `mariadb` | `cgr.dev/chainguard-private/mariadb:latest` | Database |
| `node` | `cgr.dev/chainguard-private/node:latest` | JS build (ephemeral) |

### Observability

| Container | Image | Role |
|---|---|---|
| `metrics-exporter` | `cgr.dev/chainguard-private/node:latest` | Reads Grype scan JSON, exposes `:9100/metrics` |
| `prometheus` | `cgr.dev/chainguard-private/prometheus:latest` | Scrapes metrics every 15s |
| `grafana` | `cgr.dev/chainguard-private/grafana:latest` | CVE dashboard, proxied at `/grafana/` |

## Chainguard Assets

### Container Images (`cgr.dev/chainguard-private`)
All 7 runtime images are sourced from Chainguard's private registry — hardened, minimal, and rebuilt daily with zero known CVEs.

### APK Packages (installed into `app` via Dockerfile)
- `php-8.4-gd` — image manipulation
- `php-8.4-xdebug` — local debugging

### JavaScript Libraries (`libraries.cgr.dev/javascript`)
| Package | Version | SLSA Attested |
|---|---|---|
| `alpinejs` | `2.8.2` | ✅ |
| `chart.js` | `4.5.1` | ✅ |
| `motion` | `12.35.1` | ✅ |

## AI Tooling

WordPress content is managed using a Claude AI Skill:

| Skill | Source | Purpose |
|---|---|---|
| `wordpress-content` | [skills.sh/jezweb/claude-skills/wordpress-content](https://skills.sh/jezweb/claude-skills/wordpress-content) | Structured WP-CLI workflow: draft → verify → publish |

Import the skill in Claude Code:
```
import the wordpress-content skill from https://skills.sh/jezweb/claude-skills/wordpress-content
```

## Prerequisites

- Docker Desktop
- Access to `cgr.dev/chainguard-private` (Chainguard account)
- Access to `libraries.cgr.dev/javascript` (Chainguard JS registry credentials)

## Setup

**1. Configure environment variables:**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

**2. Configure npm registry credentials:**
```bash
cp themes/twentytwentyfour-child/.npmrc.example themes/twentytwentyfour-child/.npmrc
# Edit .npmrc with your Chainguard JS registry credentials
```

**3. Build the custom WordPress image:**
```bash
docker compose build app
```

**4. Install theme dependencies:**
```bash
# Install all packages from public npm
docker compose run --rm node install

# Replace runtime libraries with Chainguard-sourced versions
docker compose run --rm node run install:chainguard
```

**5. Build theme JS assets:**
```bash
docker compose run --rm node run build
```

**6. Start the stack:**
```bash
docker compose up -d
```

Visit http://localhost:8000 to complete the WordPress installer.

> **Restore from scratch:** `bash bin/setup.sh` — tears down and rebuilds the full stack including WordPress install, pages, and theme.

## Vulnerability Scanning

Run Grype scans on all container images on demand:

```bash
bash bin/scan.sh
```

Results are saved to `scans/<image>/<YYYYMMDDTHHMMSSZ>.json`. The metrics-exporter reads these files and exposes them as Prometheus metrics, which Grafana visualises in the **Grype Security Scan Report** dashboard.

Scanned images: `wordpress`, `nginx`, `mariadb`, `node`, `grype`, `prometheus`, `grafana`

## Grafana Dashboard

The CVE dashboard is embedded in WordPress at **http://localhost:8000/security-scan-report/**

It is also accessible directly at **http://localhost:8000/grafana/** (proxied through nginx, same-origin).

## Theme Build

The `twentytwentyfour-child` theme uses `@wordpress/scripts` (webpack) to compile `src/index.js` into `build/index.js`.

```
src/index.js
  ├── alpinejs       → lightweight interactivity
  ├── chart.js       → data visualization
  └── motion         → animations and scroll effects
```

| Source | Packages | Registry |
|---|---|---|
| Runtime libs | `alpinejs`, `chart.js`, `motion` | `libraries.cgr.dev/javascript` |
| Build tooling | `@wordpress/scripts` + 749 deps | `registry.npmjs.org` |

## Architecture Diagram

Open `diagram.html` in a browser for an interactive, color-coded architecture diagram with pan/zoom:

```bash
open diagram.html
```

Color legend:
- **Green** — Chainguard assets (images, Wolfi packages, JS libs)
- **Amber** — Public upstream (npm registry, build tooling)
- **Purple (runtime)** — Docker Compose containers
- **Blue** — Observability stack
- **Gray** — Bind mounts
- **Purple (AI)** — Claude AI Skill

## Project Structure

```
wp-nginx-mariadb/
├── docker-compose.yaml          # Stack definition
├── Dockerfile                   # Custom WordPress image
├── nginx.conf                   # Nginx reverse proxy config
├── apko.yaml                    # Chainguard image spec (WordPress)
├── diagram.html                 # Interactive architecture diagram
├── ghost.melange.yaml           # Ghost APK build recipe
├── ghost.apko.yaml              # Ghost OCI image spec
├── melange.rsa.pub              # APK signing public key
├── sbom-*.spdx.json             # SPDX SBOMs (apko-generated)
├── bin/
│   ├── scan.sh                  # On-demand Grype vulnerability scanner
│   └── setup.sh                 # Full stack restore script
├── metrics-exporter/
│   ├── server.js                # Prometheus metrics exporter
│   └── Dockerfile
├── prometheus/
│   └── prometheus.yml           # Scrape config
├── grafana/
│   └── provisioning/
│       ├── datasources/         # Prometheus datasource
│       └── dashboards/          # Grype scan report dashboard
├── scans/                       # Grype JSON results (git ignored)
│   └── <image>/<timestamp>.json
└── themes/
    └── twentytwentyfour-child/
        ├── src/index.js         # JS entry point
        ├── build/               # Compiled output (git ignored)
        ├── node_modules/        # Dependencies (git ignored)
        ├── package.json         # npm manifest
        ├── .npmrc.example       # Registry config template
        ├── functions.php        # WordPress enqueue logic
        └── style.css            # Theme styles
```

## Ghost CMS — apko + melange Build

A standalone Ghost CMS 6.23.0 OCI image built entirely on Wolfi packages using Chainguard's open-source build toolchain.

### How it works

| Tool | Role |
|---|---|
| `melange` | Builds a `ghost-6.23.0-r0.apk` from the Ghost npm package |
| `apko` | Assembles the final OCI image from Wolfi base + Ghost APK |

### Build the image

```bash
# 1 — generate a signing key (first time only)
docker run --rm -v "$PWD:/work" -w /work \
  cgr.dev/chainguard/melange:latest keygen

# 2 — build the Ghost APK
docker run --rm --privileged -v "/tmp/ghost-build:/work" -w /work \
  cgr.dev/chainguard/melange:latest \
  build ghost.melange.yaml \
  --arch amd64 \
  --signing-key melange.rsa \
  --out-dir /work/packages

# 3 — assemble the OCI image
docker run --rm -v "$PWD:/work" \
  cgr.dev/chainguard/apko:latest \
  build ghost.apko.yaml ghost:latest /work/ghost.tar --arch amd64

# 4 — load into Docker
docker load < ghost.tar
```

### Run Ghost

```bash
# Copy default themes from image into the content volume (first time only)
docker create --name ghost-tmp ghost:latest-amd64
docker cp ghost-tmp:/var/lib/ghost/content/themes /tmp/ghost-content/themes
docker rm ghost-tmp

# Start Ghost
mkdir -p /tmp/ghost-content/{data,images,apps,logs,settings}
docker run -d \
  --name ghost-cms \
  -v /tmp/ghost-content:/var/lib/ghost/content \
  -p 2368:2368 \
  ghost:latest-amd64 \
  /usr/bin/node /var/lib/ghost/index.js
```

- **Site:** http://localhost:2368
- **Admin:** http://localhost:2368/ghost

### What's in the image

| Layer | Source |
|---|---|
| OS base | `wolfi-baselayout`, `busybox`, `ca-certificates-bundle` (Wolfi) |
| Runtime | `nodejs-22`, `sqlite-libs` (Wolfi) |
| App | `ghost-6.23.0-r0.apk` built by melange |
| Config | `ghost-config-sqlite-6.23.0-r0.apk` (SQLite config) |

Image size: **~177 MB** (OCI tar). Starts in ~1.5s.

### Build files

| File | Purpose |
|---|---|
| `ghost.melange.yaml` | APK recipe: npm pack → extract → install → compile sqlite3 |
| `ghost.apko.yaml` | OCI image spec: Wolfi packages + Ghost APK |
| `melange.rsa.pub` | APK index signing public key |
| `sbom-*.spdx.json` | SPDX SBOMs generated by apko |

---

## Verify Chainguard Attestations

```bash
# View SLSA provenance for chart.js
npm view chart.js@4.5.1 dist --registry https://libraries.cgr.dev/javascript

# Verify attestation endpoint
curl -u "username:token" https://libraries.cgr.dev/javascript/-/npm/v1/attestations/chart.js@4.5.1
```
