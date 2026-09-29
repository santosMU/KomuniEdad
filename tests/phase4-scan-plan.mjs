// Generate a passive-only ZAP plan from an explicitly supplied target.
import fs from 'node:fs';
import path from 'node:path';
const target = process.env.PHASE4_TARGET_URL;
if (!target) throw new Error('Set PHASE4_TARGET_URL to an authorized KomuniEdad target.');
const u = new URL(target);
if (!['http:', 'https:'].includes(u.protocol) || u.username || u.password || u.search || u.hash) throw new Error('Expected a plain HTTP(S) target, without credentials or query.');
const local = ['localhost','127.0.0.1','[::1]'].includes(u.hostname);
if (!local && u.hostname !== 'komuni-edad.vercel.app') throw new Error('Target is outside the authorized scope.');
const label = local ? 'local' : 'hosted';
const reportDir = path.resolve('storage/phase4-private');
fs.mkdirSync(reportDir,{recursive:true});
// Same-host HTTPS is the expected production transport. No external links,
// forms, credentials, active scan, AJAX spider, or third-party resources.
if (!local) u.protocol = 'https:';
const base = u.origin;
const plan = {
  env:{contexts:[{name:'KomuniEdad',urls:[base],includePaths:[base.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+'/.*']}],parameters:{failOnError:true,failOnWarning:false}},
  jobs:[
    {type:'passiveScan-config',parameters:{scanOnlyInScope:true}},
    {type:'requestor',requests:['/login','/register','/health'].map(p=>({url:base+p,method:'GET',responseCode:200}))},
    {type:'passiveScan-wait',parameters:{maxDuration:2}},
    {type:'report',parameters:{template:'traditional-json',reportDir,reportFile:`zap-${label}-raw`,reportTitle:'KomuniEdad Phase 4 passive public-page scan',reportDescription:'Unauthenticated GET requests only. No active attacks or third-party scanning.',displayReport:false}},
  ],
};
fs.writeFileSync(path.join(reportDir,`zap-${label}-plan.yaml`),JSON.stringify(plan,null,2));
console.log(`Plan written for ${base}; passive GET /login, /register, /health only.`);
