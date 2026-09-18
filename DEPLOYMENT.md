# Production deployment checklist

## Before deployment

- [ ] Required PHP extensions, including MySQL and ZipArchive, are installed.
- [ ] Run `composer install --no-dev --optimize-autoloader`.
- [ ] Create a production `.env` outside source control; set `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, and HTTPS `APP_URL`.
- [ ] MySQL uses `utf8mb4` and `utf8mb4_unicode_ci`; its user has only required privileges, is not remote root, and has a strong password.
- [ ] DB, Resend and backup secrets are environment-managed. Run `php artisan migrate --force`, never `migrate:fresh`, on production.
- [ ] Do not run `UserSeeder` in production. It skips example users; production users are manager-created.
- [ ] Set `MAIL_MAILER=resend`; verify the sender domain and configure `MAIL_FROM_ADDRESS` in the environment.
- [ ] Configure `QUEUE_CONNECTION`/failed jobs, a worker, and the scheduler cron: `* * * * * php /path/to/project/artisan schedule:run`.
- [ ] Set a backup disk and non-empty `BACKUP_ARCHIVE_PASSWORD`; retain an offsite S3-compatible copy.
- [ ] Keep documents in `storage/app/private/legal-documents`, outside the web root. Grant only required write access; never use `777`.
- [ ] Use valid TLS, HTTP-to-HTTPS redirect, and `SESSION_SECURE_COOKIE=true`.
- [ ] Run `npm run build`, `php artisan optimize`, then `php artisan queue:restart`.

## Queue worker

Keep a worker alive through Supervisor or systemd; use placeholders rather than real server paths/users:

```ini
[program:avukat-portali-worker]
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
```

Use `php artisan queue:failed` and `php artisan queue:retry <id>` for failed jobs.

## Resend smoke test

Create/select a test user, request a reset, verify Resend delivery, confirm the link is HTTPS and uses the expected host, complete the reset, and verify the old password no longer works. Do not automate this against the real provider.

## Backup and disaster recovery

Scheduled backups include the database and private documents. After deployment verify with `php artisan backup:run` and `php artisan backup:list`. Local backups alone do not survive host loss; use approved offsite storage.

### Disaster Recovery / Restore

1. Enable maintenance mode and preserve the current DB and document storage.
2. Create a separate restore database or first make a verified target backup.
3. Verify the archive opens and contains the SQL dump and private documents.
4. Restore SQL to the intended DB and documents only to the private directory.
5. Check least-privilege ownership/permissions.
6. Clear/rebuild config cache, disable maintenance mode, then smoke-test login, authorization, documents, queue and password reset.

Never test a restore by overwriting production.

## Final smoke tests

- [ ] Password reset and HTTPS email link work.
- [ ] Authorized document upload/download works; public document URLs do not.
- [ ] Backup creation and archive inspection work.
- [ ] Restore procedure has been reviewed.
- [ ] Use an appropriate production log channel/level, e.g. `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`.
- [ ] Audit retention is approved as a legal/business decision; the app intentionally has no automatic audit deletion.
