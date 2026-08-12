import { expect, test } from '@playwright/test';

const rolePages = {
    admin: ['/dashboard', '/profile', '/patients', '/appointments', '/records', '/billing', '/reports', '/users', '/settings/clinic', '/settings/services'],
    dentist: ['/dashboard', '/profile', '/patients', '/appointments', '/records'],
    receptionist: ['/dashboard', '/profile', '/patients', '/appointments', '/billing'],
};

async function login(page, role) {
    await page.goto(`/__e2e/login/${role}?token=responsive-local-only`);
    await expect(page).toHaveURL(/dashboard/);
}

async function expectNoPageOverflow(page) {
    const metrics = await page.evaluate(async () => {
        window.scrollTo(0, document.documentElement.scrollHeight);
        await new Promise((resolve) => requestAnimationFrame(resolve));
        const navigation = document.querySelector('.mobile-bottom-nav');
        const navigationVisible = navigation && getComputedStyle(navigation).display !== 'none';
        const navigationTop = navigationVisible ? navigation.getBoundingClientRect().top : window.innerHeight;
        const controls = [...document.querySelectorAll('main button, main a, main input, main select')]
            .filter((element) => {
                const rect = element.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < window.innerHeight;
            });

        return {
            documentWidth: document.documentElement.scrollWidth,
            viewportWidth: document.documentElement.clientWidth,
            coveredBottom: navigationVisible && controls.some((element) => element.getBoundingClientRect().bottom > navigationTop + 1),
        };
    });
    expect(metrics.documentWidth).toBeLessThanOrEqual(metrics.viewportWidth + 1);
    expect(metrics.coveredBottom).toBeFalsy();
}

async function expectDesktopShellContained(page) {
    const metrics = await page.evaluate(async () => {
        const scrollingElement = document.scrollingElement;
        const scrollRegion = document.querySelector('[data-app-scroll]');
        const sidebar = document.querySelector('[data-app-sidebar]');
        const sidebarNavigation = sidebar?.querySelector('nav');
        const container = document.querySelector('.app-container');
        const sidebarBefore = sidebar?.getBoundingClientRect();

        window.scrollTo(0, scrollingElement.scrollHeight);
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

        const rootScrollY = window.scrollY;
        scrollRegion.scrollTop = scrollRegion.scrollHeight;
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

        const sidebarAfter = sidebar?.getBoundingClientRect();
        const scrollRegionRect = scrollRegion.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();

        return {
            rootClientHeight: scrollingElement.clientHeight,
            rootScrollHeight: scrollingElement.scrollHeight,
            rootScrollY,
            mainClientHeight: scrollRegion.clientHeight,
            mainScrollHeight: scrollRegion.scrollHeight,
            mainScrollTop: scrollRegion.scrollTop,
            trailingSpace: Math.round(scrollRegionRect.bottom - containerRect.bottom),
            sidebarBefore: sidebarBefore && { top: sidebarBefore.top, bottom: sidebarBefore.bottom },
            sidebarAfter: sidebarAfter && { top: sidebarAfter.top, bottom: sidebarAfter.bottom },
            sidebarNavigationClientHeight: sidebarNavigation?.clientHeight,
            sidebarNavigationScrollHeight: sidebarNavigation?.scrollHeight,
        };
    });

    expect(metrics.rootScrollHeight).toBeLessThanOrEqual(metrics.rootClientHeight + 1);
    expect(metrics.rootScrollY).toBe(0);
    expect(metrics.sidebarAfter).toEqual(metrics.sidebarBefore);

    if (metrics.mainScrollHeight > metrics.mainClientHeight + 1) {
        expect(metrics.mainScrollTop).toBeGreaterThan(0);
        expect(metrics.trailingSpace).toBeGreaterThanOrEqual(0);
        expect(metrics.trailingSpace).toBeLessThanOrEqual(32);
    } else {
        expect(metrics.mainScrollTop).toBe(0);
    }

    return metrics;
}

for (const [role, paths] of Object.entries(rolePages)) {
    test(`${role} shell and pages remain contained`, async ({ page }, testInfo) => {
        await login(page, role);
        for (const path of paths) {
            await page.goto(path);
            await expect(page.locator('main')).toBeVisible();
            await expectNoPageOverflow(page);
            if (testInfo.project.name === 'desktop') await expectDesktopShellContained(page);
        }

        if (testInfo.project.name === 'phone') {
            await expect(page.locator('.mobile-bottom-nav')).toBeVisible();
            await page.getByRole('button', { name: 'More' }).click();
            await expect(page.getByRole('dialog', { name: 'Navigation menu' })).toBeVisible();
        } else if (testInfo.project.name === 'desktop') {
            await expect(page.locator('aside').first()).toBeVisible();
        }
    });
}

