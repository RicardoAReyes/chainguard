<?php
// Remove Software Issue Manager marketing/review notices from admin
add_action( 'admin_init', function () {
    remove_action( 'admin_notices', 'software_issue_manager_show_optin' );
} );

// ── Hamburger navigation ───────────────────────────────────────────────────
add_action( 'wp_footer', 'ttf_hamburger_nav' );

function ttf_hamburger_nav() {
    $menu_items = wp_get_nav_menu_items( 'Primary Menu' );
    if ( ! $menu_items ) return;

    // Build tree: top-level items with nested children
    $top_items = [];
    $children  = [];
    foreach ( $menu_items as $item ) {
        if ( $item->menu_item_parent == 0 ) {
            $top_items[ $item->db_id ] = [ 'item' => $item, 'children' => [] ];
        } else {
            $children[ $item->menu_item_parent ][] = $item;
        }
    }
    foreach ( $children as $parent_id => $kids ) {
        if ( isset( $top_items[ $parent_id ] ) ) {
            $top_items[ $parent_id ]['children'] = $kids;
        }
    }

    $current_path = rtrim( parse_url( home_url( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
    ?>
<button id="hbg-btn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="hbg-drawer">
  <span></span><span></span><span></span>
</button>
<div id="hbg-overlay" aria-hidden="true"></div>
<nav id="hbg-drawer" aria-label="Primary navigation" aria-hidden="true">
  <div id="hbg-drawer-brand">Chainguard Demo</div>
  <ul>
    <?php $i = 0; foreach ( $top_items as $node ) :
        $item        = $node['item'];
        $kids        = $node['children'];
        $item_path   = rtrim( parse_url( $item->url, PHP_URL_PATH ), '/' );
        $active      = $current_path === $item_path;
        $has_sub     = ! empty( $kids );
    ?>
    <li style="--i:<?php echo $i++; ?>"<?php if ( $has_sub ) echo ' class="has-submenu"'; ?>>
      <a href="<?php echo esc_url( $item->url ); ?>"
         <?php if ( $active ) echo 'aria-current="page"'; ?>>
        <?php echo esc_html( $item->title ); ?>
      </a>
      <?php if ( $has_sub ) : ?>
      <ul>
        <?php foreach ( $kids as $child ) :
            $child_path = rtrim( parse_url( $child->url, PHP_URL_PATH ), '/' );
            $child_active = $current_path === $child_path;
        ?>
        <li>
          <a href="<?php echo esc_url( $child->url ); ?>"
             <?php if ( $child_active ) echo 'aria-current="page"'; ?>>
            <?php echo esc_html( $child->title ); ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>
<script>
(function(){
  var btn     = document.getElementById('hbg-btn');
  var overlay = document.getElementById('hbg-overlay');
  var drawer  = document.getElementById('hbg-drawer');
  function openNav() {
    btn.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeNav() {
    btn.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
  btn.addEventListener('click', function() {
    btn.classList.contains('open') ? closeNav() : openNav();
  });
  overlay.addEventListener('click', closeNav);
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeNav();
  });
  // Submenu toggle on parent click
  drawer.querySelectorAll('li.has-submenu > a').forEach(function(link) {
    link.addEventListener('click', function(e) {
      var li = this.parentElement;
      if ( li.querySelector('ul') ) {
        e.preventDefault();
        li.classList.toggle('open');
      }
    });
  });
})();
</script>
    <?php
}

// ── Chainguard Unchained RSS feed shortcode ────────────────────────────────
add_shortcode( 'chainguard_unchained', 'ttf_chainguard_unchained' );

function ttf_chainguard_unchained() {
    include_once ABSPATH . WPINC . '/feed.php';

    $feed = fetch_feed( 'https://www.chainguard.dev/unchained/rss.xml' );
    if ( is_wp_error( $feed ) ) {
        return '<p style="color:#6b7280;">Unable to load Chainguard Unchained feed at this time.</p>';
    }

    $items    = $feed->get_items( 0, 7 );
    $featured = array_shift( $items ); // first item = featured
    // remaining 6 = latest updates

    ob_start(); ?>

<div style="font-family:inherit;">

  <!-- ── Featured post ─────────────────────────────────────────────────── -->
  <?php if ( $featured ) :
      $date = $featured->get_date( 'M j, Y' );
  ?>
  <div style="background:linear-gradient(135deg,#3D405B 0%,#2a2d40 100%);border-radius:12px;padding:36px 40px;margin-bottom:32px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:0;right:0;width:300px;height:100%;background:rgba(129,178,154,0.08);clip-path:polygon(30% 0,100% 0,100% 100%,0 100%);"></div>
    <div style="position:relative;">
      <span style="display:inline-block;background:#81B29A;color:#fff;font-size:0.72em;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 12px;border-radius:20px;margin-bottom:14px;">Featured</span>
      <h2 style="margin:0 0 12px;font-size:1.45em;font-weight:700;line-height:1.3;">
        <a href="<?php echo esc_url( $featured->get_permalink() ); ?>" target="_blank" rel="noopener"
           style="color:#fff;text-decoration:none;"
           onmouseover="this.style.color='#F2CC8F'" onmouseout="this.style.color='#fff'">
          <?php echo esc_html( $featured->get_title() ); ?>
        </a>
      </h2>
      <p style="margin:0 0 18px;color:#c9d1d9;line-height:1.65;max-width:680px;font-size:0.95em;">
        <?php echo esc_html( wp_trim_words( $featured->get_description(), 28, '…' ) ); ?>
      </p>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span style="color:#8b9ab0;font-size:0.8em;"><?php echo esc_html( $date ); ?></span>
        <a href="<?php echo esc_url( $featured->get_permalink() ); ?>" target="_blank" rel="noopener"
           style="display:inline-block;background:#E07A5F;color:#fff;font-size:0.85em;font-weight:600;padding:7px 20px;border-radius:6px;text-decoration:none;"
           onmouseover="this.style.background='#c95f44'" onmouseout="this.style.background='#E07A5F'">
          Read article →
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Latest updates ────────────────────────────────────────────────── -->
  <h3 style="font-size:1em;font-weight:700;color:#3D405B;margin:0 0 16px;letter-spacing:.04em;text-transform:uppercase;border-left:4px solid #E07A5F;padding-left:10px;">Latest Updates</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:12px;">
    <?php foreach ( $items as $item ) :
        $date = $item->get_date( 'M j, Y' );
    ?>
    <a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank" rel="noopener"
       style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(61,64,91,.1)';this.style.borderColor='#81B29A'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='#e2e8f0'">
      <p style="margin:0 0 8px;font-weight:600;color:#3D405B;font-size:0.92em;line-height:1.4;">
        <?php echo esc_html( $item->get_title() ); ?>
      </p>
      <p style="margin:0 0 12px;color:#6b7280;font-size:0.82em;line-height:1.5;">
        <?php echo esc_html( wp_trim_words( $item->get_description(), 18, '…' ) ); ?>
      </p>
      <span style="color:#81B29A;font-size:0.78em;font-weight:600;"><?php echo esc_html( $date ); ?> →</span>
    </a>
    <?php endforeach; ?>
  </div>

  <p style="text-align:right;margin:4px 0 0;">
    <a href="https://www.chainguard.dev/unchained" target="_blank" rel="noopener"
       style="font-size:0.82em;color:#81B29A;text-decoration:none;font-weight:600;">
      View all posts on Chainguard Unchained →
    </a>
  </p>

</div>
<?php
    return ob_get_clean();
}

function ttf_child_enqueue_scripts() {
    // Child theme stylesheet (style.css)
    wp_enqueue_style(
        'ttf-child-style',
        get_stylesheet_uri(),
        [ 'wp-block-library' ],
        wp_get_theme()->get( 'Version' )
    );

    $asset_file = get_stylesheet_directory() . '/build/index.asset.php';
    $asset = file_exists( $asset_file )
        ? require( $asset_file )
        : [ 'dependencies' => [], 'version' => '1.0.0' ];

    wp_enqueue_script(
        'ttf-child-scripts',
        get_stylesheet_directory_uri() . '/build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );

    // Motion — scroll-entrance animations on all content pages except the scan report and issue tracker
    if ( ! is_page( 'security-scan-report' ) && ! is_page( 'issue-tracker' ) ) {
        wp_add_inline_script( 'ttf-child-scripts', ttf_motion_scroll_js(), 'after' );
    }

    // Supply chain attacks page — load Chart.js then initialize charts
    if ( is_page( 'supply-chain-attacks' ) ) {
        wp_enqueue_script(
            'chartjs',
            get_stylesheet_directory_uri() . '/vendor/chart.umd.min.js', // sourced from Chainguard registry: https://libraries.cgr.dev/javascript
            [],
            '4.4.3',
            true
        );
        wp_add_inline_script( 'chartjs', ttf_supply_chain_charts_js(), 'after' );
    }

    // Issue tracker dashboard — load Chart.js
    if ( is_page( 'issue-tracker' ) ) {
        wp_enqueue_script(
            'chartjs',
            get_stylesheet_directory_uri() . '/vendor/chart.umd.min.js',
            [],
            '4.4.3',
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'ttf_child_enqueue_scripts' );

function ttf_motion_scroll_js() {
    return <<<'JS'
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.motionAnimate !== 'function') return;

    var selectors = [
      '.entry-content h1', '.entry-content h2', '.entry-content h3',
      '.entry-content p', '.entry-content ul', '.entry-content ol',
      '.entry-content figure', '.entry-content .wp-block-group',
      '.entry-content .wp-block-columns', '.entry-content table',
      '.entry-content blockquote'
    ].join(', ');

    var els = Array.from(document.querySelectorAll(selectors));
    if (!els.length) return;

    // Set initial hidden state
    els.forEach(function(el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(24px)';
    });

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        window.motionAnimate(
          entry.target,
          { opacity: [0, 1], transform: ['translateY(24px)', 'translateY(0px)'] },
          { duration: 0.5, easing: [0.25, 0.1, 0.25, 1] }
        );
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function(el) { observer.observe(el); });
  });
})();
JS;
}

