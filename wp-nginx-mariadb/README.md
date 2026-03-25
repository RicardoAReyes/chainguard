# WordPress + Nginx + MariaDB

A containerized WordPress stack built entirely on [Chainguard](https://chainguard.dev) hardened images, with a Node.js-powered theme build pipeline sourcing JavaScript libraries from the Chainguard JS registry.

## Stack

| Container | Image | Role |
|---|---|---|
| `app` | `cgr.dev/chainguard-private/wordpress:latest-dev` | PHP-FPM / WordPress |
| `nginx` | `cgr.dev/chainguard-private/nginx:latest` | Reverse proxy (port 8000) |
| `mariadb` | `cgr.dev/chainguard-private/mariadb:latest` | Database |
| `node` | `cgr.dev/chainguard-private/node:latest` | JS build (ephemeral) |

## Chainguard Libraries

### APK Packages (installed into `app` via Dockerfile)
- `php-8.4-gd` — image manipulation
- `php-8.4-xdebug` — local debugging

### JavaScript Libraries (sourced from `libraries.cgr.dev/javascript`)
| Package | Version | SLSA Attested |
|---|---|---|
| `alpinejs` | `2.8.2` | ✅ |
| `chart.js` | `4.5.1` | ✅ |
| `motion` | `12.35.1` | ✅ |

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

## Project Structure

```
wp-nginx-mariadb/
├── docker-compose.yaml          # Stack definition
├── Dockerfile                   # Custom WordPress image
├── nginx.conf                   # Nginx reverse proxy config
├── apko.yaml                    # Chainguard image spec
├── .env.example                 # Environment variable template
├── diagram.html                 # Architecture diagram
└── themes/
    └── twentytwentyfour-child/
        ├── src/index.js         # JS entry point
        ├── build/               # Compiled output (git ignored)
        ├── node_modules/        # Dependencies (git ignored)
        ├── package.json         # npm manifest
        ├── .npmrc.example       # Registry config template
        ├── functions.php        # WordPress enqueue logic
        └── style.css            # Theme header
```

## Verify Chainguard Attestations

```bash
# View SLSA provenance for chart.js
npm view chart.js@4.5.1 dist --registry https://libraries.cgr.dev/javascript

# Verify attestation endpoint
curl -u "username:token" https://libraries.cgr.dev/javascript/-/npm/v1/attestations/chart.js@4.5.1
```
