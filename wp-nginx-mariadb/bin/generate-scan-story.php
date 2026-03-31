<?php
/**
 * bin/generate-scan-story.php
 *
 * Generates a human-readable security blog post from the latest Grype scan
 * summary and publishes it to WordPress. Works with or without a Claude API key.
 *
 * If ANTHROPIC_API_KEY is set, Claude writes the prose.
 * Otherwise, a data-driven template produces the article automatically.
 *
 * Usage (inside the app container):
 *   php /tmp/generate-scan-story.php
 *   ANTHROPIC_API_KEY=sk-ant-... php /tmp/generate-scan-story.php
 */

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require('/var/www/html/wp-load.php');

// ── Pull scan data from WordPress options ──────────────────────────────────

$ss          = get_option( 'grype_scan_summary', [] );
$last_import = get_option( 'grype_last_import', '' );
$images      = ['wordpress','nginx','mariadb','node','grype','prometheus','grafana'];
$severities  = ['Critical','High','Medium','Low','Unknown'];

if ( empty($ss) ) {
    echo "ERROR: No scan summary found. Run bin/scan.sh first.\n";
    exit(1);
}

$cg_total = 0; $dhi_total = 0;
$cg_critical = 0; $dhi_critical = 0;
$cg_high = 0; $dhi_high = 0;
$best_win     = ['img' => '', 'cg' => 0, 'dhi' => 0, 'diff' => 0];
$worst_dhi    = ['img' => '', 'total' => 0];
$clean_images = [];
$image_rows   = [];

foreach ( $images as $img ) {
    $cg  = $ss['cg'][$img]  ?? array_fill_keys( array_merge( $severities, ['Total'] ), 0 );
    $dhi = $ss['dhi'][$img] ?? array_fill_keys( array_merge( $severities, ['Total'] ), 0 );

    $ct = $cg['Total']  ?? 0;
    $dt = $dhi['Total'] ?? 0;

    $cg_total    += $ct;
    $dhi_total   += $dt;
    $cg_critical += $cg['Critical'] ?? 0;
    $dhi_critical += $dhi['Critical'] ?? 0;
    $cg_high     += $cg['High'] ?? 0;
    $dhi_high    += $dhi['High'] ?? 0;

    if ( $ct === 0 ) $clean_images[] = $img;
    if ( ($dt - $ct) > $best_win['diff'] ) {
        $best_win = ['img' => $img, 'cg' => $ct, 'dhi' => $dt, 'diff' => $dt - $ct];
    }
    if ( $dt > $worst_dhi['total'] ) {
        $worst_dhi = ['img' => $img, 'total' => $dt];
    }
    $image_rows[$img] = ['cg' => $ct, 'dhi' => $dt];
}

$pct_reduction = $dhi_total > 0 ? round( (1 - $cg_total / $dhi_total) * 100 ) : 0;
$scan_date     = $last_import ? date( 'F j, Y', strtotime( $last_import ) ) : date( 'F j, Y' );
$clean_count   = count( $clean_images );
$clean_list    = implode( ', ', $clean_images );

// ── Generate article ───────────────────────────────────────────────────────

$api_key = getenv('ANTHROPIC_API_KEY');

if ( $api_key && $api_key !== 'your-api-key-here' ) {

    // ── Claude API path ────────────────────────────────────────────────────
    echo "Calling Claude API...\n";

    $image_summary = implode("\n", array_map(
        fn($img) => "$img: CG={$image_rows[$img]['cg']}, DHI={$image_rows[$img]['dhi']}",
        $images
    ));

    $prompt = <<<PROMPT
You are a security writer for Chainguard. Write a compelling, punchy blog post for a NON-TECHNICAL audience based on this real vulnerability scan data.

SCAN DATA ({$scan_date}):
- Chainguard images total CVEs: {$cg_total}
- Docker Hardened Images (DHI) total CVEs: {$dhi_total}
- Reduction with Chainguard: {$pct_reduction}%
- Chainguard critical CVEs: {$cg_critical} | DHI critical CVEs: {$dhi_critical}
- Images with zero Chainguard CVEs: {$clean_list}
- Biggest win: {$best_win['img']} image ({$best_win['cg']} CG vs {$best_win['dhi']} DHI CVEs)

Per-image breakdown:
{$image_summary}

REQUIREMENTS:
- Write exactly 400-500 words
- Start with a punchy, specific headline (include the {$pct_reduction}% reduction number)
- Open with one vivid, non-technical analogy (like unlocked windows, open doors, etc.)
- Explain what a CVE is in ONE plain sentence — never again after that
- Call out the {$best_win['img']} image win specifically — make it dramatic
- Mention which images are completely clean: {$clean_list}
- End with a 2-sentence "what this means for you" conclusion
- Tone: confident, clear, slightly dramatic — like a newspaper security reporter
- NO jargon (no "attack surface", "threat vector", "remediation")
- NO markdown — return plain paragraphs only

Return JSON with exactly two fields:
{"title": "the headline", "body": "full article as plain paragraphs separated by \\n\\n"}
PROMPT;

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
        'timeout' => 60,
        'headers' => [
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ],
        'body' => wp_json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 1200,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]),
    ] );

    if ( ! is_wp_error( $response ) ) {
        $raw  = trim( json_decode( wp_remote_retrieve_body( $response ), true )['content'][0]['text'] ?? '' );
        $raw  = preg_replace( '/^```json\s*/i', '', $raw );
        $raw  = preg_replace( '/```\s*$/', '', trim($raw) );
        $data = json_decode( $raw, true );
        if ( ! empty( $data['title'] ) && ! empty( $data['body'] ) ) {
            $title     = $data['title'];
            $body_text = $data['body'];
            echo "Claude wrote the article.\n";
            goto publish;
        }
    }
    echo "Claude API failed — falling back to template.\n";
}

