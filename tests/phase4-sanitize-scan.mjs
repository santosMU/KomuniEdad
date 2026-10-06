import fs from 'node:fs';
fs.mkdirSync('docs/phase4/evidence/scanner',{recursive:true});
for (const label of ['hosted','local']) {
  const file=`storage/phase4-private/zap-${label}-raw.json`;
  if (!fs.existsSync(file)) continue;
  const raw=JSON.parse(fs.readFileSync(file,'utf8'));
  const sites=(raw.site??[]).map(site=>({host:site['@host'],port:site['@port'],alerts:(site.alerts??[]).map(a=>({id:a.pluginid,name:a.name??a.alert,risk:a.riskdesc,confidence:a.confidence,count:a.count,description:a.desc,solution:a.solution,reference:a.reference,instances:(a.instances??[]).map(i=>({uri:i.uri,method:i.method,param:i.param}))}))}));
  const out={tool:'ZAP',version:raw['@version'],generated:raw['@generated'],scope:'Unauthenticated passive inspection of GET /login, /register, /health only. No active scan or third-party resources.',sanitization:'Raw evidence strings, request/response headers, bodies, cookies and session values omitted. Raw report retained only in ignored storage/phase4-private.',sites};
  fs.writeFileSync(`docs/phase4/evidence/scanner/zap-${label}-sanitized.json`,JSON.stringify(out,null,2)+'\n');
  console.log(JSON.stringify({label,version:out.version,alerts:sites.flatMap(s=>s.alerts.map(a=>({id:a.id,name:a.name,risk:a.risk,count:a.count})))}));
}
