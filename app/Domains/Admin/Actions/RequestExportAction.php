<?php

declare(strict_types=1);

namespace App\Domains\Admin\Actions;

use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Admin\DTOs\ExportConfigData;
use App\Domains\Admin\DTOs\ExportRequestData;
use App\Domains\Admin\Enums\ExportFormat;
use App\Domains\Admin\Enums\ExportStatus;
use App\Domains\Admin\Jobs\ProcessExportJob;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;

final readonly class RequestExportAction
{
    public function __construct(
        private ExportRepositoryInterface $exportRepository
    ) {}

    /**
     * Create export request and dispatch background export job transaction-safely.
     *
     * @param int $adminId
     * @param ExportConfigData $config
     * @return ExportRequestData
     */
    public function execute(int $adminId, ExportConfigData $config): ExportRequestData
    {
        return DB::transaction(function () use ($adminId, $config) {
            $expiresAt = now()->addDays(1);
            $expiresAtStr = $expiresAt->toIso8601String();

            $exportRequest = $this->exportRepository->createRequest($adminId, $config, $expiresAtStr);

            DB::afterCommit(function () use ($exportRequest) {
                ProcessExportJob::dispatch($exportRequest->id);
            });

            $expiresAtVal = $exportRequest->expires_at;
            $expiresAtStrVal = ($expiresAtVal instanceof \DateTimeInterface)
                ? $expiresAtVal->format(\DateTimeInterface::ATOM)
                : (is_string($expiresAtVal) ? $expiresAtVal : 'now');

            // Explicitly cast to prevent Enum type mismatch
            $format = $exportRequest->format instanceof ExportFormat
                ? $exportRequest->format
                : ExportFormat::from($exportRequest->format);

            $status = $exportRequest->status instanceof ExportStatus
                ? $exportRequest->status
                : ExportStatus::from($exportRequest->status);

            return new ExportRequestData(
                id: $exportRequest->id,
                adminId: $exportRequest->admin_id,
                format: $format,
                status: $status,
                filters: $exportRequest->filters,
                expiresAt: new DateTimeImmutable($expiresAtStrVal),
                filePath: $exportRequest->file_path,
                errorMessage: $exportRequest->error_message
            );
        });
    }
}