// ── Template path ──────────────────────────────────────────────────────────

echo "Generating article from template...\n";

$best_img_cap = ucfirst( $best_win['img'] );
$clean_img_cap = implode( ', ', array_map( 'ucfirst', $clean_images ) );

// Pick analogy based on scale
if ( $dhi_total > 2000 ) {
    $analogy = "Think of your server as a house. Running Docker's standard images is like moving in and discovering {$dhi_total} unlocked doors and windows — each one a potential entry point for anyone who wants in. Chainguard's hardened images lock all but {$cg_total} of them. Automatically. Before you even move the furniture in.";
} elseif ( $dhi_total > 500 ) {
    $analogy = "Imagine leaving {$dhi_total} sticky notes on your front door, each one listing a way a burglar could get inside. That is roughly what running standard container images looks like to a security scanner. Chainguard images reduced that list to just {$cg_total}.";
} else {
    $analogy = "Every software vulnerability is an unlocked window in your building — most go unnoticed, until someone decides to climb through. Our latest scan found {$dhi_total} of those open windows in standard Docker images. Chainguard had {$cg_total}.";
}

$clean_para = $clean_count > 0
    ? "Particularly striking: {$clean_img_cap} " . ( $clean_count === 1 ? 'came back' : 'all came back' ) . " with a perfect score — zero known vulnerabilities. That is not an accident. Chainguard rebuilds these images from scratch every single day, patching vulnerabilities as they are discovered rather than waiting for quarterly release cycles."
    : "Every single Chainguard image showed a dramatic reduction compared to its Docker equivalent, with the most hardened images coming close to a clean bill of health.";

$best_para = "The most dramatic result came from the {$best_img_cap} image. Docker's version carried {$best_win['dhi']} known vulnerabilities. Chainguard's version had {$best_win['cg']}. That is a difference of {$best_win['diff']} security gaps — in a single container image. For context, a CVE (Common Vulnerability and Exposure) is a publicly known software flaw that attackers can exploit. Each one represents a real risk.";

$critical_para = $dhi_critical > 0
    ? "Among the findings, DHI images contained {$dhi_critical} critical-severity vulnerabilities — the kind that security teams scramble to patch because they can lead directly to data breaches or system takeovers. Chainguard images carried {$cg_critical}."
    : "None of the Chainguard images carried critical-severity vulnerabilities this scan cycle — the highest-risk category that typically triggers emergency patching procedures.";

$conclusion = "Across all {$pct_reduction}% of the vulnerabilities that Chainguard eliminated, every single one is a risk your team does not have to track, patch, or explain to leadership. In a world where the next supply chain attack is always one dependency away, starting with fewer vulnerabilities is not just convenient — it is a strategy.";

$title     = "Chainguard Eliminated {$pct_reduction}% of Vulnerabilities in Our Latest Container Scan";
$body_text = implode( "\n\n", [ $analogy, $best_para, $clean_para, $critical_para, $conclusion ] );

// ── Build styled HTML ──────────────────────────────────────────────────────

publish:

$paragraphs = array_filter( array_map( 'trim', explode( "\n\n", $body_text ) ) );
$html_paras = implode( "\n", array_map(
    fn($p) => "<p style=\"margin:0 0 1.5em;color:#374151;line-height:1.8;font-size:1.02em;\">$p</p>",
    $paragraphs
));

