<?php

namespace Tests\Feature;

use App\Services\Community;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

class Phase4InjectionTest extends Phase4TestCase
{
    private const PAYLOAD = "' OR '1'='1";
    public function test_SQLI_01_login(): void { $this->postJson('/login', ['email' => self::PAYLOAD, 'password' => 'Synthetic-only'])->assertUnprocessable()->assertCookieMissing(Community::AUTH_COOKIE); }
    public function test_SQLI_02_search(): void { $this->getJson('/?'.http_build_query(['q' => "' OR 1=1 --"]))->assertOk()->assertJsonPath('count', 0); }
    public function test_SQLI_03_category(): void { $this->getJson('/?'.http_build_query(['category' => self::PAYLOAD]))->assertOk()->assertJsonPath('count', 0); }
    public static function textFields(): array
    {
        return ['SQLI-04' => ['profile','full_name'], 'SQLI-05' => ['profile','address'], 'SQLI-06' => ['activity','title'], 'SQLI-07' => ['activity','description'], 'SQLI-08' => ['activity','venue'], 'SQLI-09' => ['announcement','title'], 'SQLI-10' => ['announcement','message']];
    }
    #[DataProvider('textFields')]
    public function test_text_is_stored_literally(string $form, string $field): void
    {
        if ($form === 'profile') {
            $this->postJson('/profile', array_replace(['full_name' => 'Synthetic Senior'], [$field => self::PAYLOAD]))->assertOk();
            $row = $field === 'address' ? session('demo_senior') : collect(session('demo_profiles'))->firstWhere('user_id', 'demo-senior');
        } elseif ($form === 'activity') {
            $this->withSession(['demo_role' => 'coordinator'])->postJson('/workspace/create', array_replace($this->activityPayload(), [$field => self::PAYLOAD]))->assertOk();
            $rows = session('demo_activities'); $row = end($rows); $this->assertCount(5, $rows);
        } else {
            $this->withSession(['demo_role' => 'coordinator'])->postJson('/announcements', array_replace(['activity_id' => '1','title' => 'Synthetic','message' => 'Text'], [$field => self::PAYLOAD]))->assertOk();
            $rows = session('demo_announcements'); $row = end($rows); $this->assertCount(2, $rows);
        }
        $this->assertSame(self::PAYLOAD, $row[$field]);
    }
    public function test_SQLI_11_feedback(): void
    {
        $id = $this->completedEnrollment();
        $this->postJson('/history/'.$id.'/feedback', ['rating' => 5, 'comments' => self::PAYLOAD])->assertOk();
        $this->assertSame(self::PAYLOAD, session('demo_feedback')[0]['comments']);
    }
    public function test_SQLI_12_enrollment_reason(): void
    {
        $this->withSession(['demo_role' => 'coordinator'])->postJson('/workspace/1/enrollment', ['senior_id' => 'demo-senior', 'status' => 'confirmed', 'reason' => self::PAYLOAD])->assertOk();
        $this->assertCount(1, session('demo_enrollments'));
    }
    public function test_SQLI_13_attendance_remarks(): void
    {
        $id = $this->completedEnrollment();
        $this->withSession(['demo_role' => 'coordinator'])->postJson('/workspace/1/attendance', ['enrollment_id' => $id, 'attended' => 1, 'remarks' => self::PAYLOAD])->assertOk();
        $this->assertSame(self::PAYLOAD, session('demo_enrollments')[0]['remarks']);
    }
    public function test_SQLI_14_ids(): void
    {
        $this->live();
        $this->postJson('/activities/'.rawurlencode(self::PAYLOAD).'/enroll')->assertUnprocessable()->assertDontSee('SQLSTATE');
    }
}
