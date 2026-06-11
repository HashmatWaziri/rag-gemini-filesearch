import { chromium } from 'playwright';

const BASE = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8765';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'admin@glc.test';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'GlcDemo2026';

function log(step, detail = '') {
    console.log(`[e2e] ${step}${detail ? `: ${detail}` : ''}`);
}

async function loginAsAdmin(page) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.locator('#email').fill(ADMIN_EMAIL);
    await page.locator('#password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL(/\/admin\//, { timeout: 15000 });
}

async function createAccessCode(page) {
    await page.goto(`${BASE}/admin/access-codes`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: 'New codes' }).click();
    await page.locator('#note').fill('Playwright E2E flow');
    await page.getByRole('button', { name: 'Create' }).click();
    await page.waitForTimeout(1500);

    const codeCell = page.locator('tbody td.font-mono').first();
    await codeCell.waitFor({ timeout: 10000 });
    const code = (await codeCell.textContent())?.trim();
    if (!code || !/^[A-Z0-9]{8}$/.test(code)) {
        throw new Error(`Could not read generated access code (got "${code ?? ''}")`);
    }

    return code;
}

async function answerAllMcqs(page) {
    const seen = new Set();

    while (true) {
        const labels = page.locator('label:has(input[type="radio"]:not(:disabled))');
        const count = await labels.count();
        let answered = false;

        for (let i = 0; i < count; i += 1) {
            const label = labels.nth(i);
            const name = await label.locator('input[type="radio"]').getAttribute('name');
            if (!name || seen.has(name)) {
                continue;
            }
            seen.add(name);
            await label.click();
            await page.waitForTimeout(50);
            answered = true;
            break;
        }

        if (!answered) {
            break;
        }
    }
}

async function finishSection(page) {
    const finish = page.getByRole('button', { name: /Finish section/i });
    await finish.click();
    const finishAnyway = page.getByRole('button', { name: 'Finish anyway' });
    if (await finishAnyway.isVisible({ timeout: 1000 }).catch(() => false)) {
        await finishAnyway.click();
    }
    await page.waitForLoadState('networkidle');
}

async function runPlacementFlow(page, code) {
    await page.goto(`${BASE}/placement`, { waitUntil: 'networkidle' });

    await page.locator('#access-code').fill(code);
    await page.getByRole('button', { name: 'Continue' }).click();

    await page
        .getByText('I have read and understood the privacy notice.')
        .click();
    await page.getByRole('button', { name: 'I agree, continue' }).click();

    await page.locator('#name').fill('Playwright Candidate');
    await page.locator('#email').fill('playwright-e2e@example.com');
    await page.locator('#age').fill('20');
    await page.getByRole('button', { name: 'Start the test' }).click();

    await page.waitForURL(/\/placement\/instructions/, { timeout: 15000 });
    await page
        .getByText('I have read the instructions and I am ready to continue.')
        .click();
    await page.getByRole('button', { name: 'Continue to device check' }).click();

    await page.waitForURL(/\/placement\/device-check/, { timeout: 15000 });
    await page.getByRole('button', { name: 'Test microphone' }).click();
    await page.getByText('Ready', { exact: true }).nth(1).waitFor({ timeout: 10000 });
    await page.getByRole('button', { name: 'Start the test' }).click();

    await page.waitForURL(/\/placement\/test/, { timeout: 15000 });

    log('section', 'Reading');
    await answerAllMcqs(page);
    await finishSection(page);

    log('section', 'Grammar & Vocabulary');
    await answerAllMcqs(page);
    await finishSection(page);

    log('section', 'Listening');
    while ((await page.getByRole('button', { name: 'Play once' }).count()) > 0) {
        await page.getByRole('button', { name: 'Play once' }).first().click();
        await page.waitForTimeout(2000);
    }
    await answerAllMcqs(page);
    await finishSection(page);

    log('section', 'Writing');
    const essay = Array.from({ length: 160 }, (_, i) => `word${i}`).join(' ');
    await page.locator('textarea').fill(essay);
    await page.waitForTimeout(1000);
    await finishSection(page);

    log('section', 'Speaking');
    await page.getByRole('button', { name: 'Start recording' }).click();
    await page.waitForTimeout(3000);
    await page.getByRole('button', { name: 'Stop recording' }).click();
    await page.getByRole('button', { name: 'Save this recording' }).waitFor({ timeout: 15000 });
    await page.getByRole('button', { name: 'Save this recording' }).click();
    await page.getByText('Recording saved').waitFor({ timeout: 15000 });

    await page.getByRole('button', { name: 'Submit my placement test' }).click();
    await page.waitForURL(/\/placement\/complete/, { timeout: 30000 });
    await page.getByText(/pending review/i).waitFor({ timeout: 10000 });
}

const browser = await chromium.launch({
    headless: true,
    args: [
        '--use-fake-ui-for-media-stream',
        '--use-fake-device-for-media-stream',
    ],
});
const context = await browser.newContext({
    permissions: ['microphone'],
});
const page = await context.newPage();

try {
    log('login');
    await loginAsAdmin(page);

    log('create access code');
    const code = await createAccessCode(page);
    log('access code', code);

    await context.clearCookies();
    log('placement flow');
    await runPlacementFlow(page, code);

    log('done', 'Full placement flow completed successfully');
} catch (error) {
    console.error('[e2e] FAILED:', error.message);
    await page.screenshot({ path: '/tmp/e2e-placement-failure.png', fullPage: true });
    process.exitCode = 1;
} finally {
    await browser.close();
}
