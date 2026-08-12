import { closeSync, existsSync, openSync, rmSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';

export default async function globalSetup() {
    const database = path.resolve('storage/e2e.sqlite');
    if (existsSync(database)) rmSync(database);
    closeSync(openSync(database, 'w'));

    const result = spawnSync('php', ['artisan', 'migrate:fresh', '--force', '--seed', '--seeder=Database\\Seeders\\ResponsiveE2ESeeder'], {
        cwd: process.cwd(),
        encoding: 'utf8',
        env: {
            ...process.env,
            APP_ENV: 'e2e',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database.replaceAll('\\', '/'),
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
        },
    });

    if (result.status !== 0) {
        throw new Error(`Unable to prepare E2E database:\n${result.stdout}\n${result.stderr}`);
    }
}
