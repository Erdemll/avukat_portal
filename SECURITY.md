# Security overview

## Private documents

Legal documents use a private filesystem disk outside the public web root. Downloads are authorized by document and event policies and use attachment responses. Uploads accept only approved MIME types and extensions.

## Authorization and audit

Employees see their own events, lawyers see assigned events, and managers administer the system. Audit logs are append-only through the UI; no audit update or delete endpoint is exposed.

## Secrets and production settings

Credentials, archive passwords, mail configuration and API keys must be environment-managed. They must not be committed, displayed in the UI, written to audit data, or copied into logs. Production uses `APP_DEBUG=false` and HTTPS-only session cookies.

## Backup handling

Backups contain sensitive data. Encrypt them with an environment-provided password, keep an offsite copy, and rehearse the restore procedure outside production.

## Incident response

1. Restrict access or enable maintenance mode when appropriate.
2. Preserve logs and audit records without editing them.
3. Rotate suspected secrets and invalidate affected sessions.
4. Assess document exposure and notify the responsible legal/security owner.
5. Record remediation and follow-up actions.

## Reporting a vulnerability

Send reports to the organisation's designated security contact: `security-contact@example.invalid`. Replace this placeholder before public release and never include live secrets.
