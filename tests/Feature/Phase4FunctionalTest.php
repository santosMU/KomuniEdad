<?php

namespace Tests\Feature;

use App\Services\Community;
use Tests\Phase4TestCase;

class Phase4FunctionalTest extends Phase4TestCase
{
    public function test_FUNC_10_search_controls_are_reachable(): void
    {
        $this->get('/')->assertOk()->assertSee('data-activity-search',false)->assertSee('name="q"',false)->assertSee('name="category"',false)->assertSee('name="status"',false);
    }
    public function test_FUNC_04_profile_links_to_history_and_announcements(): void
    {
        $html=$this->get('/profile')->assertOk()->getContent();
        $main=explode('</main>',explode('<main id="main">',$html)[1])[0];
        $this->assertStringContainsString('href="/history"',$main);
        $this->assertStringContainsString('href="/announcements"',$main);
    }
    public function test_FUNC_07_optional_requirements_do_not_break_detail(): void
    {
        $this->withSession(['demo_role'=>'coordinator'])->postJson('/workspace/create',$this->activityPayload())->assertOk();
        $rows=session('demo_activities'); $row=end($rows);
        $this->get('/activities/'.$row['activity_id'])->assertOk()->assertSee('No special requirements.');
    }
    public function test_FUNC_09_complete_local_role_workflow(): void
    {
        $this->post('/register',$this->registration())->assertRedirect('/login');
        $this->post('/login',$this->registration())->assertRedirect('/');
        $this->getJson('/?q=Morning')->assertJsonPath('count',1);
        $this->postJson('/activities/1/enroll')->assertOk();
        $id=session('demo_enrollments')[0]['enrollment_id'];
        $this->getJson('/?mine=1')->assertJsonPath('count',1);
        $this->postJson('/enrollments/'.$id.'/withdraw')->assertOk();
        $this->postJson('/activities/1/enroll')->assertOk();
        $entries=session('demo_enrollments'); $entry=end($entries);
        $a=app(Community::class)->activities()[0];
        $a=array_replace($a,['status'=>'completed','start_at'=>now()->subHours(2)->toIso8601String(),'end_at'=>now()->subHour()->toIso8601String(),'cutoff_at'=>now()->subHours(3)->toIso8601String()]);
        $this->withSession(['demo_role'=>'coordinator'])->postJson('/workspace/1/edit',$a)->assertOk();
        $this->postJson('/workspace/1/attendance',['enrollment_id'=>$entry['enrollment_id'],'attended'=>1,'remarks'=>'Synthetic attendance'])->assertOk();
        $this->postJson('/announcements',['activity_id'=>'1','title'=>'Synthetic notice','message'=>'Thank you'])->assertOk();
        $this->get('/reports')->assertOk()->assertSee('Morning movement');
        $this->withSession(['demo_role'=>'senior'])->postJson('/history/'.$entry['enrollment_id'].'/feedback',['rating'=>5,'comments'=>'Synthetic feedback'])->assertOk();
        $this->get('/history')->assertSee('Your feedback: 5 / 5');
        $this->get('/announcements')->assertSee('Synthetic notice');
        $this->postJson('/profile',['full_name'=>'Synthetic updated'])->assertOk();
        $this->get('/help')->assertOk();
        $this->withSession(['demo_role'=>'admin'])->postJson('/administration/users/demo-senior',['role'=>'senior','account_status'=>'active','verification_status'=>'verified','reason'=>'Synthetic verification'])->assertOk();
        $this->postJson('/administration/categories',['name'=>'New category','is_active'=>1])->assertOk();
        $this->postJson('/announcements',['title'=>'Global notice','message'=>'Synthetic global message'])->assertOk();
        $this->get('/administration')->assertOk()->assertSee('New category');
        $this->get('/reports')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        // Demo is intentionally a preview. Production authentication is tested separately.
        config(['komuniedad.demo'=>false]); $this->get('/profile')->assertRedirect('/login');
    }
}
