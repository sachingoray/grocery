"""Deploy this working copy to the live cloud server over SFTP, then verify over HTTP.

Server layout (read this once, it explains every past deploy headache):
  The public URL  https://mehedihasan.au/kent/cpro306/g4/  is aliased onto the
  SFTP *home root*, NOT onto the kent/cpro306/g4 folder of the same name. Two
  copies of the app live on the account:
      "."                -> what the public URL actually serves
      "kent/cpro306/g4"  -> the course-folder copy, kept in sync on purpose
  Deploying only the course folder leaves the live site untouched.

File mapping used by the uploader:
    public/index.php  ->  <root>/index.php      (public/ is the docroot)
    public/admin/x    ->  <root>/admin/x
    includes/x        ->  <root>/includes/x
    composer.json|lock, schema.sql -> <root>/

Only files whose content actually differs are uploaded, each via a .tmp file +
rename so the live site never serves a half-written PHP file. Server-side
config (stripe_keys.local.php, ai_keys.local.php) and the live SQLite database
are never overwritten.

Credentials live in deploy/deploy.env (git-ignored) - see deploy/README.md.

Usage:
    py deploy/deploy_live.py                    # deploy both targets, then verify
    py deploy/deploy_live.py --target home      # only the live docroot
    py deploy/deploy_live.py --target course    # only kent/cpro306/g4
    py deploy/deploy_live.py --dry-run          # show what would change
    py deploy/deploy_live.py --verify-only      # probe the live site, change nothing
"""
import argparse
import hashlib
import os
import posixpath
import re
import sys
import urllib.error
import urllib.request

import paramiko

HERE = os.path.dirname(os.path.abspath(__file__))
REPO_ROOT = os.path.dirname(HERE)
REPORT = os.path.join(HERE, 'last_deploy.txt')

SKIP_DIRS = {'.git', '.clinerules', '.vscode', 'node_modules', 'tests', 'deploy'}
SKIP_FILES = {'.gitignore', 'cloud_deploy_g4.zip', 'do_push.bat', 'sess_push.log',
              'composer.phar'}
PROTECTED = {
    'includes/stripe_keys.local.php',
    'includes/ai_keys.local.php',
    'includes/data/maxi_fine_foods.sqlite',
}
DEPLOY_PREFIXES = ('includes/', 'public/')
DEPLOY_ROOT_FILES = ('composer.json', 'composer.lock', 'schema.sql')
VERIFY_PATHS = [
    '/',
    '/index.php',
    '/cart.php',
    '/checkout.php',
    '/login.php',
    '/register.php',
    '/product.php?id=1',
    '/my_orders.php',
    '/admin/dashboard.php',
    '/delivery/my_deliveries.php',
    '/api/chat.php',
    '/stripe_success.php',
    '/download_receipt.php?id=1',
    '/stripe_cancel.php',
    '/terms.php',
]

_lines = []


def say(*parts):
    line = ' '.join(str(p) for p in parts)
    _lines.append(line)
    with open(REPORT, 'w', encoding='utf-8') as fh:
        fh.write('\n'.join(_lines) + '\n')
    print(line)


def load_config():
    """Read deploy/deploy.env (KEY=VALUE). Environment variables win."""
    cfg = {}
    path = os.path.join(HERE, 'deploy.env')
    if os.path.isfile(path):
        with open(path, encoding='utf-8') as fh:
            for raw in fh:
                raw = raw.strip()
                if not raw or raw.startswith('#') or '=' not in raw:
                    continue
                key, value = raw.split('=', 1)
                cfg[key.strip()] = value.strip().strip('"').strip("'")
    for key in list(cfg):
        cfg[key] = os.environ.get(key, cfg[key])
    return cfg


def md5_of(path):
    digest = hashlib.md5()
    with open(path, 'rb') as fh:
        for chunk in iter(lambda: fh.read(65536), b''):
            digest.update(chunk)
    return digest.hexdigest()


def remote_key(rel):
    return rel[len('public/'):] if rel.startswith('public/') else rel


def deploy_set():
    """[(remote_key, local_relative_path, md5)] for every file we manage."""
    items = []
    for base, dirs, files in os.walk(REPO_ROOT):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in files:
            full = os.path.join(base, name)
            rel = os.path.relpath(full, REPO_ROOT).replace('\\', '/')
            if name in SKIP_FILES or name.startswith('_'):
                continue
            if rel.endswith(('.log', '.zip', '.bat')):
                continue
            key = remote_key(rel)
            if key in PROTECTED:
                continue
            if not (rel.startswith(DEPLOY_PREFIXES) or rel in DEPLOY_ROOT_FILES):
                continue
            if key.startswith('vendor/stripe/stripe-php/.github/'):
                continue  # upstream CI metadata, never needed on the host
            items.append((key, rel, md5_of(full)))
    return sorted(items)


def ensure_dir(sftp, remote_dir):
    cur = ''
    for part in remote_dir.strip('/').split('/'):
        cur = cur + '/' + part if cur else part
        try:
            sftp.stat(cur)
        except IOError:
            sftp.mkdir(cur)
            say('  mkdir', cur)


def vendor_check(sftp, remote_root):
    """Confirm every local vendor file exists on the server with the same size."""
    missing, size_diff, count = [], [], 0
    for base, dirs, files in os.walk(os.path.join(REPO_ROOT, 'vendor')):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in files:
            full = os.path.join(base, name)
            rel = os.path.relpath(full, REPO_ROOT).replace('\\', '/')
            if 'vendor/stripe/stripe-php/.github/' in rel.replace('\\', '/'):
                continue  # same set deploy_set() skips - never uploaded on purpose
            count += 1
            try:
                remote_size = sftp.stat(remote_root + '/' + rel).st_size
            except IOError:
                missing.append(rel)
                continue
            if remote_size != os.path.getsize(full):
                size_diff.append(rel)
    say('vendor check: %d local files, missing=%d, size mismatch=%d'
        % (count, len(missing), len(size_diff)))
    for rel in missing + size_diff:
        say('  !', rel)
    return not missing and not size_diff


