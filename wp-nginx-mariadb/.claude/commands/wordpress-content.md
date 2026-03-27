# WordPress Content Skill

Source: https://skills.sh/jezweb/claude-skills/wordpress-content

Create, update, and manage WordPress content using WP-CLI running inside the Docker container `wp-nginx-mariadb-app-1`.

## Rules

1. **Always write complex HTML to a temp file first** — never embed multi-line HTML inline in `--post_content`. Shell quoting is fragile.
2. **Always create as draft first** — use `--post_status=draft`, inspect the result, then publish.
3. **Always set an explicit slug** — use `--post_name=slug-here` to control the URL.
4. **Always verify after publishing** — retrieve the post and return the live URL.
5. **Bypass kses for rich HTML** — use `$wpdb->update()` directly to preserve inline styles, iframes, and script tags that WordPress would otherwise strip.

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

### Step 2 — Create as draft (uses wp_insert_post to avoid Phar dependency)
```bash
docker exec wp-nginx-mariadb-app-1 php -r "
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/';
require('/var/www/html/wp-load.php');
\$content = file_get_contents('/tmp/content.html');
\$id = wp_insert_post([
  'post_title'   => 'Page Title',
  'post_name'    => 'page-slug',
  'post_content' => \$content,
  'post_status'  => 'draft',
  'post_type'    => 'page',
]);
echo 'Draft ID: ' . \$id . PHP_EOL;
" 2>/dev/null
```

### Step 3 — Verify the draft
```bash
docker exec wp-nginx-mariadb-app-1 php -r "
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/';
require('/var/www/html/wp-load.php');
\$p = get_post(<ID>);
echo 'Title:  ' . \$p->post_title . PHP_EOL;
echo 'Slug:   ' . \$p->post_name . PHP_EOL;
echo 'Status: ' . \$p->post_status . PHP_EOL;
" 2>/dev/null
```

### Step 4 — Publish via \$wpdb->update (preserves inline styles, iframes, script tags)
```bash
docker exec wp-nginx-mariadb-app-1 php -r "
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/';
require('/var/www/html/wp-load.php');
global \$wpdb;
\$content = file_get_contents('/tmp/content.html');
\$wpdb->update(\$wpdb->posts, ['post_content' => \$content, 'post_status' => 'publish'], ['ID' => <ID>], ['%s','%s'], ['%d']);
clean_post_cache(<ID>);
echo 'Live URL: ' . get_permalink(<ID>) . PHP_EOL;
" 2>/dev/null
```

## Notes

- **WP-CLI is unavailable** in this container (Phar extension not compiled). Use `php -r` with `wp-load.php` instead.
- `wp_insert_post()` applies kses content filtering — always follow with `$wpdb->update()` to restore full HTML fidelity.
- The Docker container name is `wp-nginx-mariadb-app-1`.
- WordPress root is `/var/www/html`.
