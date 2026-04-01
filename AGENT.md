# AGENT.md - AitiCore Flex (STRICT)

## Motto
AitiCore Flex - CI simplicity, Laravel security.

## Golden Rules
1. Secure by default: view escape ON, CSRF ON for web, DB binding only, hardened session cookie.
2. No magic overload: advanced features are opt-in.
3. Thin controllers: business logic in `app/Services`.
4. Public CLI surface is stable: only `php aiti ...`.
5. No breaking changes without changelog + migration guide + semver bump.
6. Never overwrite user-owned app structure during framework upgrade.

## Structure Contract
Keep these folders stable:
`app/`, `routes/`, `bootstrap/`, `public/`, `storage/`, `system/`, `tests/`, root `aiti`.

Ownership split:
- Framework core (updater allowed): `system/`, `bootstrap/`, `public/`, root tooling files.
- User-owned (updater forbidden): `app/`, `routes/`, `database/`.
- If a framework update needs user-owned changes, emit actionable guide steps only (no auto-overwrite).

## Upgrade Governance (STRICT)
1. SemVer is mandatory:
   - PATCH = no breaking.
   - MINOR = backward-compatible additions.
   - MAJOR = explicit breaking changes allowed.
2. Every release must include:
   - `CHANGELOG.md` update
   - `upgrade-guides/index.php` track update
   - per-track guide `upgrade-guides/vX-to-vY.md`
3. `upgrade:check` must remain read-only.
4. `upgrade:apply` must default to dry-run unless `--apply` is set.
5. Before overwriting a file, create timestamped backup `*.bak.YmdHis`.
6. If checksum mismatch on core file: skip by default and require `--force`.
7. For merge-style changes prefer marker/AST merge over blind full-file replace.
8. Deprecated behavior must survive at least one major cycle and show warning only in debug mode.

## Security Checklist
- XSS: escaped output by default.
- CSRF: enabled in `web` routes.
- SQL Injection: prepared statements/query binding only.
- Session: HttpOnly true, SameSite Lax default, Secure when HTTPS.
- Uploads: store in `storage/uploads`, random name, MIME/ext whitelist.
- Error handling: hide stack trace in production, log to `storage/logs`.

## CLI Contract
Must keep:
- `php aiti --version`
- `php aiti list`
- `php aiti serve` (`server` alias allowed)
- `php aiti route:list`
- `php aiti key:generate`
- `php aiti preset:bootstrap`
- `php aiti upgrade:check`
- `php aiti upgrade:apply`

## Definition of Done
- App runs.
- Security defaults active.
- CLI commands work.
- Tests pass.
- Docs updated if public behavior changes.
