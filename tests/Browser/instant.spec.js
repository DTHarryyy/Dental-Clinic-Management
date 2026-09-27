import { expect, test } from '@playwright/test';

// Interaction-latency behaviours: dialogs that save optimistically, fragments served from
// the hover-prefetch cache, and the Turbo regressions that used to force reloads or stack
// duplicate listeners. Layout is covered by responsive.spec.js, so desktop only here.
test.skip(({ viewport }) => (viewport?.width ?? 0) < 1024, 'desktop-only interaction checks');

async function login(page, role) {
    await page.goto(`/__e2e/login/${role}?token=responsive-local-only`);
    await expect(page).toHaveURL(/dashboard/);
}

// Navigate the way a user does (a Turbo visit), not page.goto (a full load).
async function turboVisit(page, path) {
    await page.evaluate((target) => window.Turbo.visit(target), path);
    await page.waitForFunction((target) => window.location.pathname === target && !document.documentElement.classList.contains('turbo-loading'), path);
}

test('invoice details load once per click, even after repeated Turbo visits', async ({ page }) => {
    await login(page, 'admin');
    await turboVisit(page, '/billing');
    await turboVisit(page, '/dashboard');
    await turboVisit(page, '/billing');

    const detailRequests = [];
    page.on('request', (request) => {
        if (/\/billing\/\d+\/details/.test(request.url())) detailRequests.push(request.url());
    });

    const opener = page.locator('[data-invoice-details-url]').first();
    await opener.hover();
    await opener.click();
    await expect(page.locator('[data-invoice-details-body]')).toContainText(/INV-/);

    // One prefetch (hover) + at most one background revalidation. Before the fix, each
    // earlier visit to /billing added another set of click listeners - and another fetch.
    expect(detailRequests.length).toBeLessThanOrEqual(2);
});

test('billing create page still builds its line items on a second Turbo visit', async ({ page }) => {
    await login(page, 'admin');
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await turboVisit(page, '/billing/create');
    await turboVisit(page, '/billing');
    await turboVisit(page, '/billing/create');

    await expect(page.locator('#line-items .line-item-row')).not.toHaveCount(0);
    expect(errors.filter((message) => message.includes('already been declared'))).toEqual([]);
});

test('links inside the patient detail dialog navigate the whole page', async ({ page }) => {
    await login(page, 'admin');
    await turboVisit(page, '/patients');

    await page.locator('[data-patient-detail]:visible').first().click();
    const dialog = page.locator('#dialog-patient-view');
    await expect(dialog.getByRole('link', { name: '+ Add Record' })).toBeVisible();
    await dialog.getByRole('link', { name: '+ Add Record' }).click();

    await expect(page).toHaveURL(/\/records\/create/);
    await expect(page.getByText('Content missing')).toHaveCount(0);
});

test('a dialog save closes immediately, toasts, and keeps the list filters', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('/patients?search=a');

    const edit = page.getByRole('button', { name: 'Edit' }).first();
    await edit.click();
    const form = page.locator('form[action*="/demographics"]:visible').first();
    await expect(form).toBeVisible();

    // Hold the background refresh GET so we can observe the state between save and refresh.
    let releaseRefresh;
    const refreshHeld = new Promise((resolve) => { releaseRefresh = resolve; });
    await page.route(/\/patients(\?.*)?$/, async (route) => {
        if (route.request().method() === 'GET') await refreshHeld;
        await route.continue();
    });

    await form.getByRole('button', { name: 'Save Demographics' }).click();

    await expect(page.locator('#app-toast')).toContainText('Patient details updated');
    await expect(form).toBeHidden();

    releaseRefresh();
    await page.waitForFunction(() => !document.documentElement.classList.contains('turbo-loading'));
    await expect(page).toHaveURL(/\/patients\?search=a/);

    // And the same dialog opens again after the morph refresh.
    await page.getByRole('button', { name: 'Edit' }).first().click();
    await expect(page.locator('form[action*="/demographics"]:visible').first()).toBeVisible();
});

test('typing in a filter while its refresh is in flight keeps every character', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('/patients');

    // Slow the filter responses so keystrokes land while a morph is pending.
    await page.route(/\/patients\?/, async (route) => {
        await new Promise((resolve) => setTimeout(resolve, 700));
        await route.continue();
    });

    const search = page.locator('form[data-auto-filter="patients"] input[name="search"]');
    await search.click();
    await search.pressSequentially('mar', { delay: 60 });
    await page.waitForTimeout(600); // debounce fires, request in flight
    await search.pressSequentially('ia', { delay: 60 });

    await page.waitForTimeout(2500);
    await expect(search).toHaveValue('maria');
});

test('recording a payment keeps the invoice dialog open and re-renders it in place', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('/billing?status=Partial');

    await page.locator('[data-invoice-details-url]:visible').first().click();
    const dialog = page.locator('#dialog-invoice-details');
    const form = dialog.locator('#record-payment-form');
    await expect(form).toBeVisible();

    await form.locator('[name="amount"]').fill('1.00');
    await form.locator('#invoice-payment-method').selectOption({ label: 'Cash' });
    await form.getByRole('button', { name: /Record/ }).click();

    // The e2e server runs QUEUE_CONNECTION=sync, so the billing email is sent inside this
    // POST - allow for it. (Real environments queue it; see .env.example.)
    await expect(page.locator('#app-toast')).toBeVisible({ timeout: 20_000 });
    await page.waitForFunction(() => !document.documentElement.classList.contains('turbo-loading'));

    // Still open after the save and after the background refresh of the list behind it,
    // now showing the new payment.
    await expect(dialog.locator('[data-invoice-details-body]')).toBeVisible();
    await expect(dialog.locator('[data-invoice-details-body]')).toContainText('1.00');
});
