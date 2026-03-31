<?php
/**
 * bin/import-issues.php
 *
 * Syncs Grype scan results into the Software Issue Manager WordPress plugin.
 * Safe to run repeatedly — deduplicates by _grype_key, closes resolved CVEs.
 *
 * Expects scan files at:
 *   /tmp/scans/<image>/cg_latest.json
 *   /tmp/scans/<image>/dhi_latest.json
 *
 * Usage (inside the app container):
 *   php /tmp/import-issues.php
 */

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require('/var/www/html/wp-load.php');

$images = ['wordpress', 'nginx', 'mariadb', 'node', 'grype', 'prometheus', 'grafana'];

$priority_map = [
    'Critical' => 'critical',
    'High'     => 'major',
];

// ── Delete all existing issues ────────────────────────────────────────────────

echo "Deleting existing issues...\n";
$existing = get_posts([
    'post_type'      => 'emd_issue',
    'post_status'    => 'any',
    'numberposts'    => -1,
    'fields'         => 'ids',
]);
foreach ($existing as $id) {
    wp_delete_post($id, true); // true = bypass trash, permanent delete
}
echo "Deleted " . count($existing) . " issues.\n\n";

// ── Taxonomy helpers ──────────────────────────────────────────────────────────

function ensure_term($name, $taxonomy) {
    $term = get_term_by('name', $name, $taxonomy);
    if ($term) return $term->term_id;
    $r = wp_insert_term($name, $taxonomy);
    return is_wp_error($r) ? null : $r['term_id'];
}

$open_term    = get_term_by('slug', 'open', 'issue_status');
$open_term_id = $open_term ? $open_term->term_id : ensure_term('Open', 'issue_status');
$bug_term_id  = ensure_term('Bug', 'issue_cat');

// ── Project helper ────────────────────────────────────────────────────────────

function get_or_create_project($title, $slug) {
    $existing = get_page_by_path($slug, OBJECT, 'emd_project');
    if ($existing) return $existing->ID;
    $id = wp_insert_post([
        'post_title'  => $title,
        'post_name'   => $slug,
        'post_status' => 'publish',
        'post_type'   => 'emd_project',
    ]);
    echo "  Created project: $id ($title)\n";
    return $id;
}

// ── Issue HTML content ────────────────────────────────────────────────────────

function build_issue_content($vuln, $artifact, $image, $scan_type) {
    $cve_id      = esc_html($vuln['id']);
    $severity    = esc_html($vuln['severity']);
    $description = esc_html($vuln['description'] ?? 'No description available.');
    $namespace   = esc_html($vuln['namespace'] ?? '');
    $data_source = esc_url($vuln['dataSource'] ?? '');
    $pkg_name    = esc_html($artifact['name'] ?? '');
    $pkg_version = esc_html($artifact['version'] ?? '');
    $pkg_type    = esc_html($artifact['type'] ?? '');
    $fix_state   = esc_html($vuln['fix']['state'] ?? 'unknown');
    $fix_vers    = implode(', ', $vuln['fix']['versions'] ?? []);
    $fix_vers    = $fix_vers ? esc_html($fix_vers) : '—';
    $cvss_score  = '';
    foreach (($vuln['cvss'] ?? []) as $c) {
        if (!empty($c['metrics']['baseScore'])) {
            $cvss_score = $c['metrics']['baseScore'] . ' (' . esc_html($c['version'] ?? '') . ')';
            break;
        }
    }
    $epss_score = '';
    foreach (($vuln['epss'] ?? []) as $e) {
        if (isset($e['epss'])) {
            $pct        = round(($e['percentile'] ?? 0) * 100, 1);
            $epss_score = $e['epss'] . " ({$pct}th percentile)";
            break;
        }
    }
    $container_label = $scan_type === 'cg' ? 'Chainguard' : 'Docker Hardened Image (DHI)';
    $sev_color       = $severity === 'Critical' ? '#E07A5F' : '#F2CC8F';

    return <<<HTML
<div style="background:#f8fafc;border-left:4px solid {$sev_color};padding:16px 20px;border-radius:6px;margin-bottom:20px;">
  <strong style="font-size:1.1em;color:#3D405B;">{$cve_id}</strong>
  <span style="margin-left:12px;background:{$sev_color};color:#fff;padding:2px 10px;border-radius:12px;font-size:0.85em;font-weight:600;">{$severity}</span>
  <p style="margin:10px 0 0;color:#4a5568;line-height:1.6;">{$description}</p>
</div>

<table style="width:100%;border-collapse:collapse;font-size:0.9em;margin-bottom:20px;">
  <thead>
    <tr style="background:#3D405B;color:#fff;">
      <th style="padding:10px 14px;text-align:left;width:35%;">Field</th>
      <th style="padding:10px 14px;text-align:left;">Value</th>
    </tr>
  </thead>
  <tbody>
    <tr style="background:#fff;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">CVE ID</td>
      <td style="padding:9px 14px;"><a href="{$data_source}" target="_blank" style="color:#16a34a;">{$cve_id}</a></td>
    </tr>
    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Severity</td>
      <td style="padding:9px 14px;"><strong style="color:{$sev_color};">{$severity}</strong></td>
    </tr>
    <tr style="background:#fff;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Container Image</td>
      <td style="padding:9px 14px;"><strong>{$image}</strong> ({$container_label})</td>
    </tr>
    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Package</td>
      <td style="padding:9px 14px;">{$pkg_name}@{$pkg_version} ({$pkg_type})</td>
    </tr>
    <tr style="background:#fff;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Vendor / Namespace</td>
      <td style="padding:9px 14px;">{$namespace}</td>
    </tr>
    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">CVSS Score</td>
      <td style="padding:9px 14px;">{$cvss_score}</td>
    </tr>
    <tr style="background:#fff;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">EPSS Score</td>
      <td style="padding:9px 14px;">{$epss_score}</td>
    </tr>
    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Fix Available</td>
      <td style="padding:9px 14px;">{$fix_state} — {$fix_vers}</td>
    </tr>
    <tr style="background:#fff;border-bottom:1px solid #e2e8f0;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Data Source</td>
      <td style="padding:9px 14px;"><a href="{$data_source}" target="_blank" style="color:#16a34a;word-break:break-all;">{$data_source}</a></td>
    </tr>
    <tr style="background:#f8fafc;">
      <td style="padding:9px 14px;font-weight:600;color:#3D405B;">Status</td>
      <td style="padding:9px 14px;"><span style="background:#81B29A;color:#fff;padding:2px 10px;border-radius:12px;font-size:0.85em;font-weight:600;">Open</span></td>
    </tr>
  </tbody>
</table>
HTML;
}

