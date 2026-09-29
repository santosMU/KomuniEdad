// Record tested source without reading local secrets or generated dependencies.
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';

const git = (...args) => execFileSync('git', args, { encoding: 'utf8' }).trim();
const paths = git('ls-files', '-z').split('\0').filter(p =>
  /^(app\/|bootstrap\/app\.php$|config\/|api\/|routes\/|resources\/|public\/community\.|supabase\/|tests\/)/.test(p)
  || ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', '.env.production.example'].includes(p)
);
const files = Object.fromEntries(paths.sort().map(p => [p, createHash('sha256').update(fs.readFileSync(p)).digest('hex')]));
fs.mkdirSync('docs/phase4/evidence/automated', { recursive: true });
fs.writeFileSync('docs/phase4/evidence/automated/tested-source-manifest.json', JSON.stringify({
  recordedAt: new Date().toISOString(),
  branch: git('branch', '--show-current'),
  baseCommit: git('rev-parse', 'HEAD'),
  note: 'Hashes describe the working source after final local tests. Base commit alone does not contain uncommitted changes. Documentation and ignored secrets are excluded.',
  files,
}, null, 2) + '\n');
console.log(`Recorded ${paths.length} tracked source file hashes.`);
