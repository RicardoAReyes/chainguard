<?php
function ttf_child_enqueue_scripts() {
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

    // Supply chain attacks page — load Chart.js from CDN then initialize charts
    if ( is_page( 'supply-chain-attacks' ) ) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
            [],
            '4.4.3',
            true
        );
        wp_add_inline_script( 'chartjs', ttf_supply_chain_charts_js(), 'after' );
    }
}
add_action( 'wp_enqueue_scripts', 'ttf_child_enqueue_scripts' );

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

  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCharts);
  } else {
    initCharts();
  }
})();
JS;
}