def push_target(cfg, items, remote_root, dry_run=False):
    """Upload the differing files into one remote root. Returns True on success."""
    say('')
    say('=== target: %s ===' % remote_root)
    transport = paramiko.Transport((cfg['MFF_SFTP_HOST'], int(cfg['MFF_SFTP_PORT'])))
    transport.banner_timeout = 20
    transport.connect(username=cfg['MFF_SFTP_USER'], password=cfg['MFF_SFTP_PASSWORD'])
    try:
        sftp = paramiko.SFTPClient.from_transport(transport)
        sftp.get_channel().settimeout(60)
        say('connected; home =', sftp.normalize('.'))

        uploaded, skipped, failures = [], [], []
        for key, rel, local_md5 in items:
            remote_path = remote_root + '/' + key
            try:
                with sftp.open(remote_path, 'rb') as fh:
                    remote_md5 = hashlib.md5(fh.read()).hexdigest()
            except IOError:
                remote_md5 = None

            if remote_md5 == local_md5:
                skipped.append(key)
                continue
            if dry_run:
                say('  would upload', key)
                continue

            ensure_dir(sftp, posixpath.dirname(remote_path))
            tmp_path = remote_path + '.upload_tmp'
            sftp.put(os.path.join(REPO_ROOT, rel), tmp_path)
            try:
                sftp.posix_rename(tmp_path, remote_path)
            except (IOError, AttributeError, OSError):
                sftp.rename(tmp_path, remote_path)

            with sftp.open(remote_path, 'rb') as fh:
                new_md5 = hashlib.md5(fh.read()).hexdigest()
            status = 'OK' if new_md5 == local_md5 else 'MISMATCH'
            if status != 'OK':
                failures.append(key)
            uploaded.append(key)
            say('%-7s %s' % (status, key))

        say('')
        say('uploaded: %d, already up to date: %d, verify failures: %d %s'
            % (len(uploaded), len(skipped), len(failures), failures))
        vendor_ok = vendor_check(sftp, remote_root)
        sftp.close()
        return not failures and vendor_ok
    finally:
        transport.close()


def verify(cfg):
    """Probe the public URL and report status, build tag and PHP error markers."""
    base = cfg['MFF_BASE_URL'].rstrip('/')
    say('')
    say('=== live verification: %s ===' % base)
    builds, ok = set(), True
    for path in VERIFY_PATHS:
        url = base + path
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (deploy)'})
            with urllib.request.urlopen(req, timeout=20) as res:
                body = res.read().decode('utf-8', 'replace')
                code = res.getcode()
            hit = re.search(r'name="mff-build"\s+content="([^"]+)"', body)
            build = hit.group(1) if hit else '-'
            builds.add(build)
            php_error = 'yes' if re.search(r'Fatal error|Parse error|Warning: |Uncaught', body) else 'no'
            if php_error == 'yes':
                ok = False
            say('%-34s HTTP %s  bytes=%-7d build=%-34s php_error=%s'
                % (path, code, len(body), build, php_error))
        except urllib.error.HTTPError as exc:
            say('%-34s HTTP %s  %s' % (path, exc.code, exc.reason))
        except Exception as exc:  # noqa: BLE001 - report whatever the network did
            say('%-34s ERROR %s' % (path, exc))
            ok = False
    say('')
    say('build tag(s) served: %s' % sorted(builds))
    expected = cfg.get('MFF_EXPECTED_BUILD', '').strip()
    if expected:
        matched = builds == {expected}
        say('expected build: %s -> %s' % (expected, 'MATCH' if matched else 'MISMATCH'))
        ok = ok and matched
    return ok


def main():
    parser = argparse.ArgumentParser(description='Deploy to the live cloud server.')
    parser.add_argument('--target', choices=['home', 'course', 'both'], default='both')
    parser.add_argument('--dry-run', action='store_true', help='list changes, upload nothing')
    parser.add_argument('--verify-only', action='store_true', help='only probe the live site')
    args = parser.parse_args()

    cfg = load_config()
    missing = [k for k in ('MFF_SFTP_HOST', 'MFF_SFTP_PORT', 'MFF_SFTP_USER',
                           'MFF_SFTP_PASSWORD', 'MFF_BASE_URL') if not cfg.get(k)]
    if missing:
        say('missing config: %s' % ', '.join(missing))
        say('create deploy/deploy.env - see deploy/README.md')
        return 2

    ok = True
    if not args.verify_only:
        items = deploy_set()
        say('deploy set: %d files' % len(items))
        roots = {'home': cfg.get('MFF_REMOTE_HOME', '.'),
                 'course': cfg.get('MFF_REMOTE_COURSE', 'kent/cpro306/g4')}
        chosen = ['home', 'course'] if args.target == 'both' else [args.target]
        for name in chosen:
            ok = push_target(cfg, items, roots[name], dry_run=args.dry_run) and ok
    if not args.dry_run:
        ok = verify(cfg) and ok

    say('')
    say('RESULT: %s' % ('SUCCESS' if ok else 'PROBLEMS FOUND'))
    return 0 if ok else 1


if __name__ == '__main__':
    sys.exit(main())


