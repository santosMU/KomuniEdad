<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

class Phase4ValidationTest extends Phase4TestCase
{
    public static function invalidFields(): array
    {
        $rows = [
            ['VAL-01', 'register', 'full_name', ''], ['VAL-02', 'register', 'email', ''],
            ['VAL-03', 'register', 'email', 'abc'], ['VAL-04', 'register', 'password', 'short'],
            ['VAL-05', 'register', 'password_confirmation', 'different', 'password'], ['VAL-06', 'register', 'full_name', '   '],
            ['VAL-07', 'profile', 'full_name', str_repeat('a', 121)], ['VAL-08', 'profile', 'contact_number', str_repeat('1', 31)],
            ['VAL-09', 'profile', 'birthdate', '2999-01-01'], ['VAL-10', 'profile', 'address', str_repeat('a', 501)],
            ['VAL-11', 'activity', 'title', str_repeat('a', 161)], ['VAL-12', 'activity', 'description', str_repeat('a', 5001)],
            ['VAL-13', 'activity', 'venue', str_repeat('a', 201)], ['VAL-14', 'activity', 'capacity', 0],
            ['VAL-15', 'activity', 'capacity', -1], ['VAL-16', 'activity', 'capacity', 1.5], ['VAL-17', 'activity', 'capacity', 10001],
            ['VAL-18', 'activity', 'end_at', '2000-01-01'], ['VAL-19', 'activity', 'cutoff_at', '2999-01-01'],
            ['VAL-20', 'activity', 'status', 'invalid'], ['VAL-21', 'activity', 'fee', -1], ['VAL-22', 'paid', 'fee', 0],
            ['VAL-23', 'feedback', 'rating', 0], ['VAL-24', 'feedback', 'rating', 6], ['VAL-25', 'feedback', 'comments', str_repeat('a', 2001)],
            ['VAL-26', 'attendance', 'remarks', str_repeat('a', 1001)], ['VAL-27', 'enrollment', 'reason', 'ab'],
            ['VAL-31', 'announcement', 'title', str_repeat('a', 161)], ['VAL-32', 'announcement', 'message', str_repeat('a', 5001)],
            ['VAL-33', 'user', 'role', 'owner'], ['VAL-34', 'user', 'account_status', 'unknown'], ['VAL-35', 'user', 'verification_status', 'unknown'],
        ];
        $out = [];
        foreach ($rows as $r) $out[$r[0]] = array_slice($r, 1);
        return $out;
    }

    #[DataProvider('invalidFields')]
    public function test_rejects_invalid_field(string $form, string $field, mixed $value, ?string $error = null): void
    {
        [$url, $data, $role] = match ($form) {
            'register' => ['/register', $this->registration(), 'senior'],
            'profile' => ['/profile', ['full_name' => 'Synthetic Senior'], 'senior'],
            'activity', 'paid' => ['/workspace/create', array_replace($this->activityPayload(), ['is_free' => $form === 'paid' ? 0 : 1]), 'coordinator'],
            'feedback' => ['/history/missing/feedback', ['rating' => 5], 'senior'],
            'attendance' => ['/workspace/1/attendance', ['enrollment_id' => 'missing', 'attended' => 1], 'coordinator'],
            'enrollment' => ['/workspace/1/enrollment', ['senior_id' => 'demo-senior', 'status' => 'confirmed', 'reason' => 'Test reason'], 'coordinator'],
            'announcement' => ['/announcements', ['title' => 'Test', 'message' => 'Message', 'activity_id' => '1'], 'coordinator'],
            'user' => ['/administration/users/demo-senior', ['role' => 'senior', 'account_status' => 'active', 'verification_status' => 'pending', 'reason' => 'Test reason'], 'admin'],
        };
        $this->withSession(['demo_role' => $role])->postJson($url, array_replace($data, [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($error ?? $field);
    }

    public function test_VAL_28_nonexistent_activity(): void { $this->getJson('/activities/missing')->assertNotFound(); }
    public function test_VAL_29_nonexistent_enrollment(): void { $this->postJson('/enrollments/missing/withdraw')->assertNotFound(); }
    public function test_VAL_30_malformed_uuid(): void
    {
        $this->live();
        $this->postJson('/activities/not-a-uuid/enroll')->assertUnprocessable();
    }
}