// ── Sync issues for one project ───────────────────────────────────────────────

function sync_issues($project_id, $scan_type, $images, $open_term_id, $bug_term_id, $priority_map) {
    global $wpdb;

    $created = 0;

    foreach ($images as $image) {
        $scan_file = "/tmp/scans/{$image}/{$scan_type}_latest.json";
        if (!file_exists($scan_file)) {
            echo "  ⚠ no scan file for $image\n";
            continue;
        }
        $data    = json_decode(file_get_contents($scan_file), true);
        $matches = $data['matches'] ?? [];

        $image_count = 0;
        foreach ($matches as $match) {
            $vuln     = $match['vulnerability'];
            $artifact = $match['artifact'];
            if (!in_array($vuln['severity'], ['Critical', 'High'])) continue;

            $title   = "{$vuln['id']} — {$artifact['name']}@{$artifact['version']} ({$image})";
            $content = build_issue_content($vuln, $artifact, $image, $scan_type);

            $issue_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => '',
                'post_status'  => 'draft',
                'post_type'    => 'emd_issue',
            ]);
            if (is_wp_error($issue_id)) continue;

            $slug = sanitize_title( $vuln['id'] . '-' . $image . '-' . $issue_id );
            $wpdb->update($wpdb->posts,
                ['post_content' => $content, 'post_status' => 'publish', 'post_name' => $slug],
                ['ID' => $issue_id], ['%s', '%s', '%s'], ['%d']
            );
            clean_post_cache($issue_id);

            // Taxonomies
            if ($open_term_id) wp_set_post_terms($issue_id, [$open_term_id], 'issue_status');
            if ($bug_term_id)  wp_set_post_terms($issue_id, [$bug_term_id],  'issue_cat');
            wp_set_post_terms($issue_id, [$image], 'issue_tag');

            $priority_slug = $priority_map[$vuln['severity']] ?? 'normal';
            $pterm = get_term_by('slug', $priority_slug, 'issue_priority');
            if ($pterm) wp_set_post_terms($issue_id, [$pterm->term_id], 'issue_priority');

            // Meta
            update_post_meta($issue_id, '_cve_id',      $vuln['id']);
            update_post_meta($issue_id, '_severity',    $vuln['severity']);
            update_post_meta($issue_id, '_container',   $image);
            update_post_meta($issue_id, '_scan_type',   $scan_type);
            update_post_meta($issue_id, '_package',     "{$artifact['name']}@{$artifact['version']}");
            update_post_meta($issue_id, '_namespace',   $vuln['namespace'] ?? '');
            update_post_meta($issue_id, '_data_source', $vuln['dataSource'] ?? '');

            if (function_exists('emd_p2p_type')) {
                emd_p2p_type('project_issues')->connect($project_id, $issue_id);
            }

            $image_count++;
            $created++;
        }
        if ($image_count > 0) echo "  ✓ $image: $image_count issues\n";
    }

    echo "  Total: $created issues created\n";
}

// ── Main ──────────────────────────────────────────────────────────────────────

echo "\n=== Chainguard Software Issues ===\n";
$cg_project_id = get_or_create_project('Chainguard Software Issues', 'chainguard-software-issues');
sync_issues($cg_project_id, 'cg', $images, $open_term_id, $bug_term_id, $priority_map);

echo "\n=== DHI Software Issues ===\n";
$dhi_project_id = get_or_create_project('DHI Software Issues', 'dhi-software-issues');
sync_issues($dhi_project_id, 'dhi', $images, $open_term_id, $bug_term_id, $priority_map);

// Save import timestamp for dashboard display
update_option( 'grype_last_import', current_time( 'mysql' ) );

echo "\nDone.\n";
