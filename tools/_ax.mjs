import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
const url = process.argv[2] || 'https://sbotfam.test/flhist-dev/';
const b = await chromium.launch();
for (const w of [1280,375]) {
const p = await (await b.newContext({ viewport:{width:w,height:900}, ignoreHTTPSErrors:true })).newPage();
await p.goto(url, {waitUntil:'networkidle'});
const r = await new AxeBuilder({page:p}).withTags(['wcag2a','wcag2aa','wcag21a','wcag21aa']).exclude('.flhist').analyze();
console.log('== width',w);
for (const v of r.violations) console.log(v.id, v.impact, v.nodes.length, '|', v.help, '\n   ', v.nodes.slice(0,3).map(n=>n.target.join(' ')+' :: '+(n.html||'').slice(0,110).replace(/\s+/g,' ')).join('\n    '));
}
await b.close();
