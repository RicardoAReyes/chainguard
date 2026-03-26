Create, update, and manage WordPress content using WP-CLI running inside the Docker container `wp-nginx-mariadb-app-1`.

## Rules

1. **Always write complex HTML to a temp file first** — never embed multi-line HTML inline in `--post_content`. Shell quoting is fragile.
2. **Always create as draft first** — use `--post_status=draft`, inspect the result, then publish.
3. **Always set an explicit slug** — use `--post_name=slug-here` to control the URL.
4. **Always verify after publishing** — retrieve the post and return the live URL.

## Workflow

### Step 1 — Write HTML to a temp file
```bash
docker exec wp-nginx-mariadb-app-1 bash -c 'cat > /tmp/content.html << '"'"'EOF'"'"'
<h2>Your Heading</h2>
<p>Your paragraph content here.</p>
<ul>
  <li><strong>Item 1</strong> — description.</li>
  <li><strong>Item 2</strong> — description.</li>
  <li><strong>Item 3</strong> — description.</li>
</ul>
EOF'
```

### Step 2 — Create as draft with explicit slug
```bash
docker exec wp-nginx-mariadb-app-1 wp post create \
  --path=/var/www/html \
  --post_type=page \
  --post_title="Page Title" \
  --post_name="page-slug" \
  --post_content="$(docker exec wp-nginx-mariadb-app-1 cat /tmp/content.html)" \
  --post_status=draft \
  --allow-root
```

### Step 3 — Verify the draft
```bash
docker exec wp-nginx-mariadb-app-1 wp post get <ID> \
  --path=/var/www/html \
  --fields=ID,post_title,post_status,post_name,post_content \
  --allow-root
```

### Step 4 — Publish and retrieve live URL
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