test('role navigation and direct module access follow the RBAC matrix', async ({ page }) => {
    const expectations = {
        admin: { allowed: ['/records', '/billing', '/reports', '/users', '/settings'], denied: [] },
        dentist: { allowed: ['/records'], denied: ['/billing', '/reports', '/users', '/settings'] },
        receptionist: { allowed: ['/billing'], denied: ['/records', '/reports', '/users', '/settings'] },
    };

    for (const [role, access] of Object.entries(expectations)) {
        await login(page, role);

        for (const path of access.allowed) {
            await expect(page.locator(`a[href$="${path}"]`).first()).toBeAttached();
            const response = await page.goto(path);
            expect(response.status(), `${role} should reach ${path}`).toBe(200);
            await page.goto('/dashboard');
        }

        for (const path of access.denied) {
            await expect(page.locator(`a[href$="${path}"]`)).toHaveCount(0);
            const response = await page.goto(path);
            expect(response.status(), `${role} should be denied ${path}`).toBe(403);
            await page.goto('/dashboard');
        }
    }
});

test('public and authentication flows fit compact widths', async ({ page }) => {
    for (const path of ['/login', '/forgot-password', '/book-appointment', '/book-appointment/success']) {
        await page.goto(path);
        await expectNoPageOverflow(page);
    }
});

test('profile navigation and password visibility are accessible', async ({ page }, testInfo) => {
    await login(page, 'admin');
    if (testInfo.project.name === 'phone') {
        await page.getByRole('button', { name: 'More' }).click();
        await page.getByRole('link', { name: 'My Profile' }).click();
    } else {
        await page.locator('header').getByRole('button').last().click();
        await page.getByRole('link', { name: 'My Profile' }).click();
    }
    await expect(page).toHaveURL(/\/profile/);
    await expect(page.getByRole('heading', { name: 'Personal Information' })).toBeVisible();
    const password = page.getByLabel('New password', { exact: true });
    await password.fill('ExamplePassword1');
    await page.getByRole('button', { name: 'Show new password' }).click();
    await expect(password).toHaveAttribute('type', 'text');
    await expectNoPageOverflow(page);
});

test('global search is keyboard accessible and opens a patient result', async ({ page }, testInfo) => {
    await login(page, 'admin');
    const mobile = testInfo.project.name === 'phone';
    if (mobile) await page.getByRole('button', { name: 'Search clinic records' }).click();
    const input = page.locator(`[data-global-search-input][data-search-mode="${mobile ? 'mobile' : 'desktop'}"]`);
    await input.fill('Alexandria-Marguerite');
    await expect(page.getByRole('option', { name: /Alexandria-Marguerite/ }).first()).toBeVisible();
    await input.press('ArrowDown');
    await input.press('Enter');
    await expect(page).toHaveURL(/\/patients\?view=/);
    await expect(page.getByRole('dialog', { name: 'Patient Details' })).toBeVisible();
    await expectNoPageOverflow(page);
});

test('logout requires confirmation', async ({ page }, testInfo) => {
    await login(page, 'admin');
    if (testInfo.project.name === 'phone') {
        await page.getByRole('button', { name: 'More' }).click();
        await page.getByRole('button', { name: 'Logout' }).click();
    } else {
        await page.locator('header').getByRole('button').last().click();
        await page.getByRole('button', { name: 'Logout' }).click();
    }

    const dialog = page.getByRole('dialog', { name: 'Confirm logout' });
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Stay signed in' }).click();
    await expect(dialog).toBeHidden();
    await page.waitForTimeout(200);

    if (testInfo.project.name === 'phone') {
        await page.getByRole('button', { name: 'More' }).click();
        await page.getByRole('button', { name: 'Logout' }).click();
    } else {
        await page.locator('header').getByRole('button').last().click();
        await page.getByRole('button', { name: 'Logout' }).click();
    }
    await dialog.getByRole('button', { name: 'Log out', exact: true }).click();
    await expect(page).toHaveURL(/\/login/);
});

test.describe('boundary widths', () => {
    for (const width of [320, 360, 375, 390, 412, 480, 600, 768, 820, 1024, 1280, 1440, 1920]) {
        test(`dashboard has no overflow at ${width}px`, async ({ page }) => {
            await page.setViewportSize({ width, height: width < 640 ? 720 : 900 });
            await login(page, 'admin');
            await expectNoPageOverflow(page);
            if (width >= 1024) await expectDesktopShellContained(page);
        });
    }
});

test('desktop shell remains contained at short viewport heights', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop');

    for (const viewport of [{ width: 1024, height: 520 }, { width: 1280, height: 640 }, { width: 1440, height: 700 }]) {
        await page.setViewportSize(viewport);
        await login(page, 'admin');
        const metrics = await expectDesktopShellContained(page);

        expect(metrics.sidebarNavigationScrollHeight).toBeGreaterThanOrEqual(metrics.sidebarNavigationClientHeight);
    }
});