// Per-image comparison mini-table
$table_rows = '';
foreach ( $image_rows as $img => $counts ) {
    $pct  = $counts['dhi'] > 0 ? round( (1 - $counts['cg'] / $counts['dhi']) * 100 ) : 100;
    $bar_w = min( $pct, 100 );
    $bar_color = $pct >= 90 ? '#81B29A' : ( $pct >= 50 ? '#F2CC8F' : '#E07A5F' );
    $zero_badge = $counts['cg'] === 0 ? ' <span style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.72em;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:6px;">Clean</span>' : '';
    $table_rows .= <<<ROW
    <tr style="border-bottom:1px solid #f3f4f6;">
      <td style="padding:10px 14px;font-weight:600;color:#3D405B;font-size:0.88em;">{$img}{$zero_badge}</td>
      <td style="padding:10px 14px;text-align:center;color:#E07A5F;font-weight:700;font-size:0.88em;">{$counts['dhi']}</td>
      <td style="padding:10px 14px;text-align:center;color:#16a34a;font-weight:700;font-size:0.88em;">{$counts['cg']}</td>
      <td style="padding:10px 14px;width:120px;">
        <div style="background:#f3f4f6;border-radius:4px;height:8px;overflow:hidden;">
          <div style="background:{$bar_color};width:{$bar_w}%;height:100%;border-radius:4px;"></div>
        </div>
        <div style="font-size:0.75em;color:#6b7280;margin-top:3px;">{$pct}% safer</div>
      </td>
    </tr>
ROW;
}

$post_content = <<<HTML
<div style="max-width:800px;margin:0 auto;font-family:inherit;">

  <!-- Meta bar -->
  <div style="display:flex;gap:12px;align-items:center;margin-bottom:28px;flex-wrap:wrap;">
    <span style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.78em;font-weight:700;padding:3px 12px;border-radius:20px;letter-spacing:.05em;text-transform:uppercase;">Security Report</span>
    <span style="color:#9ca3af;font-size:0.85em;">{$scan_date}</span>
    <span style="color:#9ca3af;font-size:0.85em;">·</span>
    <span style="color:#9ca3af;font-size:0.85em;">14 images · Grype vulnerability scanner</span>
  </div>

  <!-- Key stats callout -->
  <div style="background:linear-gradient(135deg,#3D405B 0%,#2a2d40 100%);border-radius:12px;padding:28px 32px;margin-bottom:36px;display:flex;gap:0;align-items:stretch;flex-wrap:wrap;">
    <div style="flex:1;min-width:100px;text-align:center;padding:8px 16px;">
      <div style="font-size:2.8em;font-weight:800;color:#81B29A;line-height:1;">{$pct_reduction}%</div>
      <div style="font-size:0.78em;color:#c9d1d9;margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Fewer CVEs</div>
    </div>
    <div style="width:1px;background:rgba(255,255,255,0.12);margin:8px 0;"></div>
    <div style="flex:1;min-width:100px;text-align:center;padding:8px 16px;">
      <div style="font-size:2.8em;font-weight:800;color:#F2CC8F;line-height:1;">{$dhi_total}</div>
      <div style="font-size:0.78em;color:#c9d1d9;margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">DHI CVEs</div>
    </div>
    <div style="width:1px;background:rgba(255,255,255,0.12);margin:8px 0;"></div>
    <div style="flex:1;min-width:100px;text-align:center;padding:8px 16px;">
      <div style="font-size:2.8em;font-weight:800;color:#E07A5F;line-height:1;">{$cg_total}</div>
      <div style="font-size:0.78em;color:#c9d1d9;margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Chainguard CVEs</div>
    </div>
    <div style="width:1px;background:rgba(255,255,255,0.12);margin:8px 0;"></div>
    <div style="flex:1;min-width:100px;text-align:center;padding:8px 16px;">
      <div style="font-size:2.8em;font-weight:800;color:#fff;line-height:1;">{$clean_count}</div>
      <div style="font-size:0.78em;color:#c9d1d9;margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Clean Images</div>
    </div>
  </div>

  <!-- Article body -->
  {$html_paras}

  <!-- Per-image breakdown table -->
  <div style="margin:36px 0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
    <div style="background:#3D405B;color:#fff;padding:12px 14px;">
      <span style="font-size:0.88em;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">Image-by-Image Breakdown</span>
    </div>
    <table style="width:100%;border-collapse:collapse;background:#fff;">
      <thead>
        <tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb;">
          <th style="padding:10px 14px;text-align:left;font-size:0.8em;color:#6b7280;font-weight:600;">Image</th>
          <th style="padding:10px 14px;text-align:center;font-size:0.8em;color:#E07A5F;font-weight:600;">DHI CVEs</th>
          <th style="padding:10px 14px;text-align:center;font-size:0.8em;color:#16a34a;font-weight:600;">Chainguard CVEs</th>
          <th style="padding:10px 14px;font-size:0.8em;color:#6b7280;font-weight:600;">Reduction</th>
        </tr>
      </thead>
      <tbody>
        {$table_rows}
      </tbody>
    </table>
  </div>

  <!-- Footer -->
  <div style="border-top:1px solid #e5e7eb;padding-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <span style="font-size:0.8em;color:#9ca3af;">Scanned {$scan_date} · Powered by Grype</span>
    <a href="/issue-tracker/" style="font-size:0.82em;font-weight:600;color:#81B29A;text-decoration:none;">View full Issue Tracker →</a>
  </div>

