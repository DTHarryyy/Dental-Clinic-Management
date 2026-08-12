import { defineConfig, devices } from '@playwright/test';

const database = `${process.cwd().replaceAll('\\', '/')}/storage/e2e.sqlite`;

export default defineConfig({
    testDir: './tests/Browser',
    globalSetup: './tests/Browser/global-setup.js',
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: 'list',
    use: {
        baseURL: 'http://127.0.0.1:8010',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8010',
        url: 'http://127.0.0.1:8010/login',
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
            ...process.env,
            APP_ENV: 'e2e',
            APP_URL: 'http://127.0.0.1:8010',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database,
            CACHE_STORE: 'array',
            SESSION_DRIVER: 'file',
            QUEUE_CONNECTION: 'sync',
            E2E_AUTH_TOKEN: 'responsive-local-only',
        },
    },
    projects: [
        { name: 'phone', use: { ...devices['Desktop Chrome'], viewport: { width: 390, height: 844 } } },
        { name: 'tablet', use: { ...devices['Desktop Chrome'], viewport: { width: 820, height: 1180 } } },
        { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 1000 } } },
    ],
});
