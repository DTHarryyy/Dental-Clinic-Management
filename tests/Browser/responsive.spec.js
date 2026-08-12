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

for (const [role, paths] of Object.entries(rolePages)) {
    test(`${role} shell and pages remain contained`, async ({ page }, testInfo) => {
        await login(page, role);
        for (const path of paths) {
            await page.goto(path);
            await expect(page.locator('main')).toBeVisible();
            await expectNoPageOverflow(page);
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
        });
    }
});
