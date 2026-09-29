<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

class Phase4XssTest extends Phase4TestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';
    public static function activityFields(): array { return ['XSS-01'=>['title'], 'XSS-02'=>['description'], 'XSS-03'=>['venue'], 'XSS-04'=>['requirements']]; }
    #[DataProvider('activityFields')]
    public function test_activity_encoding(string $field): void
    {
        $this->withSession(['demo_role'=>'coordinator'])->postJson('/workspace/create', array_replace($this->activityPayload(), [$field=>self::PAYLOAD]))->assertOk();
        $rows=session('demo_activities'); $row=end($rows);
        $this->withSession(['demo_role'=>'senior'])->get('/activities/'.$row['activity_id'])->assertOk()->assertSee(e(self::PAYLOAD),false)->assertDontSee(self::PAYLOAD,false);
    }
    public static function notices(): array { return ['XSS-05'=>['title'], 'XSS-06'=>['message']]; }
    #[DataProvider('notices')]
    public function test_announcement_encoding(string $field): void
    {
        $this->withSession(['demo_role'=>'coordinator'])->postJson('/announcements', array_replace(['activity_id'=>'1','title'=>'Test','message'=>'Test'],[$field=>'<script>alert(1)</script>']))->assertOk();
        $this->get('/announcements')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
    }
    public static function profiles(): array { return ['XSS-07'=>['full_name'], 'XSS-08'=>['address']]; }
    #[DataProvider('profiles')]
    public function test_profile_attribute_encoding(string $field): void
    {
        $p='"><svg/onload=alert(1)>';
        $this->postJson('/profile',array_replace(['full_name'=>'Synthetic'],[$field=>$p]))->assertOk();
        $this->get('/profile')->assertSee(e($p),false)->assertDontSee($p,false);
    }
    public function test_XSS_09_feedback_is_not_executable(): void
    {
        $id=$this->completedEnrollment(); $this->postJson('/history/'.$id.'/feedback',['rating'=>5,'comments'=>self::PAYLOAD])->assertOk();
        $this->assertSame(self::PAYLOAD,session('demo_feedback')[0]['comments']);
        // Current history intentionally displays the score only, not comments.
        $this->get('/history')->assertOk()->assertDontSee(self::PAYLOAD,false);
    }
    public function test_XSS_10_reflected_search(): void { $this->get('/?'.http_build_query(['q'=>self::PAYLOAD]))->assertOk()->assertDontSee(self::PAYLOAD,false); }
    public function test_XSS_11_json_markup(): void
    {
        $a=app(Community::class)->activities(); $a[0]['title']=self::PAYLOAD;
        $r=$this->withSession(['demo_activities'=>$a])->getJson('/?q=onerror')->assertOk()->assertJsonPath('count',1);
        $this->assertStringContainsString(e(self::PAYLOAD),$r->json('html')); $this->assertStringNotContainsString(self::PAYLOAD,$r->json('html'));
    }
    public function test_XSS_12_validation_error_encoding(): void
    {
        $this->withSession(['errors'=>new \Illuminate\Support\ViewErrorBag])->get('/login');
        session()->get('errors')->put('default',new MessageBag(['field'=>self::PAYLOAD]));
        $this->get('/login')->assertSee(e(self::PAYLOAD),false)->assertDontSee(self::PAYLOAD,false);
    }
    public function test_XSS_13_status_encoding(): void { $this->withSession(['status'=>self::PAYLOAD])->get('/')->assertSee(e(self::PAYLOAD),false)->assertDontSee(self::PAYLOAD,false); }
}
