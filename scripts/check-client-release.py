"""Validate a clean source export before initializing its independent repository."""
import hashlib
import importlib.util
import json
from pathlib import Path
import sys

spec = importlib.util.spec_from_file_location('exporter', Path(__file__).with_name('export-client-release.py'))
exporter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(exporter)


def validate(root):
    manifest = json.loads((root / 'RELEASE.json').read_text(encoding='utf-8'))
    exporter.git('merge-base', '--is-ancestor', manifest['source_commit'], 'origin/main')
    actual = set()
    for path in root.rglob('*'):
        if path.is_symlink():
            raise ValueError('Symlinks are not permitted')
        if path.is_file():
            actual.add(path.relative_to(root).as_posix())
    if actual != set(manifest['files']) | {'RELEASE.json'}:
        raise ValueError('Unexpected or missing release files')
    for name, expected in manifest['files'].items():
        if not exporter.allowed(name) and name not in exporter.TEMPLATES:
            raise ValueError('Disallowed release file: ' + name)
        if hashlib.sha256((root / name).read_bytes()).hexdigest() != expected:
            raise ValueError('Changed release file: ' + name)
        if name in exporter.TEMPLATES:
            source = (exporter.ROOT / 'scripts/client-release' / exporter.TEMPLATES[name]).read_bytes()
        else:
            source = exporter.git('show', manifest['source_commit'] + ':' + name)
        if (root / name).read_bytes() != source:
            raise ValueError('File differs from committed source/template: ' + name)
    env = (root / '.env.example').read_text()
    if 'APP_DEBUG=false' not in env or 'APP_ENV=production' not in env:
        raise ValueError('Unsafe environment template')
    print(f"Validated {len(manifest['files'])} files; version {manifest['version']}")


if __name__ == '__main__':
    validate(Path(sys.argv[1]).resolve())
