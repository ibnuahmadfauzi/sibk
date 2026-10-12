"""Export committed production source without internal Git history or local data."""
import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import subprocess

ROOT = Path(__file__).resolve().parents[1]
ROOT_FILES = {
    'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'vite.config.js', '.gitignore', '.gitattributes', '.editorconfig', '.npmrc',
    'LICENSE', 'LICENSE.md', 'NOTICE',
}
TEMPLATES = {'README.md': 'README.md', '.env.example': '.env.example',
             'database/seeders/DatabaseSeeder.php': 'DatabaseSeeder.php'}


def git(*args):
    return subprocess.check_output(['git', '-C', str(ROOT), *args])


def allowed(name):
    path = PurePosixPath(name)
    if path.is_absolute() or '..' in path.parts or '\\' in name:
        raise ValueError('Unsafe export path')
    if name in ROOT_FILES:
        return True
    if any(part.startswith('.') for part in path.parts):
        return name == 'public/.htaccess' or (path.name == '.gitignore' and path.parts[0] in {'storage', 'bootstrap'})
    if path.parts[0] == 'bootstrap':
        return name in {'bootstrap/app.php', 'bootstrap/providers.php'}
    if name in {'database/seeders/RoleSeeder.php', 'database/seeders/ReferenceSeeder.php'}:
        return True
    if name.startswith('database/migrations/'):
        return path.suffix == '.php'
    if path.parts[0] in {'app', 'config', 'routes'}:
        return path.suffix == '.php'
    if path.parts[0] in {'resources', 'public'}:
        if len(path.parts) > 1 and path.parts[1] in {'build', 'storage', 'uploads'}:
            return False
        return path.suffix.lower() in {'.php', '.js', '.css', '.scss', '.json', '.png', '.jpg', '.jpeg', '.svg', '.ico', '.woff', '.woff2', '.ttf'} or name == 'public/robots.txt'
    return False


def export(version, output):
    if not re.fullmatch(r'\d+\.\d+\.\d+(?:-[a-z0-9.-]+)?', version):
        raise ValueError('Version must be semantic, e.g. 1.0.0')
    if output.exists():
        raise ValueError('Output already exists; choose a new directory')
    commit = git('rev-parse', '--verify', 'origin/main^{commit}').decode().strip()
    files = {}
    for entry in git('ls-tree', '-rz', commit).split(b'\0'):
        if not entry:
            continue
        header, raw_name = entry.split(b'\t', 1)
        mode, kind, oid = header.decode().split()
        name = raw_name.decode('utf-8')
        if not allowed(name) or name in TEMPLATES:
            continue
        if mode not in {'100644', '100755'} or kind != 'blob':
            raise ValueError('Symlink/submodule is not allowed in release: ' + name)
        files[name] = git('cat-file', 'blob', oid)
    for destination, template in TEMPLATES.items():
        files[destination] = (ROOT / 'scripts/client-release' / template).read_bytes()
    required = {'artisan', 'composer.lock', 'package-lock.json', 'public/index.php', 'bootstrap/app.php'}
    if not required <= files.keys():
        raise ValueError('Production source is incomplete')
    output.mkdir(parents=True, exist_ok=False)
    for name, content in files.items():
        target = output / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(content)
    manifest = {'version': version, 'source_commit': commit,
                'files': {name: hashlib.sha256(content).hexdigest() for name, content in sorted(files.items())}}
    (output / 'RELEASE.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
    print(f'Exported {len(files)} files from {commit[:12]} to {output}')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--version', required=True)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    export(args.version, args.output.resolve())
