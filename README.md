# Nested Forum Lab (PHP 8.2+, MySQL, PDO)

Libraries: **Bramus Router** (routing), **PDO** with real prepared statements (no Eloquent; SQL lives only in `app/Models`), **Mustache** (templates, `{{var}}` is HTML-escaped).

## Setup
```bash
composer install
mysql -uroot < database/schema.sql      # creates nested_forum + 7 seed comments (seed users cannot log in)
DB_USER=root DB_PASS= php -S 127.0.0.1:8080 -t public public/index.php
```
Env vars: `DB_HOST DB_PORT DB_NAME DB_USER DB_PASS`. Open http://127.0.0.1:8080/discussions/1, then Register + Log in to post.
Production: point all requests to `public/index.php` (rewrite rule); not needed locally.

## Layout
`public/index.php` front controller -> `config/routes.php` -> `app/Controllers` -> `app/Models` (PDO) -> `app/Views` (Mustache).

## Design notes
- `comments.parent_id` NULL = top-level. Composite FK `(discussion_id, parent_id)` -> `comments(discussion_id, id)` forces a parent into the same discussion.
- Depth is computed, not stored. `DiscussionController::flatten` groups by parent, walks iteratively with a visited set; indent capped at 8 levels, depth label stays real; max 5000 comments rendered.
- Cycles: `parent_id` is only set at INSERT (immutable) and points at an older row, so cycles cannot be created. Write depth limit: 50.
- Invalid IDs: non-numeric -> router 404; `0`/overflow -> 400; valid but missing -> 404.
- Auth: `password_hash`/`password_verify`; NULL hash never logs in; `session_regenerate_id` on login/logout; HttpOnly, SameSite=Lax, Secure over HTTPS.
- Replies: login checked server-side on every POST, `user_id` from session only, CSRF token on all POST forms, body 1-2000 chars, redirect after post.

## Tests
With server on :8080 and DB on the port in `DB_PORT` (default 3307 in the test): `DB_PORT=3306 php tests/smoke.php`. Covers guest POST, bad CSRF, wrong password, cross-discussion parent, missing/invalid ids, depth-5 reply, second top-level, XSS escaping, logout.

## Not done / limitations
No rate limiting or account recovery. Local avatar upload is not implemented.

## Optional extras
- **Collapse/expand:** `public/app.js` adds a "Hide replies / Show replies" button per comment with replies (`aria-controls`, `aria-expanded`, `hidden`). Without JS all replies stay visible.
- **Avatars:** default grey avatar for everyone. Gravatar is opt-in at `/settings` (off by default) because it shares an email hash and the viewer's IP with gravatar.com.
