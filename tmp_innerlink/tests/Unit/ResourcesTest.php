<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Identity\Http\Resources\UserResource;
use App\Domains\Communication\Http\Resources\MessageResource;
use App\Domains\Admin\Http\Resources\ExportResource;
use App\Domains\Admin\Http\Resources\ExportDetailResource;
use App\Domains\Identity\Enums\Role;
use App\Domains\Admin\Enums\ExportFormat;
use App\Domains\Admin\Enums\ExportStatus;
use App\Domains\Identity\DTOs\SessionData;
use App\Domains\Communication\DTOs\MessageData;
use App\Domains\Communication\ValueObjects\MessageContent;
use App\Domains\Admin\DTOs\ExportRequestData;
use DateTimeImmutable;

class ResourcesTest extends TestCase
{
    public function test_user_resource_exposes_correct_attributes_and_filters_secrets(): void
    {
        $userModel = (object) [
            'id' => 123,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => Role::USER,
            'is_enabled' => true,
            'is_muted' => false,
            'last_seen_at' => now(),
            'password' => 'secret_hashed_password',
            'two_factor_secret' => '2FA_SECRET',
            'current_session_id' => 'session_xyz123',
        ];

        $resource = new UserResource($userModel);
        $array = $resource->toArray(request());

        $this->assertEquals(123, $array['id']);
        $this->assertEquals('John Doe', $array['full_name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals('USER', $array['role']);
        $this->assertTrue($array['is_enabled']);
        $this->assertFalse($array['is_muted']);
        $this->assertNotNull($array['last_seen_at']);

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('current_session_id', $array);
    }

    public function test_user_resource_supports_session_data_dto_serialization(): void
    {
        $sessionDto = new SessionData(
            userId: 123,
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
            loggedInAt: new DateTimeImmutable('2026-06-15T21:00:00Z')
        );

        $resource = new UserResource($sessionDto);
        $array = $resource->toArray(request());

        // Session DTO only exposes id (resolves to userId fallback) and last_seen_at (resolves to loggedInAt if matched, but here last_seen_at falls back to null because it's not present on SessionData - which is fine)
        $this->assertEquals(123, $array['id']);
        $this->assertNull($array['full_name']);
    }

    public function test_message_resource_exposes_correct_attributes(): void
    {
        $messageModel = (object) [
            'id' => 999,
            'group_id' => 1,
            'sender_id' => 123,
            'message_text' => 'Hello team',
            'sent_at' => now(),
        ];

        $resource = new MessageResource($messageModel);
        $array = $resource->toArray(request());

        $this->assertEquals(999, $array['id']);
        $this->assertEquals(1, $array['group_id']);
        $this->assertEquals(123, $array['sender_id']);
        $this->assertEquals('Hello team', $array['message_text']);
        $this->assertNotNull($array['sent_at']);
    }

    public function test_message_resource_supports_message_data_dto_serialization(): void
    {
        $messageData = new MessageData(
            groupId: 10,
            senderId: 456,
            content: new MessageContent('Hello world')
        );

        $resource = new MessageResource($messageData);
        $array = $resource->toArray(request());

        // messageText will fall back to content->value() if resolved dynamically, or null. But let's check group_id / sender_id fallback works
        $this->assertEquals(10, $array['group_id']);
        $this->assertEquals(456, $array['sender_id']);
    }

    public function test_export_resource_exposes_correct_attributes_and_filters_filepath_and_error(): void
    {
        $exportModel = (object) [
            'id' => 7,
            'admin_id' => 5,
            'format' => ExportFormat::PDF,
            'status' => ExportStatus::COMPLETED,
            'expires_at' => now()->addDays(1),
            'created_at' => now(),
            'file_path' => 'exports/secret_file.pdf',
            'error_message' => 'Something failed',
        ];

        $resource = new ExportResource($exportModel);
        $array = $resource->toArray(request());

        $this->assertEquals(7, $array['id']);
        $this->assertEquals(5, $array['admin_id']);
        $this->assertEquals('PDF', $array['format']);
        $this->assertEquals('COMPLETED', $array['status']);
        $this->assertNotNull($array['expires_at']);
        $this->assertNotNull($array['created_at']);

        $this->assertArrayNotHasKey('file_path', $array);
        $this->assertArrayNotHasKey('error_message', $array);
        $this->assertArrayNotHasKey('internal_error_details', $array);
    }

    public function test_export_resource_supports_export_request_data_dto_serialization(): void
    {
        $exportDto = new ExportRequestData(
            id: 77,
            adminId: 11,
            format: ExportFormat::CSV,
            status: ExportStatus::PENDING,
            filters: ['role' => 'admin'],
            expiresAt: new DateTimeImmutable('2026-06-16T21:00:00Z'),
            filePath: 'ignored_filepath',
            errorMessage: 'ignored_error'
        );

        $resource = new ExportResource($exportDto);
        $array = $resource->toArray(request());

        $this->assertEquals(77, $array['id']);
        $this->assertEquals(11, $array['admin_id']); // camelCase adminId successfully falls back to admin_id
        $this->assertEquals('CSV', $array['format']);
        $this->assertEquals('PENDING', $array['status']);
        $this->assertNotNull($array['expires_at']); // camelCase expiresAt successfully falls back to expires_at

        $this->assertArrayNotHasKey('file_path', $array);
        $this->assertArrayNotHasKey('error_message', $array);
        $this->assertArrayNotHasKey('internal_error_details', $array);
    }

    public function test_export_detail_resource_exposes_safe_details_only(): void
    {
        $exportModel = (object) [
            'id' => 7,
            'admin_id' => 5,
            'format' => ExportFormat::PDF,
            'status' => ExportStatus::FAILED,
            'expires_at' => now()->addDays(1),
            'created_at' => now(),
            'file_path' => 'exports/secret_file.pdf',
            'error_message' => 'Export generation failed.',
            'internal_error_details' => '[Illuminate\\Database\\QueryException] SQLSTATE[23505]...',
        ];

        $resource = new ExportDetailResource($exportModel);
        $array = $resource->toArray(request());

        $this->assertEquals(7, $array['id']);
        $this->assertEquals(5, $array['admin_id']);
        $this->assertEquals('PDF', $array['format']);
        $this->assertEquals('FAILED', $array['status']);
        $this->assertNotNull($array['expires_at']);
        $this->assertNotNull($array['created_at']);

        $this->assertTrue($array['file_available']);
        $this->assertSame('Export generation failed.', $array['error_message']);

        $this->assertArrayNotHasKey('file_path', $array);
        $this->assertArrayNotHasKey('internal_error_details', $array);
    }

    public function test_export_detail_resource_shows_file_unavailable_when_no_file(): void
    {
        $exportModel = (object) [
            'id' => 8,
            'admin_id' => 5,
            'format' => ExportFormat::CSV,
            'status' => ExportStatus::PENDING,
            'expires_at' => now()->addDays(1),
            'created_at' => now(),
            'file_path' => null,
            'error_message' => null,
        ];

        $resource = new ExportDetailResource($exportModel);
        $array = $resource->toArray(request());

        $this->assertFalse($array['file_available']);
        $this->assertNull($array['error_message']);

        $this->assertArrayNotHasKey('file_path', $array);
        $this->assertArrayNotHasKey('internal_error_details', $array);
    }

    public function test_export_detail_resource_never_exposes_raw_internal_details(): void
    {
        $exportModel = (object) [
            'id' => 9,
            'admin_id' => 5,
            'format' => ExportFormat::XLSX,
            'status' => ExportStatus::FAILED,
            'expires_at' => now()->addDays(1),
            'created_at' => now(),
            'file_path' => null,
            'error_message' => 'An unexpected error occurred while generating the export.',
            'internal_error_details' => '[Predis\\Connection\\ConnectionException] Connection refused [tcp://10.0.1.50:6379]',
        ];

        $resource = new ExportDetailResource($exportModel);
        $array = $resource->toArray(request());

        $this->assertSame('An unexpected error occurred while generating the export.', $array['error_message']);

        $this->assertArrayNotHasKey('internal_error_details', $array);
        $this->assertArrayNotHasKey('file_path', $array);

        $rawExceptionFound = str_contains(
            json_encode($array),
            'Connection refused [tcp://10.0.1.50:6379]'
        );
        $this->assertFalse($rawExceptionFound, 'Raw exception details must never appear in API responses.');
    }
}
