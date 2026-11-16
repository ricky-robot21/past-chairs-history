// Extra acceptance checks: visible focus on every control, token retheme, third-party requests, PHP warnings in output.
// Usage: node tools/checks.mjs <page url>
import { chromium } from 'playwright';

const url = process.argv[2] || 'https://sbotfam.test/flhist-dev/';
const origin = new URL(url).origin;
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 900 }, ignoreHTTPSErrors: true });
const page = await context.newPage();
const errors = [];
const thirdParty = new Set();
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
page.on('pageerror', (e) => errors.push(String(e)));
page.on('request', (r) => {
  if (!r.url().startsWith('data:') && new URL(r.url()).origin !== origin) thirdParty.add(new URL(r.url()).host);
});

const response = await page.goto(url, { waitUntil: 'networkidle' });
const result = { url, status: response.status(), consoleErrors: errors };

// PHP messages printed into the page, split by whether they name the plugin.
const html = await page.content();
const phpMessages = [...html.matchAll(/(Warning|Notice|Deprecated|Fatal error)<\/b>:\s*([^<]{0,160})/g)].map((m) => m[1] + ': ' + m[2].trim());
result.phpMessages = phpMessages.length ? phpMessages : [];
result.phpMessagesFromPlugin = phpMessages.filter((m) => /flhist/i.test(m));

result.hasTimeline = await page.locator('.flhist-timeline').count();
result.hasJumpBar = await page.locator('.flhist-jump').count();
result.hasToolbar = await page.locator('.flhist-toolbar').count();
result.entries = await page.locator('.flhist-entry').count();

// Keyboard: tab through the first controls inside the timeline and report the focus outline.
await page.locator('[data-flhist-q]').focus();
const stops = [];
for (let i = 0; i < 14; i++) {
  const stop = await page.evaluate(() => {
    const el = document.activeElement;
    const cs = getComputedStyle(el);
    return {
      tag: el.tagName.toLowerCase(),
      label: (el.getAttribute('aria-label') || el.textContent || el.id || '').trim().replace(/\s+/g, ' ').slice(0, 28),
      outline: cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) >= 2,
      inFlhist: !!el.closest('.flhist'),
    };
  });
  stops.push(stop);
  await page.keyboard.press('Tab');
}
result.focus = { stops: stops.length, withoutVisibleOutline: stops.filter((s) => s.inFlhist && !s.outline).map((s) => s.tag + ' ' + s.label) };

// Tokens: change accent, heading font, and text color, and see what follows.
result.tokens = await page.evaluate(() => {
  const root = document.querySelector('.flhist');
  const read = () => {
    const q = (s) => document.querySelector(s);
    const css = (el, p) => (el ? getComputedStyle(el)[p] : null);
    return {
      button: css(q('.flhist-button'), 'backgroundColor'),
      decadeFont: css(q('.flhist-decade__title'), 'fontFamily'),
      cardTitleColor: css(q('.flhist-card__title'), 'color'),
      spine: css(q('.flhist-decade'), 'borderLeftColor'),
      chairBadge: css(q('.flhist-badge--chair'), 'color'),
      radius: css(q('.flhist-card'), 'borderTopLeftRadius'),
    };
  };
  const before = read();
  root.style.setProperty('--flhist-color-accent', 'rgb(10, 130, 10)');
  root.style.setProperty('--flhist-font-heading', 'Georgia, serif');
  root.style.setProperty('--flhist-color-text', 'rgb(40, 40, 40)');
  root.style.setProperty('--flhist-color-spine', 'rgb(200, 100, 0)');
  root.style.setProperty('--flhist-color-chair', 'rgb(0, 100, 200)');
  root.style.setProperty('--flhist-radius', '16px');
  const after = read();
  return { before, after };
});

result.thirdPartyHosts = [...thirdParty];
await browser.close();
console.log(JSON.stringify(result, null, 2));
