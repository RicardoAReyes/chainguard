// metrics-exporter/server.js
// Reads grype JSON scan results from /scans and exposes Prometheus metrics.

const http = require('http');
const fs   = require('fs');
const path = require('path');

const SCANS_DIR = process.env.SCANS_DIR || '/scans';
const PORT      = parseInt(process.env.PORT || '9100', 10);
const IMAGES    = ['wordpress', 'nginx', 'mariadb', 'node'];
const SEVERITIES = ['critical', 'high', 'medium', 'low', 'unknown'];

function getLatestScanFile(image) {
  const dir = path.join(SCANS_DIR, image);
  try {
    const files = fs.readdirSync(dir)
      .filter(f => f.endsWith('.json'))
      .sort()
      .reverse();
    return files.length > 0 ? path.join(dir, files[0]) : null;
  } catch {
    return null;
  }
}

function parseScan(filePath) {
  const data    = JSON.parse(fs.readFileSync(filePath, 'utf8'));
  const matches = data.matches || [];
  const counts  = Object.fromEntries(SEVERITIES.map(s => [s, 0]));
  for (const match of matches) {
    const sev = (match.vulnerability?.severity || 'unknown').toLowerCase();
    counts[sev] !== undefined ? counts[sev]++ : counts.unknown++;
  }
  return counts;
}

function metrics() {
  const lines = [
    '# HELP grype_scan_vulnerabilities Vulnerability count by image and severity (latest scan)',
    '# TYPE grype_scan_vulnerabilities gauge',
  ];

  for (const image of IMAGES) {
    const file = getLatestScanFile(image);
    if (!file) continue;
    try {
      const counts = parseScan(file);
      for (const sev of SEVERITIES) {
        lines.push(`grype_scan_vulnerabilities{image="${image}",severity="${sev}"} ${counts[sev]}`);
      }
    } catch (e) {
      console.error(`[exporter] error parsing ${file}: ${e.message}`);
    }
  }

  lines.push('');
  lines.push('# HELP grype_scan_total Total vulnerability count by image (latest scan)');
  lines.push('# TYPE grype_scan_total gauge');

  for (const image of IMAGES) {
    const file = getLatestScanFile(image);
    if (!file) continue;
    try {
      const counts = parseScan(file);
      const total  = SEVERITIES.reduce((s, k) => s + counts[k], 0);
      lines.push(`grype_scan_total{image="${image}"} ${total}`);
    } catch { /* already logged */ }
  }

  return lines.join('\n') + '\n';
}

http.createServer((req, res) => {
  if (req.url === '/metrics') {
    try {
      res.writeHead(200, { 'Content-Type': 'text/plain; version=0.0.4; charset=utf-8' });
      res.end(metrics());
    } catch (e) {
      res.writeHead(500);
      res.end(`error: ${e.message}\n`);
    }
  } else if (req.url === '/health' || req.url === '/') {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('ok\n');
  } else {
    res.writeHead(404);
    res.end('not found\n');
  }
}).listen(PORT, '0.0.0.0', () => {
  console.log(`[exporter] :${PORT}/metrics  scans_dir=${SCANS_DIR}`);
});
