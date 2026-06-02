<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AttendanceLog;
use Tests\TestCase;

class IclockTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test GET /iclock/cdata.
     */
    public function test_cdata_get_request(): void
    {
        $response = $this->get('/iclock/cdata?SN=TEST_DEVICE_123');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test GET /iclock/getrequest.
     */
    public function test_getrequest(): void
    {
        $response = $this->get('/iclock/getrequest?SN=TEST_DEVICE_123');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test POST /iclock/cdata with tab-delimited attendance log data.
     */
    public function test_cdata_post_request_processing(): void
    {
        // Sample tab-separated logs transmitted by a biometric device
        $rawPayload = "1001\t2026-06-02 14:00:00\t1\n";
        $rawPayload .= "1002\t2026-06-02 14:05:30\t0\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=TEST_DEVICE_123&table=ATTLOG',
            [], // parameters
            [], // cookies
            [], // files
            [], // server
            $rawPayload // content
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('OK', $response->getContent());

        // Check if logs are successfully created in the database
        $this->assertDatabaseHas('attendance_logs', [
            'employee_pin' => '1001',
            'timestamp' => '2026-06-02 14:00:00',
            'status' => '1',
            'device_sn' => 'TEST_DEVICE_123',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'employee_pin' => '1002',
            'timestamp' => '2026-06-02 14:05:30',
            'status' => '0',
            'device_sn' => 'TEST_DEVICE_123',
        ]);
    }
}
