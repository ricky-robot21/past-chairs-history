// Review run: screenshots at 375, 768, 1280, an axe scan, and the acceptance checks that can be scripted.
// Usage: node tools/review.mjs <page url>
// Dev tooling only. Lives outside the plugin folder.
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const url = process.argv[2] || 'https://sbotfam.test/flhist-dev/';
const origin = new URL(url).origin;
const out = new URL('../review/', import.meta.url).pathname;
mkdirSync(out, { recursive: true });

const browser = await chromium.launch();
const results = { url, widths: {}, axe: {}, checks: {} };
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];

for (const width of [375, 768, 1280]) {
  const context = await browser.newContext({ viewport: { width, height: width === 1280 ? 900 : 812 }, ignoreHTTPSErrors: true });
  const page = await context.newPage();
  const consoleErrors = [];
  const thirdParty = new Set();
  let dialogOpened = false;
  page.on('console', (m) => m.type() === 'error' && consoleErrors.push(m.text()));
  page.on('pageerror', (e) => consoleErrors.push(String(e)));
  page.on('request', (r) => {
    const host = new URL(r.url()).origin;
    if (host !== origin && !r.url().startsWith('data:')) thirdParty.add((dialogOpened ? 'AFTER-OPEN ' : 'BEFORE-OPEN ') + new URL(r.url()).host);
  });

  await page.goto(url, { waitUntil: 'networkidle' });
  const info = await page.evaluate(() => ({
    pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    flhistOverflow: [...document.querySelectorAll('.flhist *')].filter((e) => !e.closest('.flhist-jump__list') && e.getBoundingClientRect().right > innerWidth + 1 && getComputedStyle(e).position !== 'fixed').length,
    entries: document.querySelectorAll('.flhist-entry').length,
  }));

  await page.screenshot({ path: `${out}${width}-1-top.png` });
  await page.locator('.flhist').screenshot({ path: `${out}${width}-2-full-timeline.png`, animations: 'disabled' }).catch(() => {});

  // Rich entry card
  const rich = page.locator('#entry-sample-chair-2008');
  if (await rich.count()) {
    await rich.scrollIntoViewIfNeeded();
    await page.evaluate(() => window.scrollBy(0, -200));
    // Card photos load lazily. Wait for them so the screenshot shows them.
    await page.waitForFunction(() => [...document.querySelectorAll('#entry-sample-chair-2008 img')].every((i) => i.complete && i.naturalWidth > 0));
    await page.screenshot({ path: `${out}${width}-3-rich-card.png` });

    // Keyboard only: focus the button, Enter opens, Escape closes, focus returns.
    const button = rich.locator('[data-flhist-open]');
    await button.focus();
    dialogOpened = true;
    await page.keyboard.press('Enter');
    await page.waitForSelector('dialog[open]');
    await page.waitForTimeout(800);
    await page.screenshot({ path: `${out}${width}-4-dialog.png` });
    const keyboard = await page.evaluate(() => ({
      focusInDialog: document.querySelector('dialog').contains(document.activeElement),
      iframe: !!document.querySelector('dialog iframe'),
      hash: location.hash,
    }));
    if (width === 1280) {
      const dialogAxe = await new AxeBuilder({ page }).withTags(tags).include('.flhist').analyze();
      results.axe.dialogOpen = dialogAxe.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help }));
    }
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
    const after = await page.evaluate(() => ({
      closed: !document.querySelector('dialog').open,
      iframe: !!document.querySelector('dialog iframe'),
      hash: location.hash,
      focusOnButton: document.activeElement.matches('[data-flhist-open]') && !!document.activeElement.closest('#entry-sample-chair-2008'),
    }));
    results.checks[width] = { keyboard, after };
  }

  // Filtered state
  await page.fill('[data-flhist-q]', 'houston');
  await page.waitForTimeout(400);
  await page.locator('.flhist').scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${out}${width}-5-filtered.png` });

  if (width === 1280) {
    await page.fill('[data-flhist-q]', '');
    await page.waitForTimeout(300);
    const scoped = await new AxeBuilder({ page }).withTags(tags).include('.flhist').analyze();
    const whole = await new AxeBuilder({ page }).withTags(tags).analyze();
    const summarize = (r) => r.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help, sample: v.nodes[0]?.target?.join(' ') }));
    results.axe.plugin = summarize(scoped);
    results.axe.wholePage = summarize(whole);
    results.axe.passes = scoped.passes.length;
  }

  results.widths[width] = { ...info, consoleErrors, thirdParty: [...thirdParty] };
  await context.close();
}

await browser.close();
writeFileSync(`${out}results.json`, JSON.stringify(results, null, 2));
console.log(JSON.stringify(results, null, 2));