</div>
HTML;

// ── Create or update WordPress post ───────────────────────────────────────

echo "Publishing to WordPress...\n";

$existing = get_posts([
    'post_type'   => 'post',
    'post_status' => 'any',
    'meta_key'    => '_scan_story',
    'meta_value'  => '1',
    'numberposts' => 1,
]);

if ( $existing ) {
    $post_id = $existing[0]->ID;
    $wpdb->update( $wpdb->posts,
        [
            'post_title'        => $title,
            'post_content'      => $post_content,
            'post_status'       => 'publish',
            'post_modified'     => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
        ],
        ['ID' => $post_id], ['%s','%s','%s','%s','%s'], ['%d']
    );
    clean_post_cache( $post_id );
    echo "  Updated existing post ID: $post_id\n";
} else {
    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => '',
        'post_status'  => 'draft',
        'post_type'    => 'post',
        'post_name'    => 'chainguard-security-scan-story',
    ]);
    if ( is_wp_error( $post_id ) ) {
        echo "ERROR: " . $post_id->get_error_message() . "\n";
        exit(1);
    }
    $wpdb->update( $wpdb->posts,
        ['post_content' => $post_content, 'post_status' => 'publish'],
        ['ID' => $post_id], ['%s','%s'], ['%d']
    );
    clean_post_cache( $post_id );
    echo "  Created new post ID: $post_id\n";
}

update_post_meta( $post_id, '_scan_story', '1' );
update_post_meta( $post_id, '_scan_date',  $scan_date );

$post_url = get_permalink( $post_id );
echo "  Live URL: $post_url\n";

// ── Update Primary Menu: Blog top-level + scan story submenu ──────────────

echo "Updating navigation menu...\n";

$menu = wp_get_nav_menu_object( 'Primary Menu' );
if ( ! $menu ) {
    echo "  WARNING: Primary Menu not found — skipping nav update.\n";
    echo "\nDone.\n";
    exit(0);
}

$menu_items = wp_get_nav_menu_items( $menu->term_id );

// Find or create Blog page
$blog_page = get_page_by_path( 'blog', OBJECT, 'page' );
if ( ! $blog_page ) {
    $blog_page_id = wp_insert_post([
        'post_title'   => 'Blog',
        'post_name'    => 'blog',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ]);
    update_option( 'page_for_posts', $blog_page_id );
    $blog_page = get_post( $blog_page_id );
    echo "  Created Blog page ID: $blog_page_id\n";
} else {
    update_option( 'page_for_posts', $blog_page->ID );
}
update_option( 'show_on_front', 'page' );

// Find Blog and scan story nav items
$blog_item_id       = null;
$scan_story_item_id = null;
foreach ( $menu_items as $item ) {
    if ( (int) $item->object_id === (int) $blog_page->ID && $item->object === 'page' ) {
        $blog_item_id = $item->db_id;
    }
    if ( (int) $item->object_id === (int) $post_id && $item->object === 'post' ) {
        $scan_story_item_id = $item->db_id;
    }
}

// Add Blog to menu if missing
if ( ! $blog_item_id ) {
    $blog_item_id = wp_update_nav_menu_item( $menu->term_id, 0, [
        'menu-item-title'     => 'Blog',
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $blog_page->ID,
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
        'menu-item-position'  => 10,
    ]);
    echo "  Added Blog nav item: $blog_item_id\n";
}

// Add or update scan story submenu item
if ( $scan_story_item_id ) {
    wp_update_nav_menu_item( $menu->term_id, $scan_story_item_id, [
        'menu-item-title'     => $title,
        'menu-item-object'    => 'post',
        'menu-item-object-id' => $post_id,
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $blog_item_id,
        'menu-item-position'  => 1,
    ]);
    echo "  Updated scan story submenu item.\n";
} else {
    wp_update_nav_menu_item( $menu->term_id, 0, [
        'menu-item-title'     => $title,
        'menu-item-object'    => 'post',
        'menu-item-object-id' => $post_id,
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $blog_item_id,
        'menu-item-position'  => 1,
    ]);
    echo "  Added scan story as submenu under Blog.\n";
}

echo "\nDone. Scan story live at: $post_url\n";
