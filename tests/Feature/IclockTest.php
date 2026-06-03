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
     * Test POST /iclock/devicecmd.
     */
    public function test_devicecmd(): void
    {
        $response = $this->call(
            'POST',
            '/iclock/devicecmd?SN=TEST_DEVICE_123',
            [], [], [], [],
            "ID=1&Return=0"
        );

        $response->assertStatus(200);
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
        $this->assertEquals('OK', $response->getContent());

        // Check if logs are successfully created in the database
        $this->assertDatabaseHas('attendance_logs', [
            'employee_pin' => '1001',
            'timestamp' => '2026-06-02 14:00:00',
            'status' => '1',
            'device_sn' => 'TEST_DEVICE_123',
        ]);
    }

    /**
     * Test POST /iclock/cdata with invalid format.
     */
    public function test_cdata_post_invalid_format(): void
    {
        $rawPayload = "INVALID_DATA_WITHOUT_TABS\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=TEST_DEVICE_123&table=ATTLOG',
            [], [], [], [],
            $rawPayload
        );

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
        // Validation of actual log file would be done manually or via Log::assertLogged if using a specific driver,
        // but for now we just ensure the controller doesn't crash.
    }

    /**
     * Test POST /iclock/cdata with empty body.
     */
    public function test_cdata_post_empty_body(): void
    {
        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=TEST_DEVICE_123&table=ATTLOG',
            [], [], [], [],
            ""
        );

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
    }
}
