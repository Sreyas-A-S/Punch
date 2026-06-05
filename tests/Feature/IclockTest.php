<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
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
     * Test GET /iclock/cdata with options request.
     */
    public function test_cdata_options_request(): void
    {
        $response = $this->get('/iclock/cdata?SN=TEST_DEVICE_123&options=all');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('GET OPTION FROM: TEST_DEVICE_123', $content);
        $this->assertStringContainsString('Realtime=1', $content);
        $this->assertStringContainsString('Delay=30', $content);
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
     * Test the full lifecycle of a device command.
     */
    public function test_device_command_lifecycle(): void
    {
        $sn = 'TEST_DEVICE_123';
        
        // 1. Create a pending command
        $command = DeviceCommand::create([
            'device_sn' => $sn,
            'command' => 'REBOOT',
            'status' => 'pending'
        ]);

        // 2. Device polls for commands
        $response = $this->get("/iclock/getrequest?SN={$sn}");
        $response->assertStatus(200);
        $this->assertEquals("C:{$command->id}:REBOOT", $response->getContent());

        // Verify status changed to 'sent'
        $this->assertDatabaseHas('device_commands', [
            'id' => $command->id,
            'status' => 'sent'
        ]);

        // 3. Device acknowledges command execution
        $response = $this->call(
            'POST',
            "/iclock/devicecmd?SN={$sn}",
            [], [], [], [],
            "ID={$command->id}&Return=0"
        );
        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());

        // Verify status changed to 'completed'
        $this->assertDatabaseHas('device_commands', [
            'id' => $command->id,
            'status' => 'completed'
        ]);
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
        $this->assertDatabaseHas('device_attendance_logs', [
            'employee_pin' => '1001',
            'timestamp' => '2026-06-02 14:00:00',
            'status' => 'Check-Out',
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

    /**
     * Test POST /iclock/cdata with biometric data (should be skipped gracefully).
     */
    public function test_cdata_skips_biometric_data(): void
    {
        $initialCount = AttendanceLog::count();

        // Sample biometric data payload that previously caused SQL errors
        $rawPayload = "FACE PIN=9\tFID=10\tSIZE=1648\n";
        $rawPayload .= "FP PIN=35\tFID=0\tSIZE=500\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=TEST_DEVICE_123&table=BIODATA',
            [], [], [], [],
            $rawPayload
        );

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());

        // Ensure no attendance logs were created for this junk data
        $this->assertEquals($initialCount, AttendanceLog::count());
    }
}
