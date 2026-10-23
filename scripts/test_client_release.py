"""Regression checks for exports pinned to a production commit."""
import importlib.util
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch


def load(name, filename):
    spec = importlib.util.spec_from_file_location(name, Path(__file__).with_name(filename))
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


exporter = load('release_exporter', 'export-client-release.py')
checker = load('release_checker', 'check-client-release.py')


class ClientReleaseTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / 'source'
        self.root.mkdir()
        self.run_git('init', '-q')
        self.run_git('config', 'user.name', 'Release Test')
        self.run_git('config', 'user.email', 'release@example.test')
        for name in ('artisan', 'composer.lock', 'package-lock.json', 'public/index.php', 'bootstrap/app.php'):
            self.write(name, 'source\n')
        self.write('scripts/client-release/README.md', 'Release instructions\n')
        self.write('scripts/client-release/.env.example', 'APP_ENV=production\nAPP_DEBUG=false\n')
        self.write('scripts/client-release/DatabaseSeeder.php', '<?php // release seeder\n')
        self.write('docs/manualbook/Buku-Panduan-Ruang-BK.docx', 'old binary\x00data')
        self.commit()
        self.export_patch = patch.object(exporter, 'ROOT', self.root)
        self.check_patch = patch.object(checker.exporter, 'ROOT', self.root)
        self.export_patch.start()
        self.check_patch.start()
        self.addCleanup(self.export_patch.stop)
        self.addCleanup(self.check_patch.stop)

    def run_git(self, *args):
        return subprocess.check_output(['git', '-C', str(self.root), *args], stderr=subprocess.DEVNULL).decode().strip()

    def write(self, name, content):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding='utf-8')

    def commit(self):
        self.run_git('add', '.')
        self.run_git('commit', '-qm', 'Fixture source')
        self.run_git('update-ref', 'refs/remotes/origin/main', 'HEAD')

    def release(self, name='release'):
        output = Path(self.temp.name) / name
        exporter.export('1.0.0', output)
        return output

    def test_export_ignores_working_template_changes(self):
        self.write('scripts/client-release/README.md', 'Unreleased instructions\n')
        self.write('scripts/client-release/.env.example', 'APP_ENV=local\nAPP_DEBUG=true\n')
        self.write('scripts/client-release/DatabaseSeeder.php', '<?php // unreleased seeder\n')
        self.write('docs/manualbook/Buku-Panduan-Ruang-BK.docx', 'new binary\x00data')
        output = self.release()
        self.assertEqual((output / 'README.md').read_text(), 'Release instructions\n')
        self.assertIn('APP_ENV=production', (output / '.env.example').read_text())
        self.assertEqual((output / 'database/seeders/DatabaseSeeder.php').read_text(), '<?php // release seeder\n')
        self.assertEqual((output / 'docs/manualbook/Buku-Panduan-Ruang-BK.docx').read_bytes(), b'old binary\x00data')
        self.assertFalse(exporter.allowed('docs/manualbook/other.docx'))
        checker.validate(output)

    def test_old_release_validates_after_production_advances(self):
        output = self.release()
        self.write('scripts/client-release/README.md', 'New instructions\n')
        self.write('artisan', 'new source\n')
        self.commit()
        checker.validate(output)

    def test_export_requires_committed_word_manual(self):
        self.run_git('rm', 'docs/manualbook/Buku-Panduan-Ruang-BK.docx')
        self.commit()
        output = Path(self.temp.name) / 'release'
        with self.assertRaisesRegex(ValueError, 'Production source is incomplete'):
            exporter.export('1.0.0', output)
        self.assertFalse(output.exists())

    def test_checker_requires_word_even_if_manifest_omits_it(self):
        output = self.release()
        word = 'docs/manualbook/Buku-Panduan-Ruang-BK.docx'
        (output / word).unlink()
        manifest_path = output / 'RELEASE.json'
        manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
        del manifest['files'][word]
        manifest_path.write_text(json.dumps(manifest), encoding='utf-8')
        with self.assertRaisesRegex(ValueError, 'Production source is incomplete'):
            checker.validate(output)

    def test_changed_and_missing_release_files_fail(self):
        output = self.release()
        readme = output / 'README.md'
        readme.write_text('Tampered\n')
        with self.assertRaisesRegex(ValueError, 'Changed release file'):
            checker.validate(output)
        readme.unlink()
        with self.assertRaisesRegex(ValueError, 'Unexpected or missing release files'):
            checker.validate(output)


if __name__ == '__main__':
    unittest.main()
