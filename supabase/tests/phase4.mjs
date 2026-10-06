import assert from 'node:assert/strict';

export async function phase4(db, ids, asUser, payload) {
  let count = 0;
  const check = async (id, name, run) => { await run(); count++; console.log(`PASS ${id}: ${name}`); };
  const denied = (sql, args=[]) => assert.rejects(db.query(sql,args), /Not authorized|permission denied|active senior|Enrollment not found|completed, attended/);
  const marker = "' OR '1'='1";
  await asUser(ids[3]);
  const activity = (await db.query('select save_activity($1) id',[{...payload,title:marker,description:marker,venue:marker,requirements:marker,capacity:2,is_free:false,fee:25}])).rows[0].id;
  await asUser(ids[0]);
  const enrollment = (await db.query('select * from enroll_in_activity($1)',[activity])).rows[0];
  await check('DB-01','SQLI-06/07/08 activity text stored literally',async()=>{
    const r=(await db.query('select title,description,venue,requirements from activities where activity_id=$1',[activity])).rows[0];
    for(const value of Object.values(r)) assert.equal(value,marker);
  });
  await check('DB-02','SQLI-04/05 profile text and role allowlist',async()=>{
    await db.query('select update_own_profile($1)',[{full_name:marker,address:marker,role:'admin'}]);
    assert.equal((await db.query('select full_name,role from profiles where user_id=$1',[ids[0]])).rows[0].full_name,marker);
    assert.equal((await db.query('select role from profiles where user_id=$1',[ids[0]])).rows[0].role,'senior');
    assert.equal((await db.query('select address from senior_profiles where user_id=$1',[ids[0]])).rows[0].address,marker);
  });
  await asUser(ids[1]);
  await check('DB-03','AUTHZ-17 enrollment RLS hides another senior',async()=>assert.equal((await db.query('select * from enrollments where enrollment_id=$1',[enrollment.enrollment_id])).rows.length,0));
  await check('DB-04','AUTHZ-18 cross-senior withdrawal denied',()=>denied('select withdraw_enrollment($1)',[enrollment.enrollment_id]));
  await check('DB-05','AUTHZ-19 cross-senior feedback denied',()=>denied('select submit_feedback($1,5,$2)',[enrollment.enrollment_id,marker]));
  await asUser(ids[4]);
  await check('DB-06','AUTHZ-11 cross-coordinator edit denied',()=>denied('select save_activity($1,$2)',[payload,activity]));
  await check('DB-07','AUTHZ-12 cross-coordinator roster denied',()=>denied('select * from participant_list($1)',[activity]));
  await check('DB-08','AUTHZ-13 cross-coordinator attendance denied',()=>denied('select record_attendance($1,true,$2)',[enrollment.enrollment_id,marker]));
  await check('DB-09','AUTHZ-14 cross-coordinator payment denied',()=>denied('select record_cash_payment($1,true,$2)',[enrollment.enrollment_id,marker]));
  await check('DB-10','AUTHZ-15 cross-coordinator enrollment denied',()=>denied('select manage_enrollment($1,$2,$3,$4)',[activity,ids[0],'cancelled',marker]));
  await check('DB-11','AUTHZ-09 coordinator category mutation denied',()=>denied('select save_category($1)',[{name:'Forged',is_active:true}]));
  await asUser(ids[3]);
  const notice=(await db.query('select save_announcement($1) id',[{activity_id:activity,title:marker,message:marker}])).rows[0].id;
  await check('DB-12','SQLI-09/10 announcement text literal',async()=>{
    const n=(await db.query('select title,message from announcements where announcement_id=$1',[notice])).rows[0]; assert.equal(n.title,marker); assert.equal(n.message,marker);
  });
  await asUser(ids[4]);
  await check('DB-13','AUTHZ-16 cannot retarget another coordinator announcement',()=>denied('select save_announcement($1,$2)',[{activity_id:activity,title:'Forged',message:'Forged'},notice]));
  await asUser(ids[3]);
  await check('DB-14','SQLI-12 enrollment reason literal and audited',async()=>{
    await db.query('select manage_enrollment($1,$2,$3,$4)',[activity,ids[1],'confirmed',marker]);
    await asUser(ids[5]);
    const r=await db.query("select details from audit_logs where action_type='enrollment.managed' and details->>'reason'=$1",[marker]); assert.equal(r.rows.length,1);
  });
  await db.exec('reset role');
  await db.query("update activities set start_at=now()-interval '2 hours',end_at=now()-interval '1 hour',cutoff_at=now()-interval '3 hours',status='ongoing' where activity_id=$1",[activity]);
  await asUser(ids[3]);
  await check('DB-15','FR-27 unpaid attendance gate',()=>assert.rejects(db.query('select record_attendance($1,true,$2)',[enrollment.enrollment_id,marker]),/Cash payment/));
  await db.query('select record_cash_payment($1,true,$2)',[enrollment.enrollment_id,'Synthetic cash received']);
  await check('DB-16','SQLI-13 attendance text literal',async()=>{
    await db.query('select record_attendance($1,true,$2)',[enrollment.enrollment_id,marker]);
    assert.equal((await db.query('select remarks from attendance where enrollment_id=$1',[enrollment.enrollment_id])).rows[0].remarks,marker);
  });
  await asUser(ids[5]);
  await check('DB-17','AUTHZ-25 administrator correction persists audit',async()=>{
    await db.query('select record_attendance($1,false,$2)',[enrollment.enrollment_id,'Synthetic correction']);
    assert.equal((await db.query("select count(*)::int n from audit_logs where actor_id=$1 and target_id=$2 and details->>'remarks'=$3",[ids[5],enrollment.enrollment_id,'Synthetic correction'])).rows[0].n,1);
    await db.query('select record_attendance($1,true,$2)',[enrollment.enrollment_id,'Synthetic correction confirmed']);
  });
  await asUser(ids[3]);
  await db.query('select save_activity($1,$2)',[{...payload,capacity:2,is_free:false,fee:25,status:'completed',start_at:new Date(Date.now()-7200000).toISOString(),end_at:new Date(Date.now()-3600000).toISOString(),cutoff_at:new Date(Date.now()-10800000).toISOString()},activity]);
  await asUser(ids[0]);
  await check('DB-18','SQLI-11 feedback literal persisted',async()=>{
    await db.query('select submit_feedback($1,5,$2)',[enrollment.enrollment_id,marker]);
    assert.equal((await db.query('select comments from feedback where enrollment_id=$1',[enrollment.enrollment_id])).rows[0].comments,marker);
  });
  await check('DB-19','SQLI-14 UUID rejects quote marker',()=>assert.rejects(db.query('select enroll_in_activity($1)',[marker]),/invalid input syntax for type uuid/));
  await asUser(ids[5]);
  await db.query('select manage_user($1,$2,$3)',[ids[2],{role:'senior',account_status:'disabled',verification_status:'verified'},'Synthetic disabled test']);
  await asUser(ids[2]);
  await check('DB-20','disabled account cannot mutate',()=>denied('select enroll_in_activity($1)',[activity]));
  await check('DB-21','disabled account sees no activities',async()=>assert.equal((await db.query('select * from activities')).rows.length,0));
  await db.exec('reset role; set role anon');
  await check('DB-22','anonymous RPC denied',()=>denied('select enroll_in_activity($1)',[activity]));
  await check('DB-23','anonymous profile read denied',()=>denied('select * from profiles'));
  await asUser(ids[3]);
  await check('DB-24','direct RPC capacity upper bound',()=>assert.rejects(db.query('select save_activity($1)',[{...payload,capacity:10001}]),/constraint|Invalid/));
  await check('DB-25','direct RPC requirements upper bound',()=>assert.rejects(db.query('select save_activity($1)',[{...payload,requirements:'x'.repeat(2001)}]),/constraint|Invalid/));
  await check('DB-26','direct RPC fee upper bound',()=>assert.rejects(db.query('select save_activity($1)',[{...payload,is_free:false,fee:100001}]),/constraint|Invalid/));
  console.log(`Phase 4 database checks: ${count} passed. Local PGlite with auth.uid shim; not hosted Auth or concurrent multi-connection evidence.`);
}