function ttf_supply_chain_charts_js() {
    return <<<'JS'
(function() {
  var attacks = [
    { name: "Heartbleed (OpenSSL)",        year: 2014, impact: 10, stage: "source",       severity: "Critical" },
    { name: "npm / PyPI Typosquatting",    year: 2017, impact: 6,  stage: "source",       severity: "Medium"   },
    { name: "Event-Stream (npm)",          year: 2018, impact: 7,  stage: "source",       severity: "High"     },
    { name: "SolarWinds Orion",            year: 2020, impact: 10, stage: "build",        severity: "Critical" },
    { name: "Dependency Confusion",        year: 2021, impact: 8,  stage: "source",       severity: "High"     },
    { name: "Log4Shell (Log4j)",           year: 2021, impact: 10, stage: "source",       severity: "Critical" },
    { name: "Codecov",                     year: 2021, impact: 8,  stage: "build",        severity: "High"     },
    { name: "Kaseya VSA",                  year: 2021, impact: 9,  stage: "distribution", severity: "Critical" },
    { name: "CircleCI Breach",             year: 2023, impact: 8,  stage: "build",        severity: "High"     },
    { name: "3CX Desktop App",             year: 2023, impact: 9,  stage: "distribution", severity: "Critical" },
    { name: "MOVEit Transfer",             year: 2023, impact: 9,  stage: "deployment",   severity: "Critical" },
    { name: "Microsoft Signing Key Abuse", year: 2023, impact: 9,  stage: "distribution", severity: "Critical" },
    { name: "XZ Utils Backdoor",           year: 2024, impact: 10, stage: "source",       severity: "Critical" },
    { name: "JetBrains TeamCity",          year: 2024, impact: 8,  stage: "build",        severity: "High"     },
    { name: "Trivy GitHub Action",         year: 2026, impact: 8,  stage: "build",        severity: "High"     },
    { name: "LiteLLM Exposure",            year: 2026, impact: 7,  stage: "deployment",   severity: "High"     }
  ];

  var stageColors = { source: "#E07A5F", build: "#81B29A", distribution: "#F2CC8F", deployment: "#9b8ec4" };
  var severityColors = { Critical: "#E07A5F", High: "#81B29A", Medium: "#F2CC8F", Low: "#9b8ec4" };

  function initCharts() {
    // Bubble chart
    var bubbleEl = document.getElementById("bubbleChart");
    if (bubbleEl) {
      var stages = ["source", "build", "distribution", "deployment"];
      var datasets = stages.map(function(stage) {
        return {
          label: stage.charAt(0).toUpperCase() + stage.slice(1),
          data: attacks.filter(function(a) { return a.stage === stage; }).map(function(a) {
            return { x: a.year, y: a.impact, r: a.impact + 2, name: a.name };
          }),
          backgroundColor: stageColors[stage] + "cc",
          borderColor: stageColors[stage]
        };
      });
      new window.Chart(bubbleEl.getContext("2d"), {
        type: "bubble",
        data: { datasets: datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top" },
            tooltip: { callbacks: { label: function(ctx) { return ctx.raw.name + " (impact: " + ctx.raw.y + ")"; } } }
          },
          scales: {
            x: { title: { display: true, text: "Year", color: "#3D405B", font: { weight: "600" } }, ticks: { color: "#3D405B", stepSize: 1, precision: 0 }, grid: { color: "rgba(61,64,91,0.08)" } },
            y: { title: { display: true, text: "Impact Score", color: "#3D405B", font: { weight: "600" } }, min: 0, max: 11, ticks: { color: "#3D405B" }, grid: { color: "rgba(61,64,91,0.08)" } }
          }
        }
      });
    }

    // Impact bar chart (sorted)
    var stageEl = document.getElementById("stageChart");
    if (stageEl) {
      var sorted = attacks.slice().sort(function(a, b) { return b.impact - a.impact; });
      new window.Chart(stageEl.getContext("2d"), {
        type: "bar",
        data: {
          labels: sorted.map(function(a) { return a.name; }),
          datasets: [{ label: "Impact Score", data: sorted.map(function(a) { return a.impact; }), backgroundColor: sorted.map(function(a) { return severityColors[a.severity]; }), borderRadius: 4 }]
        },
        options: {
          indexAxis: "y",
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            x: { title: { display: true, text: "Impact Score", color: "#3D405B", font: { weight: "600" } }, min: 0, max: 10, ticks: { color: "#3D405B" }, grid: { color: "rgba(61,64,91,0.08)" } },
            y: { ticks: { color: "#3D405B", font: { size: 11 } }, grid: { display: false } }
          }
        }
      });
    }

    // Stage distribution bar chart
    var stageBarEl = document.getElementById("stageBarChart");
    if (stageBarEl) {
      var stageCounts = { Source: 0, Build: 0, Distribution: 0, Deployment: 0 };
      attacks.forEach(function(a) { stageCounts[a.stage.charAt(0).toUpperCase() + a.stage.slice(1)]++; });
      new window.Chart(stageBarEl.getContext("2d"), {
        type: "bar",
        data: {
          labels: Object.keys(stageCounts),
          datasets: [{
            label: "Number of Incidents",
            data: Object.values(stageCounts),
            backgroundColor: ["#E07A5F", "#81B29A", "#F2CC8F", "#9b8ec4"],
            borderColor:     ["#c95f44", "#62967a", "#d4aa6a", "#7c6fa8"],
            borderWidth: 1.5,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return " " + ctx.parsed.y + " incident" + (ctx.parsed.y !== 1 ? "s" : ""); } } } },
          scales: {
            x: { title: { display: true, text: "Supply Chain Stage", color: "#3D405B", font: { size: 13, weight: "600" } }, grid: { display: false }, ticks: { color: "#3D405B", font: { size: 13 } } },
            y: { title: { display: true, text: "Number of Incidents", color: "#3D405B", font: { size: 13, weight: "600" } }, beginAtZero: true, ticks: { color: "#3D405B", stepSize: 1, precision: 0 }, grid: { color: "rgba(61,64,91,0.08)" } }
          }
        }
      });
    }

    // Stage x Severity grouped bar chart
    var stageSevEl = document.getElementById("stageSeverityChart");
    if (stageSevEl) {
      new window.Chart(stageSevEl.getContext("2d"), {
        type: "bar",
        data: {
          labels: ["Source", "Build", "Distribution", "Deployment"],
          datasets: [
            { label: "Critical", data: [3, 1, 3, 1], backgroundColor: "#E07A5F", borderColor: "#c95f44", borderWidth: 1.5, borderRadius: 4 },
            { label: "High",     data: [2, 4, 0, 1], backgroundColor: "#81B29A", borderColor: "#62967a", borderWidth: 1.5, borderRadius: 4 },
            { label: "Medium",   data: [1, 0, 0, 0], backgroundColor: "#F2CC8F", borderColor: "#d4aa6a", borderWidth: 1.5, borderRadius: 4 }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top", labels: { color: "#3D405B", font: { size: 13 } } },
            tooltip: { callbacks: { label: function(ctx) { return " " + ctx.dataset.label + ": " + ctx.parsed.y + " incident" + (ctx.parsed.y !== 1 ? "s" : ""); } } }
          },
          scales: {
            x: { title: { display: true, text: "Supply Chain Stage", color: "#3D405B", font: { size: 13, weight: "600" } }, ticks: { color: "#3D405B", font: { size: 13 } }, grid: { display: false } },
            y: { title: { display: true, text: "Number of Incidents", color: "#3D405B", font: { size: 13, weight: "600" } }, beginAtZero: true, ticks: { color: "#3D405B", stepSize: 1, precision: 0 }, grid: { color: "rgba(61,64,91,0.08)" } }
          }
        }
      });
    }

    // Scatter chart — X: year, Y: supply chain stage
    var scatterEl = document.getElementById("scatterChart");
    if (scatterEl) {
      var scatterStageMap = { source: 1, build: 2, distribution: 3, deployment: 4 };
      var scatterSeverities = ["Critical", "High", "Medium"];
      var scatterDatasets = scatterSeverities.map(function(sev, si) {
        var jitter = 0;
        return {
          label: sev,
          data: attacks
            .filter(function(a) { return a.severity === sev; })
            .map(function(a) {
              var offset = (jitter++ % 3 - 1) * 0.13;
              return { x: a.year, y: scatterStageMap[a.stage] + offset, name: a.name };
            }),
          backgroundColor: severityColors[sev] + "dd",
          borderColor: severityColors[sev],
          borderWidth: 1.5,
          pointRadius: 8,
          pointHoverRadius: 11
        };
      });
      new window.Chart(scatterEl.getContext("2d"), {
        type: "scatter",
        data: { datasets: scatterDatasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top", labels: { color: "#3D405B", font: { size: 13 } } },
            tooltip: { callbacks: { label: function(ctx) { return " " + ctx.raw.name + " (" + ctx.raw.x + ")"; } } }
          },
          scales: {
            x: {
              title: { display: true, text: "Year of Incident", color: "#3D405B", font: { size: 13, weight: "600" } },
              min: 2013, max: 2027,
              ticks: { color: "#3D405B", stepSize: 1, precision: 0, callback: function(v) { return String(v); } },
              grid: { color: "rgba(61,64,91,0.08)" }
            },
            y: {
              title: { display: true, text: "Supply Chain Stage", color: "#3D405B", font: { size: 13, weight: "600" } },
              min: 0.5, max: 4.5,
              afterBuildTicks: function(axis) { axis.ticks = [{value:1},{value:2},{value:3},{value:4}]; },
              ticks: { color: "#3D405B", font: { size: 13 }, callback: function(v) { return {1:"Source",2:"Build",3:"Distribution",4:"Deployment"}[v]||""; } },
              grid: { color: "rgba(61,64,91,0.08)" }
            }
          }
        }
      });
    }

  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCharts);
  } else {
    initCharts();
  }
})();
JS;
}

