// Screenshots of Dog-ear Books, as used in Reviews' docs and Marketplace
// listing. Run against a freshly seeded demo (php artisan demo:seed --fresh)
// with the demo users:
//
//   cd scripts && npm install && npm run screenshots
//
// DEMO_URL     the demo's address (default https://reviews-sandbox.test)
// SCREENSHOTS  where to write them (default ./screenshots)
// CHROME       a Chrome or Chromium to drive (default: Google Chrome on macOS)
// REVIEW       the ID of a review with a reply, for the Control Panel's review page
import { chromium } from 'playwright-core';
import { mkdirSync } from 'node:fs';

const base = (process.env.DEMO_URL || 'https://reviews-sandbox.test').replace(/\/$/, '');
const out = process.env.SCREENSHOTS || new URL('./screenshots', import.meta.url).pathname;
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
mkdirSync(out, { recursive: true });

// Local demos often use a self-signed certificate (Herd, Valet).
const browser = await chromium.launch({ args: ['--ignore-certificate-errors'], executablePath: chrome });
const desktop = { viewport: { width: 1280, height: 860 }, deviceScaleFactor: 2, colorScheme: 'light' };

async function settle(page) {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(600);
}

const shop = await browser.newContext(desktop);
const page = await shop.newPage();

await page.goto(`${base}/products/a-field-guide-to-leaving`);
await settle(page);
await page.screenshot({ path: `${out}/product.png` });

await page.goto(`${base}/products/a-field-guide-to-leaving`);
await settle(page);
const reviews = page.locator('#reviews');
await reviews.scrollIntoViewIfNeeded();
const box = await reviews.boundingBox();
await page.screenshot({ path: `${out}/reviews.png`, clip: { x: box.x - 32, y: box.y - 8, width: box.width + 64, height: 860 } });

const form = page.locator('.review-form');
await page.click('label[for=rating-4]');
await page.fill('input[name=title]', 'A book to keep');
await page.fill('textarea[name=body]', 'Short chapters, perfect for the commute. I read the last one twice.');
await page.fill('input[name=name]', 'Sam Reader');
await page.fill('input[name=email]', 'sam@example.com');
await form.screenshot({ path: `${out}/form.png` });

// The Control Panel, as the demo admin.
const cp = await browser.newContext(desktop);
const admin = await cp.newPage();
// Signed in with the demo's own front-end sign-in form.
await admin.goto(`${base}/login`);
await admin.fill('input[name=email]', 'admin@example.com');
await admin.fill('input[name=password]', 'password');
await Promise.all([admin.waitForNavigation(), admin.click('button[type=submit]')]);

// Trial-mode licence notice, if Statamic shows one.
const snooze = admin.getByText('Snooze');

await admin.goto(`${base}/cp/reviews`);
await settle(admin);
if (await snooze.isVisible().catch(() => false)) await snooze.click();
await settle(admin);
await admin.screenshot({ path: `${out}/cp-reviews.png` });

if (process.env.REVIEW) {
    await admin.goto(`${base}/cp/reviews/${process.env.REVIEW}`);
    await settle(admin);
    await admin.screenshot({ path: `${out}/cp-review.png` });
}

await browser.close();
console.log(`Screenshots in ${out}`);
