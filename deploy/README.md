# Deploying to the live server

`deploy/deploy_live.py` uploads the app over SFTP and then proves the change
landed by probing the public URL.

## Server layout — the gotcha that caused past failures

```
SFTP home root (".")            = https://mehedihasan.au/kent/cpro306/g4/   <- LIVE
kent/cpro306/g4/                = course-folder copy (kept in sync, NOT the live URL)
```

The URL path `kent/cpro306/g4/` is **aliased onto the SFTP home root**, not onto
the folder of the same name. Deploying only to `kent/cpro306/g4` uploads files
successfully but changes nothing the public sees. Both targets are therefore
pushed by default (`--target both`).

File mapping:

| local | remote |
| --- | --- |
| `public/*.php`, `public/assets/*`, `public/admin/*` | `<root>/*.php`, `<root>/assets/*`, `<root>/admin/*` |
| `includes/*.php` | `<root>/includes/*.php` |
| `composer.json`, `composer.lock`, `schema.sql` | `<root>/` |
| `vendor/**` | verified by size only (never re-uploaded) |

Never uploaded (protected): `includes/stripe_keys.local.php`,
`includes/ai_keys.local.php`, `includes/data/maxi_fine_foods.sqlite`
(the live database).

## Setup

1. `pip install paramiko` (Python 3).
2. Copy `deploy/deploy.env.example` to `deploy/deploy.env` and fill in the
   credentials. That file is git-ignored — **never** commit it.

## Usage

```bat
py deploy\deploy_live.py --dry-run        :: show what would change, upload nothing
py deploy\deploy_live.py                  :: deploy both targets + verify over HTTP
py deploy\deploy_live.py --target home    :: only the live docroot
py deploy\deploy_live.py --verify-only    :: probe the site, change nothing
```

Every run writes `deploy/last_deploy.txt` (git-ignored) and mirrors it to stdout.

## How a deploy is made safe

* Only files whose MD5 differs from the server are uploaded.
* Each upload goes to `<file>.upload_tmp` and is then renamed, so a PHP file is
  never read by the web server while half-written.
* After uploading, every file is re-read from the server and its MD5 compared.
* `vendor/` is checked file-by-file for presence and size.
* The script ends by fetching 14 public URLs and asserting there is no
  `Fatal error` / `Parse error` / `Warning:` in the HTML.

## Verifying the right build is live

The build tag lives in `includes/header.php`:

```html
<meta name="mff-build" content="2026-09-28-auth-checkout-parse-fix">
```

Bump that string before every release, then read the `build=` column in
`deploy/last_deploy.txt`. If it still shows the previous tag after a deploy,
you deployed to the wrong target — see the layout section above.

To check by hand:

```bat
curl -s https://mehedihasan.au/kent/cpro306/g4/ | findstr mff-build
```
