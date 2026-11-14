import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';

// Capture the public reference as a portable static site. Run manually to refresh.
const origin = 'https://www.drrunaakhterdola.com';
const output = path.resolve('dist');
const routes = new Map([['/', 'index.html'], ['/about-us', 'about-us.html'], ['/service-page', 'service-page.html'], ['/faq-page', 'faq-page.html'], ['/gallery-page', 'gallery-page.html']]);
const assets = new Map();
const failures = [];
for (const directory of ['css', 'js', 'fonts', 'images']) {
  await mkdir(path.join(output, 'assets', directory), { recursive: true });
}

async function replaceAsync(text, pattern, transform) {
  const matches = [...text.matchAll(pattern)];
  const replacements = await Promise.all(matches.map(transform));
  for (let i = matches.length - 1; i >= 0; i--) {
    text = text.slice(0, matches[i].index) + replacements[i] + text.slice(matches[i].index + matches[i][0].length);
  }
  return text;
}

async function download(raw, base = origin) {
  if (!raw || /^(data:|#|javascript:)/i.test(raw)) return raw;
  const url = new URL(raw.replaceAll('&amp;', '&'), base);
  if (url.origin === origin) url.searchParams.delete('v');
  const fragment = url.hash;
  url.hash = '';
  const key = url.href;
  if (!assets.has(key)) {
    const hash = createHash('sha256').update(key).digest('hex').slice(0, 12);
    let basename = path.posix.basename(url.pathname).replace(/[^a-zA-Z0-9._-]/g, '-');
    if (!path.extname(basename)) basename += url.hostname.includes('googleapis') ? '.css' : '.jpg';
    const extension = path.posix.extname(basename).toLowerCase();
    const directory = extension === '.css' ? 'css' : extension === '.js' ? 'js' : ['.woff', '.woff2', '.ttf', '.otf', '.eot'].includes(extension) ? 'fonts' : 'images';
    const file = `assets/${directory}/${hash}-${basename}`;
    assets.set(key, { file, promise: null });
    const entry = assets.get(key);
    entry.promise = (async () => {
      try {
        let response;
        for (let attempt = 0; attempt < 3; attempt++) {
          response = await fetch(key, { signal: AbortSignal.timeout(45000), headers: { Referer: origin + '/' } });
          if (response.ok) break;
          await new Promise(resolve => setTimeout(resolve, 500));
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        if (response.headers.get('content-type')?.includes('text/css') || file.endsWith('.css')) {
          let css = await response.text();
          css = await replaceAsync(css, /url\(\s*(['"]?)([^)'"\s]+)\1\s*\)/g, async match => {
            const local = await download(match[2], key);
            return `url("${local.startsWith('assets/') ? path.posix.relative(path.posix.dirname(file), local) : local}")`;
          });
          css = await replaceAsync(css, /@import\s+(['"])([^'"]+)\1/g, async match => {
            const local = await download(match[2], key);
            return `@import "${local.startsWith('assets/') ? path.posix.relative(path.posix.dirname(file), local) : local}"`;
          });
          await writeFile(path.join(output, file), css);
        } else {
          await writeFile(path.join(output, file), Buffer.from(await response.arrayBuffer()));
        }
      } catch (error) {
        failures.push({ url: key, error: error.message });
        entry.failed = true;
      }
    })();
  }
  const entry = assets.get(key);
  await entry.promise;
  return (entry.failed ? key : entry.file) + fragment;
}

for (const [route, filename] of routes) {
  const response = await fetch(origin + route);
  if (!response.ok) throw new Error(`${route}: HTTP ${response.status}`);
  let html = await response.text();
  // Analytics and server-session tokens do not belong in the standalone copy.
  html = html.replace(/<script\b[^>]*>(?:(?!<\/script>)[\s\S])*?<\/script>/gi, block => block.includes('googletagmanager') ? '' : block);
  html = html.replace(/<noscript>[\s\S]*?googletagmanager[\s\S]*?<\/noscript>/gi, '');
  html = html.replace(/<meta\b[^>]*name="csrf-token"[^>]*>/gi, '');
  html = html.replace(/<input\b[^>]*name="_token"[^>]*>/gi, '');
  html = html.replace(/<link\b[^>]*rel="(?:manifest|author)"[^>]*>/gi, '');
  html = html.replace(/<meta\b[^>]*name="msapplication-config"[^>]*>/gi, '');
  html = html.replace(/<link\b[^>]*rel="preload"[^>]*href="https:\/\/www\.drrunaakhterdola\.com\/?"[^>]*>/gi, '');
  html = html.replace(/<script\b[^>]*>\s*\$\(document\)\.ready\(function\(\)\s*\{[\s\S]*?review-store[\s\S]*?<\/script>/gi, '');
  html = await replaceAsync(html, /<(?:script|img|link|source)\b[^>]*>/gi, async match => {
    let tag = match[0];
    if (/rel="(?:canonical|preconnect)"/i.test(tag)) return tag;
    tag = await replaceAsync(tag, /\b(src|href|poster)=(['"])(.*?)\2/gi, async attr => {
      if (/^(?:data:|#|javascript:)/i.test(attr[3])) return attr[0];
      return `${attr[1]}=${attr[2]}${await download(attr[3])}${attr[2]}`;
    });
    return tag;
  });
  html = await replaceAsync(html, /url\(\s*(['"]?)(https?:\/\/[^)'"\s]+)\1\s*\)/g, async match => `url("${await download(match[2])}")`);
  html = await replaceAsync(html, /<a\b[^>]*>/gi, async match => replaceAsync(match[0], /href=(['"])([^'"]+\.(?:jpe?g|png|webp|svg)(?:\?[^'"]*)?)\1/gi, async attr => `href=${attr[1]}${await download(attr[2])}${attr[1]}`));
  // Keep all navigation within the downloaded five-page site.
  html = html.replace(/href=(['"])(https?:\/\/www\.drrunaakhterdola\.com\/?[^'"\s]*)\1/gi, (all, quote, raw) => {
    const url = new URL(raw);
    const file = routes.get(url.pathname.replace(/\/$/, '') || '/');
    return file ? `href=${quote}${file}${url.hash}${quote}` : all;
  });
  html = html.replace('</body>', '    <script src="assets/js/site.js" defer></script>\n</body>');
  html = html.replace('</head>', '    <link rel="stylesheet" href="assets/css/site.css">\n</head>');
  await writeFile(path.join(output, filename), html.replace(/\r\n/g, '\n').replace(/[\t ]+$/gm, '').trimEnd() + '\n');
  console.log(`Saved ${filename}`);
}
console.log(`Downloaded ${assets.size} assets; ${failures.length} failures.`);
if (failures.length) {
  console.log(JSON.stringify(failures, null, 2));
  process.exitCode = 1;
}