test('dashboard analytics are role-aware and custom ranges remain responsive', async ({ page }, testInfo) => {
    await login(page, 'admin');
    await expect(page.getByRole('heading', { name: 'Needs attention' })).toBeVisible();
    await expect(page.getByRole('link', { name: /Full reports/i }).first()).toBeVisible();
    await page.getByRole('button', { name: 'Custom' }).click();
    await expect(page.getByRole('button', { name: 'Apply range' })).toBeVisible();
    const customRange = page.getByRole('form', { name: 'Custom analytics date range' });
    await customRange.getByRole('textbox', { name: 'From', exact: true }).fill('2026-08-01');
    await customRange.getByRole('textbox', { name: 'To', exact: true }).fill('2026-08-13');
    await page.getByRole('button', { name: 'Apply range' }).click();
    await expect(page).toHaveURL(/period=custom/);
    await expect(page.locator('canvas[data-dashboard-chart="primary"]')).toBeVisible();
    await expectNoPageOverflow(page);

    await login(page, 'dentist');
    await expect(page.getByText('Assigned appointments', { exact: true })).toBeVisible();
    await expect(page.getByText('Collected revenue', { exact: true })).toHaveCount(0);
    await expect(page.getByRole('link', { name: /Create invoice/i })).toHaveCount(0);
    await expectNoPageOverflow(page);

    if (testInfo.project.name === 'phone') {
        const attention = page.getByRole('heading', { name: 'Workload signals' });
        const chart = page.getByRole('heading', { name: 'Appointment volume' });
        expect((await attention.boundingBox()).y).toBeLessThan((await chart.boundingBox()).y);
    }
});

test('clinic reports support turbo tabs, custom ranges, charts, print, and downloads', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('/reports');
    await expect(page.getByRole('heading', { name: 'Clinic performance' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Financial', exact: true }).click();
    await expect(page).toHaveURL(/tab=financial/);
    await expect(page.getByRole('heading', { name: 'Financial', exact: true })).toBeVisible();
    await expect(page.locator('canvas[data-analytics-chart]').first()).toBeVisible();
    await expect(page.locator('[id^="chart-summary-"]').first()).toHaveText(/.+/);
    await expect(page.locator('[data-report-insights]')).toBeVisible();
    expect(await page.evaluate(() => {
        const insights = document.querySelector('[data-report-insights]');
        const visuals = document.querySelector('[data-report-visuals]');
        return Boolean(insights.compareDocumentPosition(visuals) & Node.DOCUMENT_POSITION_FOLLOWING);
    })).toBeTruthy();

    for (const tab of ['appointments', 'patients-services', 'dentists', 'overview', 'financial']) {
        await page.getByRole('navigation', { name: 'Report sections' }).getByRole('link', { name: tab === 'patients-services' ? 'Patients & Services' : new RegExp(`^${tab}$`, 'i') }).click();
        await expect(page.locator('[data-report-visuals]')).toBeVisible();
        await expect.poll(() => page.evaluate(() => window.__analyticsChartCount)).toBe(await page.locator('canvas[data-analytics-chart]').count());
    }
    await page.getByRole('link', { name: 'Dentists', exact: true }).click();
    await expect(page.locator('canvas[data-analytics-chart]')).toHaveCount(1);
    await expect(page.getByRole('heading', { name: 'Dentist workload and clinical activity' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Completed treatment sessions by dentist' })).toHaveCount(0);
    const chartHeights = await page.locator('.report-chart-shell').evaluateAll((shells) => shells.map((shell) => shell.getBoundingClientRect().height));
    expect(chartHeights.every((height) => height >= 180 && height <= 360)).toBeTruthy();

    await page.getByRole('link', { name: 'Financial', exact: true }).click();

    await page.getByRole('button', { name: 'Custom' }).click();
    await expect(page).toHaveURL(/period=custom/);
    await page.getByLabel('From', { exact: true }).fill('2026-08-01');
    await page.getByLabel('To', { exact: true }).fill('2026-08-13');
    await page.getByRole('button', { name: 'Apply range' }).click();
    await expect(page).toHaveURL(/from=2026-08-01.*to=2026-08-13/);

    await page.evaluate(() => { window.print = () => { window.reportPrintCalled = true; }; });
    await page.getByRole('button', { name: 'Print section' }).click();
    await expect.poll(() => page.evaluate(() => window.reportPrintCalled)).toBeTruthy();

    const pdf = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Full PDF' }).click();
    expect((await pdf).suggestedFilename()).toMatch(/^clinic-performance-.*\.pdf$/);
    await expectNoPageOverflow(page);
});

test.describe('report boundary widths', () => {
    for (const width of [320, 360, 390, 412, 768, 820, 1024, 1440, 1920]) {
        test(`reports have no overflow at ${width}px`, async ({ page }) => {
            await page.setViewportSize({ width, height: width < 640 ? 720 : 900 });
            await login(page, 'admin');
            await page.goto('/reports?tab=patients-services&period=this_month');
            await expect(page.getByRole('heading', { name: 'Patients & Services', exact: true })).toBeVisible();
            await expectNoPageOverflow(page);
            if (width >= 1024) await expectDesktopShellContained(page);
        });
    }
});
