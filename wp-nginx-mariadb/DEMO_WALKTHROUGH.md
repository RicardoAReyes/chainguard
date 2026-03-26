# Demo Walkthrough — Claude Skills for WordPress Content

A live CLI session demonstrating how loading a Claude skill changes the quality of AI-assisted WordPress content creation.

**Skill:** `wordpress-content`
**Source:** [skills.sh/jezweb/claude-skills/wordpress-content](https://skills.sh/jezweb/claude-skills/wordpress-content)

Import in Claude Code:
```
import the wordpress-content skill from https://skills.sh/jezweb/claude-skills/wordpress-content
```

---

## Setup

Stack should already be running. If not:

```bash
docker compose up -d --build
```

Verify WordPress is live at **http://localhost:8000**

---

## Act 1 — Without a Skill (Naive Attempt)

> "Claude, create a WordPress page called 'About Us'."

Claude has no skill loaded — it guesses at WP-CLI usage and produces the minimal working command:

```bash
docker exec wp-nginx-mariadb-app-1 wp post create \
  --path=/var/www/html \
  --post_type=page \
  --post_title="About Us" \
  --post_content="Welcome to our company. We build great things." \
  --post_status=publish \
  --allow-root
```

**Result (post ID 8):**

```
Field         Value
ID            8
post_title    About Us
post_status   publish   ← published immediately, no review
post_content  Welcome to our company. We build great things.  ← plain text, no HTML
post_name     about-us
guid          http://localhost:8000/?page_id=8
```

View it: **http://localhost:8000/?page_id=8**

**What's missing:**
- No HTML structure (no headings, no paragraphs, no layout blocks)
- Published immediately — no draft-first review
- No SEO slug, no excerpt, no page template assignment
- Shell quoting will break the moment content has quotes or special characters
- No verification step — Claude has no idea if it rendered correctly

---

## Act 2 — Load the Skill

In Claude Code, fetch and install the `wordpress-content` skill by asking Claude directly:

```
import the wordpress-content skill from https://skills.sh/jezweb/claude-skills/wordpress-content
```

Claude will fetch the skill content from the URL and write it to `.claude/commands/wordpress-content.md`, making `/wordpress-content` available as a slash command in this project.

This gives Claude structured knowledge of:

| Capability | Detail |
|---|---|
| HTML authoring | Writes content to a temp file — avoids fragile shell quoting |
| Safe workflow | Draft → inspect → publish (never publishes blind) |
| Taxonomy | Creates/assigns categories and tags |
| Media | Imports images from URL or local path |
| Menus | Creates nav menus and assigns locations |
| ACF metadata | Sets custom field values when ACF is active |
| Verification | Retrieves the live URL and confirms post status after publish |

---

## Act 3 — With the Skill

> "Create a WordPress page called 'Our Services' with a heading, an intro paragraph, and a 3-item list of services. Use a clean slug, draft first, verify, then publish."

**Step 1 — Claude writes HTML to a temp file (no quoting fragility):**

```bash
docker exec wp-nginx-mariadb-app-1 bash -c 'cat > /tmp/our-services.html << '"'"'EOF'"'"'
<h2>What We Do</h2>
<p>We deliver reliable, secure solutions for teams that care about quality.</p>
<ul>
  <li><strong>Hardened Container Images</strong> — minimal, CVE-free base images for production.</li>
  <li><strong>Supply Chain Security</strong> — SLSA-attested packages and signed provenance.</li>
  <li><strong>Developer Tooling</strong> — fast, reproducible builds from trusted registries.</li>
</ul>
EOF'
```

**Step 2 — Create as draft with proper slug:**

```bash
docker exec wp-nginx-mariadb-app-1 wp post create \
  --path=/var/www/html \
  --post_type=page \
  --post_title="Our Services" \
  --post_name="our-services" \
  --post_content="$(docker exec wp-nginx-mariadb-app-1 cat /tmp/our-services.html)" \
  --post_status=draft \
  --allow-root
```

**Step 3 — Verify draft before publishing:**

```bash
docker exec wp-nginx-mariadb-app-1 wp post get <ID> \
  --path=/var/www/html \
  --fields=ID,post_title,post_status,post_name,post_content \
  --allow-root
```

**Step 4 — Publish and get live URL:**

```bash
docker exec wp-nginx-mariadb-app-1 wp post update <ID> \
  --path=/var/www/html \
  --post_status=publish \
  --allow-root

docker exec wp-nginx-mariadb-app-1 wp post get <ID> \
  --path=/var/www/html \
  --field=link \
  --allow-root
```

**Result:** **http://localhost:8000/our-services/**

---

## Act 4 — Going Further: Rich Visual Content

> "Create a WordPress page called 'Our Products' with a heading, an intro paragraph, and a 3-item random IT products services with visual. Use a clean slug, draft first, verify, then publish."

The skill handles more than plain lists — it produces fully styled, on-brand layouts using the site's color palette.

**Step 1 — Write HTML with visual card layout to temp file:**

```bash
docker exec wp-nginx-mariadb-app-1 bash -c 'cat > /tmp/our-products.html << '"'"'EOF'"'"'
<h2>Built for Modern Infrastructure</h2>
<p>Our products help engineering teams ship faster with security baked in from the start — not bolted on after. Every tool in our lineup is designed around minimal attack surface and verified provenance.</p>

<div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:2rem;">

  <div style="flex:1;min-width:260px;background:#3D405B;color:#F4F1DE;border-radius:8px;padding:1.75rem;">
    <div style="font-size:2.5rem;margin-bottom:0.75rem;">🛡️</div>
    <h3 style="color:#F2CC8F;margin-top:0;">Chainguard Images</h3>
    <p style="margin:0;line-height:1.6;">Hardened, distroless container images with zero known CVEs. Updated daily and SLSA-attested so your supply chain is provably clean.</p>
    <div style="margin-top:1.25rem;padding:0.5rem 0.75rem;background:#F2CC8F;color:#3D405B;border-radius:4px;display:inline-block;font-weight:600;font-size:0.85rem;">Zero CVEs · Daily rebuilds</div>
  </div>

  <div style="flex:1;min-width:260px;background:#81B29A;color:#F4F1DE;border-radius:8px;padding:1.75rem;">
    <div style="font-size:2.5rem;margin-bottom:0.75rem;">📦</div>
    <h3 style="color:#3D405B;margin-top:0;">Chainguard Libraries</h3>
    <p style="margin:0;line-height:1.6;">Secure, attested JavaScript and Python packages sourced directly from our registry. Drop-in replacements for npm and PyPI with full provenance.</p>
    <div style="margin-top:1.25rem;padding:0.5rem 0.75rem;background:#3D405B;color:#F4F1DE;border-radius:4px;display:inline-block;font-weight:600;font-size:0.85rem;">SLSA Level 3 · Signed SBOMs</div>
  </div>

  <div style="flex:1;min-width:260px;background:#E07A5F;color:#F4F1DE;border-radius:8px;padding:1.75rem;">
    <div style="font-size:2.5rem;margin-bottom:0.75rem;">🔍</div>
    <h3 style="color:#F4F1DE;margin-top:0;">Chainguard Enforce</h3>
    <p style="margin:0;line-height:1.6;">Continuous policy enforcement for your Kubernetes clusters. Automatically blocks unverified workloads and surfaces risk across your entire fleet.</p>
    <div style="margin-top:1.25rem;padding:0.5rem 0.75rem;background:#3D405B;color:#F4F1DE;border-radius:4px;display:inline-block;font-weight:600;font-size:0.85rem;">Policy-as-code · K8s native</div>
  </div>

</div>
EOF'
```

**Steps 2–4** follow the same draft → verify → publish pattern.

**Result:** **http://localhost:8000/our-products/**

| Card | Color | Badge |
|---|---|---|
| Chainguard Images 🛡️ | Space Cadet `#3D405B` | Sandy Yellow |
| Chainguard Libraries 📦 | Sage `#81B29A` | Space Cadet |
| Chainguard Enforce 🔍 | Terra Cotta `#E07A5F` | Space Cadet |

> The skill knew to write complex inline-styled HTML to a temp file — the same quoting-safety rule that applied to a plain list also protects arbitrarily rich markup.

---

## Side-by-Side Comparison

| | Act 1 — No Skill | Act 3 — With Skill |
|---|---|---|
| Content | Plain text string | Structured HTML in temp file |
| Shell safety | Breaks on quotes/special chars | Temp file avoids all quoting issues |
| Publish flow | Immediate publish | Draft → verify → publish |
| Slug | Auto-generated | Explicitly set (`our-services`) |
| URL | `?page_id=8` | `/our-services/` |
| Verification | None | Confirms status + returns live URL |
| Extensible | One-off guess | Repeatable pattern for any content type |

---

## What This Demonstrates

- **Without a skill**, Claude can use WP-CLI — but it guesses, skips steps, and won't scale.
- **With a skill**, Claude follows a proven, structured workflow every time.
- Skills are composable: the same pattern works for posts, pages, media, menus, and ACF fields.
- Skills are shareable: one `/import` URL gives any team member the same capability.

---

## Bonus — Observability Stack

The demo also includes a full vulnerability scanning pipeline built with Chainguard images:

```bash
# Run Grype scans on all 7 container images
bash bin/scan.sh
```

Results feed into Prometheus → Grafana and are embedded in WordPress at:
**http://localhost:8000/security-scan-report/**

The architecture diagram (with pan/zoom) is at:
**http://localhost:8000/diagram/**  or  `open diagram.html`