// ── Issue Tracker Dashboard shortcode ─────────────────────────────────────────
add_shortcode( 'issue_dashboard', 'ttf_issue_dashboard' );

function ttf_issue_dashboard() {
    // ── Aggregate issue data from post meta ──────────────────────────────────
    $issues = get_posts( [
        'post_type'   => 'emd_issue',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
    ] );

    $images   = [ 'grafana', 'grype', 'mariadb', 'nginx', 'node', 'prometheus', 'wordpress' ];
    $cg_data  = array_fill_keys( $images, [ 'Critical' => 0, 'High' => 0 ] );
    $dhi_data = array_fill_keys( $images, [ 'Critical' => 0, 'High' => 0 ] );

    foreach ( $issues as $id ) {
        $scan_type = get_post_meta( $id, '_scan_type', true );
        $container = get_post_meta( $id, '_container', true );
        $severity  = get_post_meta( $id, '_severity',  true );
        if ( ! $scan_type || ! $container || ! $severity ) continue;
        if ( $scan_type === 'cg' && isset( $cg_data[ $container ] ) ) {
            $cg_data[ $container ][ $severity ] = ( $cg_data[ $container ][ $severity ] ?? 0 ) + 1;
        } elseif ( $scan_type === 'dhi' && isset( $dhi_data[ $container ] ) ) {
            $dhi_data[ $container ][ $severity ] = ( $dhi_data[ $container ][ $severity ] ?? 0 ) + 1;
        }
    }

    // Totals
    $cg_critical  = array_sum( array_column( $cg_data,  'Critical' ) );
    $cg_high      = array_sum( array_column( $cg_data,  'High' ) );
    $dhi_critical = array_sum( array_column( $dhi_data, 'Critical' ) );
    $dhi_high     = array_sum( array_column( $dhi_data, 'High' ) );
    $cg_total     = $cg_critical  + $cg_high;
    $dhi_total    = $dhi_critical + $dhi_high;
    $pct_fewer    = $dhi_total > 0 ? round( ( 1 - $cg_total / $dhi_total ) * 100 ) : 0;

    // JSON for JS
    $js_images      = wp_json_encode( $images );
    $js_cg_crit     = wp_json_encode( array_values( array_map( fn($d) => $d['Critical'], $cg_data ) ) );
    $js_cg_high     = wp_json_encode( array_values( array_map( fn($d) => $d['High'],     $cg_data ) ) );
    $js_dhi_crit    = wp_json_encode( array_values( array_map( fn($d) => $d['Critical'], $dhi_data ) ) );
    $js_dhi_high    = wp_json_encode( array_values( array_map( fn($d) => $d['High'],     $dhi_data ) ) );

    // Full severity totals for donut charts (from scan_summary, all severities)
    $ss             = get_option( 'grype_scan_summary', [] );
    $sev_keys       = [ 'Critical', 'High', 'Medium', 'Low', 'Unknown' ];
    $cg_sev_donut   = array_fill_keys( $sev_keys, 0 );
    $dhi_sev_donut  = array_fill_keys( $sev_keys, 0 );
    foreach ( $images as $img ) {
        foreach ( $sev_keys as $s ) {
            $cg_sev_donut[$s]  += $ss['cg'][$img][$s]  ?? 0;
            $dhi_sev_donut[$s] += $ss['dhi'][$img][$s] ?? 0;
        }
    }
    $js_cg_donut  = wp_json_encode( array_values( $cg_sev_donut ) );
    $js_dhi_donut = wp_json_encode( array_values( $dhi_sev_donut ) );
    $cg_project_url  = esc_url( get_permalink( get_page_by_path( 'chainguard-software-issues', OBJECT, 'emd_project' ) ) );
    $dhi_project_url = esc_url( get_permalink( get_page_by_path( 'dhi-software-issues',        OBJECT, 'emd_project' ) ) );
    $last_import_raw = get_option( 'grype_last_import', '' );
    $last_import     = $last_import_raw
        ? esc_html( date( 'M j, Y · g:i a', strtotime( $last_import_raw ) ) )
        : 'Not yet recorded';

    ob_start(); ?>

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<div style="background:linear-gradient(135deg,#3D405B 0%,#2a2d40 100%);color:#fff;padding:40px;border-radius:12px;margin-bottom:32px;text-align:center;">
  <div style="font-size:44px;margin-bottom:10px;">🛡️</div>
  <h1 style="margin:0 0 8px;font-size:2em;font-weight:700;">Issue Tracker Dashboard</h1>
  <p style="margin:0;color:#c9d1d9;max-width:560px;margin:0 auto;">Critical &amp; High severity CVEs detected by Grype across Chainguard and Docker Hardened Images.</p>
  <p style="margin:12px 0 0;font-size:0.8em;color:#8b9ab0;">🕐 Last scanned: <?php echo $last_import; ?></p>
</div>

<!-- ── Stat cards ─────────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:32px;">
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:22px;text-align:center;">
    <div style="font-size:2.2em;font-weight:800;color:#16a34a;"><?php echo $cg_total; ?></div>
    <div style="font-size:0.82em;color:#4a5568;margin-top:4px;font-weight:600;">Chainguard Issues</div>
    <div style="font-size:0.75em;color:#6b7280;margin-top:2px;"><?php echo $cg_critical; ?> Critical · <?php echo $cg_high; ?> High</div>
  </div>
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:22px;text-align:center;">
    <div style="font-size:2.2em;font-weight:800;color:#dc2626;"><?php echo $dhi_total; ?></div>
    <div style="font-size:0.82em;color:#4a5568;margin-top:4px;font-weight:600;">DHI Issues</div>
    <div style="font-size:0.75em;color:#6b7280;margin-top:2px;"><?php echo $dhi_critical; ?> Critical · <?php echo $dhi_high; ?> High</div>
  </div>
  <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:22px;text-align:center;">
    <div style="font-size:2.2em;font-weight:800;color:#2563eb;"><?php echo $pct_fewer; ?>%</div>
    <div style="font-size:0.82em;color:#4a5568;margin-top:4px;font-weight:600;">Fewer CVEs</div>
    <div style="font-size:0.75em;color:#6b7280;margin-top:2px;">Chainguard vs DHI</div>
  </div>
  <div style="background:#fefce8;border:1px solid #fef08a;border-radius:10px;padding:22px;text-align:center;">
    <div style="font-size:2.2em;font-weight:800;color:#ca8a04;"><?php echo $cg_critical; ?></div>
    <div style="font-size:0.82em;color:#4a5568;margin-top:4px;font-weight:600;">CG Critical</div>
    <div style="font-size:0.75em;color:#6b7280;margin-top:2px;">vs <?php echo $dhi_critical; ?> in DHI</div>
  </div>
  <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:22px;text-align:center;">
    <div style="font-size:2.2em;font-weight:800;color:#9333ea;"><?php echo count( $images ) * 2; ?></div>
    <div style="font-size:0.82em;color:#4a5568;margin-top:4px;font-weight:600;">Images Scanned</div>
    <div style="font-size:0.75em;color:#6b7280;margin-top:2px;"><?php echo count( $images ); ?> CG &amp; <?php echo count( $images ); ?> DHI</div>
  </div>
</div>

<!-- ── Grouped bar chart: CG vs DHI by container ─────────────────────────── -->
<div class="alignfull" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;margin-bottom:24px;">
  <h2 style="font-size:1.1em;font-weight:700;color:#3D405B;margin:0 0 20px;">Issues by Container — Chainguard vs DHI</h2>
  <canvas id="iddContainerChart" height="90"></canvas>
</div>

<!-- ── Severity doughnuts ─────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;">
    <h2 style="font-size:1.05em;font-weight:700;color:#3D405B;margin:0 0 16px;">🟢 Chainguard — Severity Split</h2>
    <canvas id="iddCgDonut" height="160"></canvas>
  </div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;">
    <h2 style="font-size:1.05em;font-weight:700;color:#3D405B;margin:0 0 16px;">🔴 DHI — Severity Split</h2>
    <canvas id="iddDhiDonut" height="160"></canvas>
  </div>
</div>

<!-- ── Full scan summary table (all severities) ───────────────────────────── -->
<?php
$scan_summary = get_option( 'grype_scan_summary', [] );
$severities   = [ 'Critical', 'High', 'Medium', 'Low', 'Unknown' ];
$sev_colors   = [
    'Critical' => '#E07A5F',
    'High'     => '#d97706',
    'Medium'   => '#6b7280',
    'Low'      => '#9ca3af',
    'Unknown'  => '#d1d5db',
];
if ( ! empty( $scan_summary ) ) : ?>
<div class="alignfull" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;margin-bottom:24px;overflow-x:auto;">
  <h2 style="font-size:1.1em;font-weight:700;color:#3D405B;margin:0 0 16px;">Full Scan Summary — All Severities</h2>
  <table style="width:100%;border-collapse:collapse;font-size:0.84em;">
    <thead>
      <tr style="background:#3D405B;color:#fff;">
        <th style="padding:10px 14px;text-align:left;" rowspan="2">Image</th>
        <th style="padding:6px 14px;text-align:center;border-bottom:1px solid #555;letter-spacing:.05em;" colspan="6">🟢 Chainguard</th>
        <th style="padding:6px 14px;text-align:center;border-bottom:1px solid #555;letter-spacing:.05em;" colspan="6">🔴 DHI</th>
        <th style="padding:6px 14px;text-align:center;" rowspan="2">Reduction</th>
      </tr>
      <tr style="background:#2a2d40;color:#c9d1d9;font-size:0.9em;">
        <?php foreach ( $severities as $s ) : ?>
        <th style="padding:6px 10px;text-align:center;"><?php echo $s; ?></th>
        <?php endforeach; ?>
        <th style="padding:6px 10px;text-align:center;font-weight:700;color:#fff;">CG Total</th>
        <?php foreach ( $severities as $s ) : ?>
        <th style="padding:6px 10px;text-align:center;background:#4a2020;"><?php echo $s; ?></th>
        <?php endforeach; ?>
        <th style="padding:6px 10px;text-align:center;font-weight:700;color:#fff;background:#4a2020;">DHI Total</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $row_i = 0;
      foreach ( $images as $img ) :
          $cg  = $scan_summary['cg'][$img]  ?? array_fill_keys( array_merge( $severities, ['Total'] ), 0 );
          $dhi = $scan_summary['dhi'][$img] ?? array_fill_keys( array_merge( $severities, ['Total'] ), 0 );
          $row_bg = $row_i++ % 2 === 0 ? '#fff' : '#f8fafc';
      ?>
      <tr style="background:<?php echo $row_bg; ?>;border-bottom:1px solid #e2e8f0;">
        <td style="padding:8px 14px;font-weight:600;color:#3D405B;"><?php echo esc_html( $img ); ?></td>
        <?php foreach ( $severities as $s ) :
            $val = $cg[$s] ?? 0;
            $color = $val > 0 ? $sev_colors[$s] : '#9ca3af';
        ?>
        <td style="padding:8px 10px;text-align:center;color:<?php echo $color; ?>;font-weight:<?php echo $val > 0 ? '700' : '400'; ?>;"><?php echo $val; ?></td>
        <?php endforeach; ?>
        <td style="padding:8px 10px;text-align:center;font-weight:700;color:#3D405B;"><?php echo $cg['Total'] ?? 0; ?></td>
        <?php foreach ( $severities as $s ) :
            $val = $dhi[$s] ?? 0;
            $color = $val > 0 ? $sev_colors[$s] : '#9ca3af';
        ?>
        <td style="padding:8px 10px;text-align:center;color:<?php echo $color; ?>;font-weight:<?php echo $val > 0 ? '700' : '400'; ?>;background:#fff5f5;"><?php echo $val; ?></td>
        <?php endforeach; ?>
        <td style="padding:8px 10px;text-align:center;font-weight:700;color:#3D405B;background:#fff5f5;"><?php echo $dhi['Total'] ?? 0; ?></td>
        <?php
        $ct = $cg['Total'] ?? 0;
        $dt = $dhi['Total'] ?? 0;
        $red = $dt > 0 ? round( (1 - $ct / $dt) * 100 ) : 0;
        $red_color = $red >= 80 ? '#16a34a' : ( $red >= 50 ? '#65a30d' : ( $red >= 0 ? '#ca8a04' : '#dc2626' ) );
        ?>
        <td style="padding:8px 10px;text-align:center;font-weight:700;color:<?php echo $red_color; ?>;"><?php echo $red; ?>%</td>
      </tr>
      <?php endforeach; ?>
      <?php
      // Totals row
      $cg_sev_totals  = array_fill_keys( $severities, 0 );
      $dhi_sev_totals = array_fill_keys( $severities, 0 );
      $cg_grand = 0; $dhi_grand = 0;
      foreach ( $images as $img ) {
          foreach ( $severities as $s ) {
              $cg_sev_totals[$s]  += $scan_summary['cg'][$img][$s]  ?? 0;
              $dhi_sev_totals[$s] += $scan_summary['dhi'][$img][$s] ?? 0;
          }
          $cg_grand  += $scan_summary['cg'][$img]['Total']  ?? 0;
          $dhi_grand += $scan_summary['dhi'][$img]['Total'] ?? 0;
      }
      ?>
      <tr style="background:#f1f5f9;border-top:2px solid #3D405B;font-weight:700;">
        <td style="padding:9px 14px;color:#3D405B;">TOTAL</td>
        <?php foreach ( $severities as $s ) : ?>
        <td style="padding:9px 10px;text-align:center;color:<?php echo $sev_colors[$s]; ?>;"><?php echo $cg_sev_totals[$s]; ?></td>
        <?php endforeach; ?>
        <td style="padding:9px 10px;text-align:center;color:#3D405B;"><?php echo $cg_grand; ?></td>
        <?php foreach ( $severities as $s ) : ?>
        <td style="padding:9px 10px;text-align:center;color:<?php echo $sev_colors[$s]; ?>;background:#fde8e8;"><?php echo $dhi_sev_totals[$s]; ?></td>
        <?php endforeach; ?>
        <td style="padding:9px 10px;text-align:center;color:#3D405B;background:#fde8e8;"><?php echo $dhi_grand; ?></td>
        <?php
        $grand_red = $dhi_grand > 0 ? round( (1 - $cg_grand / $dhi_grand) * 100 ) : 0;
        $grand_red_color = $grand_red >= 80 ? '#16a34a' : ( $grand_red >= 50 ? '#65a30d' : '#ca8a04' );
        ?>
        <td style="padding:9px 10px;text-align:center;font-weight:700;color:<?php echo $grand_red_color; ?>;"><?php echo $grand_red; ?>%</td>
      </tr>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ── Quick links ────────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:8px;">
  <a href="<?php echo $cg_project_url; ?>" style="display:block;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px 24px;text-decoration:none;">
    <div style="font-weight:700;color:#16a34a;font-size:1.05em;">🟢 Chainguard Issues →</div>
    <div style="font-size:0.83em;color:#4a5568;margin-top:4px;"><?php echo $cg_total; ?> open issues across <?php echo count($images); ?> images</div>
  </a>
  <a href="<?php echo $dhi_project_url; ?>" style="display:block;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:20px 24px;text-decoration:none;">
    <div style="font-weight:700;color:#dc2626;font-size:1.05em;">🔴 DHI Issues →</div>
    <div style="font-size:0.83em;color:#4a5568;margin-top:4px;"><?php echo $dhi_total; ?> open issues across <?php echo count($images); ?> images</div>
  </a>
</div>

<!-- ── Issue list ─────────────────────────────────────────────────────────── -->
<?php
$all_issues_query = new WP_Query( [
    'post_type'      => 'emd_issue',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'meta_value',
    'meta_key'       => '_severity',
    'order'          => 'ASC',
    'no_found_rows'  => true,
] );
$all_issues_posts = $all_issues_query->posts;
// Sort: Critical first, then High
usort( $all_issues_posts, function( $a, $b ) {
    $order = [ 'Critical' => 0, 'High' => 1 ];
    $sa = $order[ get_post_meta( $a->ID, '_severity', true ) ] ?? 2;
    $sb = $order[ get_post_meta( $b->ID, '_severity', true ) ] ?? 2;
    if ( $sa !== $sb ) return $sa - $sb;
    return strcmp(
        get_post_meta( $a->ID, '_container', true ),
        get_post_meta( $b->ID, '_container', true )
    );
} );
?>
<div class="alignfull" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;margin-bottom:24px;overflow-x:auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <h2 style="font-size:1.1em;font-weight:700;color:#3D405B;margin:0;">All Issues <span id="ail-count" style="font-size:0.75em;font-weight:400;color:#6b7280;">(<?php echo count( $all_issues_posts ); ?> total)</span></h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <select id="ail-f-scan" style="font-size:0.82em;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;color:#3D405B;">
        <option value="">All scan types</option>
        <option value="cg">Chainguard</option>
        <option value="dhi">DHI</option>
      </select>
      <select id="ail-f-sev" style="font-size:0.82em;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;color:#3D405B;">
        <option value="">All severities</option>
        <option value="Critical">Critical</option>
        <option value="High">High</option>
      </select>
      <select id="ail-f-img" style="font-size:0.82em;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;color:#3D405B;">
        <option value="">All containers</option>
        <?php foreach ( $images as $img ) : ?>
        <option value="<?php echo esc_attr( $img ); ?>"><?php echo esc_html( $img ); ?></option>
        <?php endforeach; ?>
      </select>
      <input id="ail-f-cve" type="text" placeholder="Search CVE…" style="font-size:0.82em;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;color:#3D405B;width:140px;">
      <button id="ail-clear" type="button" style="font-size:0.8em;padding:4px 10px;border:1px solid #cbd5e1;border-radius:4px;background:#f8fafc;cursor:pointer;color:#3D405B;">✕ Clear</button>
    </div>
  </div>
  <table id="ail-table" style="width:100%;border-collapse:collapse;font-size:0.86em;">
    <thead>
      <tr style="background:#3D405B;color:#fff;">
        <th style="padding:10px 14px;text-align:left;min-width:180px;">Issue #</th>
        <th style="padding:10px 14px;text-align:left;">Description</th>
        <th style="padding:10px 14px;text-align:center;">Container</th>
        <th style="padding:10px 14px;text-align:center;">Severity</th>
        <th style="padding:10px 14px;text-align:center;">Scan</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ( $all_issues_posts as $i => $issue ) :
        $iid      = $issue->ID;
        $cve_id   = get_post_meta( $iid, '_cve_id',    true ) ?: $issue->post_title;
        $cont     = get_post_meta( $iid, '_container', true );
        $sev      = get_post_meta( $iid, '_severity',  true );
        $scan     = get_post_meta( $iid, '_scan_type', true );
        $pkg      = get_post_meta( $iid, '_package',   true );
        preg_match( '/<p[^>]*color:#4a5568[^>]*>(.*?)<\/p>/s', $issue->post_content, $dm );
        $desc     = isset( $dm[1] ) ? wp_strip_all_tags( $dm[1] ) : '';
        $desc     = mb_strlen( $desc ) > 160 ? mb_substr( $desc, 0, 160 ) . '…' : $desc;
        $sev_bg   = $sev === 'Critical' ? '#E07A5F' : '#F2CC8F';
        $sev_fg   = $sev === 'Critical' ? '#fff' : '#3D405B';
        $scan_label = $scan === 'cg' ? 'Chainguard' : 'DHI';
        $scan_bg    = $scan === 'cg' ? '#f0fdf4' : '#fef2f2';
        $scan_fg    = $scan === 'cg' ? '#166534'  : '#991b1b';
        $row_bg   = $i % 2 === 0 ? '#fff' : '#f8fafc';
    ?>
    <tr style="background:<?php echo $row_bg; ?>;border-bottom:1px solid #e2e8f0;"
        data-scan="<?php echo esc_attr( $scan ); ?>"
        data-sev="<?php echo esc_attr( $sev ); ?>"
        data-img="<?php echo esc_attr( $cont ); ?>"
        data-cve="<?php echo esc_attr( strtolower( $cve_id ) ); ?>">
      <td style="padding:8px 14px;"><a href="<?php echo esc_url( get_permalink( $iid ) ); ?>" style="color:#E07A5F;font-weight:600;text-decoration:none;"><?php echo esc_html( $cve_id ); ?></a></td>
      <td style="padding:8px 14px;color:#4a5568;font-size:0.9em;"><?php echo esc_html( $desc ?: $pkg ); ?></td>
      <td style="padding:8px 14px;text-align:center;"><span style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:2px 9px;border-radius:12px;font-size:0.82em;font-weight:600;"><?php echo esc_html( $cont ); ?></span></td>
      <td style="padding:8px 14px;text-align:center;"><span style="background:<?php echo $sev_bg; ?>;color:<?php echo $sev_fg; ?>;padding:2px 9px;border-radius:12px;font-size:0.82em;font-weight:700;"><?php echo esc_html( $sev ); ?></span></td>
      <td style="padding:8px 14px;text-align:center;"><span style="background:<?php echo $scan_bg; ?>;color:<?php echo $scan_fg; ?>;padding:2px 9px;border-radius:12px;font-size:0.78em;font-weight:600;"><?php echo $scan_label; ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p id="ail-empty" style="display:none;color:#6b7280;font-size:0.9em;text-align:center;padding:20px;">No issues match the current filters.</p>
</div>
<script>
(function(){
  var rows   = Array.from(document.querySelectorAll('#ail-table tbody tr'));
  var empty  = document.getElementById('ail-empty');
  var count  = document.getElementById('ail-count');
  var filters = {
    scan: document.getElementById('ail-f-scan'),
    sev:  document.getElementById('ail-f-sev'),
    img:  document.getElementById('ail-f-img'),
    cve:  document.getElementById('ail-f-cve'),
  };
  function apply() {
    var visible = 0;
    rows.forEach(function(r) {
      var show = true;
      if (filters.scan.value && r.dataset.scan !== filters.scan.value) show = false;
      if (filters.sev.value  && r.dataset.sev  !== filters.sev.value)  show = false;
      if (filters.img.value  && r.dataset.img  !== filters.img.value)  show = false;
      if (filters.cve.value  && r.dataset.cve.indexOf(filters.cve.value.toLowerCase()) === -1) show = false;
      r.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    empty.style.display = visible === 0 ? '' : 'none';
    count.textContent = '(' + visible + ' total)';
  }
  filters.scan.addEventListener('change', apply);
  filters.sev.addEventListener('change', apply);
  filters.img.addEventListener('change', apply);
  filters.cve.addEventListener('input', apply);
  document.getElementById('ail-clear').addEventListener('click', function(){
    filters.scan.value = ''; filters.sev.value = ''; filters.img.value = ''; filters.cve.value = '';
    apply();
  });
})();
</script>

<!-- ── Chart.js init ──────────────────────────────────────────────────────── -->
<script>
(function() {
  var images    = <?php echo $js_images; ?>;
  var cgCrit    = <?php echo $js_cg_crit; ?>;
  var cgHigh    = <?php echo $js_cg_high; ?>;
  var dhiCrit   = <?php echo $js_dhi_crit; ?>;
  var dhiHigh   = <?php echo $js_dhi_high; ?>;
  var cgTotal   = <?php echo $cg_total; ?>;
  var dhiTotal  = <?php echo $dhi_total; ?>;
  var cgCritTot = <?php echo $cg_critical; ?>;
  var dhiCritTot= <?php echo $dhi_critical; ?>;
  var cgHighTot = <?php echo $cg_high; ?>;
  var dhiHighTot= <?php echo $dhi_high; ?>;

  function init() {
    if (typeof window.Chart === 'undefined') return;

    // Grouped bar: CG vs DHI by container
    var containerEl = document.getElementById('iddContainerChart');
    if (containerEl) {
      new window.Chart(containerEl.getContext('2d'), {
        type: 'bar',
        data: {
          labels: images,
          datasets: [
            { label: 'Chainguard', data: images.map(function(_,i){ return cgCrit[i]+cgHigh[i]; }),
              backgroundColor: '#81B29A', borderColor: '#62967a', borderWidth: 1.5, borderRadius: 4 },
            { label: 'DHI',        data: images.map(function(_,i){ return dhiCrit[i]+dhiHigh[i]; }),
              backgroundColor: '#E07A5F', borderColor: '#c95f44', borderWidth: 1.5, borderRadius: 4 }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top', labels: { color: '#3D405B', font: { size: 13 } } },
            tooltip: { callbacks: { label: function(ctx) {
              var i = ctx.dataIndex;
              var isCg = ctx.datasetIndex === 0;
              var crit = isCg ? cgCrit[i] : dhiCrit[i];
              var high = isCg ? cgHigh[i] : dhiHigh[i];
              return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' (' + crit + ' Critical, ' + high + ' High)';
            }}}
          },
          scales: {
            x: { ticks: { color: '#3D405B', font: { size: 12 } }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: '#3D405B', stepSize: 1, precision: 0 },
                 grid: { color: 'rgba(61,64,91,0.08)' },
                 title: { display: true, text: 'Issue Count', color: '#3D405B', font: { size: 12, weight: '600' } } }
          }
        }
      });
    }

    var donutLabels = ['Critical', 'High', 'Medium', 'Low', 'Unknown'];
    var donutColors = ['#E07A5F', '#F2CC8F', '#94a3b8', '#cbd5e1', '#e2e8f0'];
    var donutBorders= ['#c95f44', '#d4aa6a', '#64748b', '#94a3b8', '#cbd5e1'];
    var cgDonutData  = <?php echo $js_cg_donut; ?>;
    var dhiDonutData = <?php echo $js_dhi_donut; ?>;

    var donutOpts = function(data) {
      return {
        responsive: true, cutout: '65%',
        plugins: {
          datalabels: { display: false },
          legend: { position: 'bottom', labels: { color: '#3D405B', font: { size: 12 },
            filter: function(item, chart) { return chart.datasets[0].data[item.index] > 0; } } },
          tooltip: { callbacks: { label: function(ctx) {
            var val = ctx.raw;
            var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
            var pct = total > 0 ? Math.round(val / total * 100) : 0;
            return ' ' + ctx.label + ': ' + val + ' (' + pct + '%)';
          }}}
        }
      };
    };

    // CG donut
    var cgDonutEl = document.getElementById('iddCgDonut');
    if (cgDonutEl) {
      new window.Chart(cgDonutEl.getContext('2d'), {
        type: 'doughnut',
        data: { labels: donutLabels, datasets: [{ data: cgDonutData,
          backgroundColor: donutColors, borderColor: donutBorders, borderWidth: 2 }] },
        options: donutOpts(cgDonutData)
      });
    }

    // DHI donut
    var dhiDonutEl = document.getElementById('iddDhiDonut');
    if (dhiDonutEl) {
      new window.Chart(dhiDonutEl.getContext('2d'), {
        type: 'doughnut',
        data: { labels: donutLabels, datasets: [{ data: dhiDonutData,
          backgroundColor: donutColors, borderColor: donutBorders, borderWidth: 2 }] },
        options: donutOpts(dhiDonutData)
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>

<?php
    return ob_get_clean();
}
