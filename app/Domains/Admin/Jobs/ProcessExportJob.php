<?php

declare(strict_types=1);

namespace App\Domains\Admin\Jobs;

use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Admin\Events\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    private const ERROR_MAP = [
        'Illuminate\\Database\\QueryException' => [
            'public' => 'Export generation failed.',
            'internal' => true,
        ],
        'Predis\\Connection\\ConnectionException' => [
            'public' => 'Export processing failed due to a background service error.',
            'internal' => true,
        ],
        'Aws\\S3\\Exception\\S3Exception' => [
            'public' => 'Export file could not be stored.',
            'internal' => true,
        ],
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $exportRequestId
    ) {
        $this->connection = config('queue.default');
    }

    /**
     * Execute the job.
     */
    public function handle(ExportRepositoryInterface $exportRepository): void
    {
        DB::transaction(function () use ($exportRepository) {
            $export = $exportRepository->findRequest($this->exportRequestId);

            if (!$export) {
                return;
            }

            $exportRepository->markProcessing($this->exportRequestId);

            $filePath = 'exports/export_' . $this->exportRequestId . '.tmp';

            Storage::disk(config('filesystems.default'))->put($filePath, '');

            $exportRepository->markCompleted($this->exportRequestId, $filePath);

            DB::afterCommit(function () use ($filePath) {
                event(new ExportCompleted($this->exportRequestId, $filePath));
            });
        });
    }

    /**
     * Handle a job failure.
     *
     * NEVER exposes raw exception details through the API.
     * Raw details are stored in internal_error_details (encrypted at rest)
     * and logged to the application log for observability.
     */
    public function failed(Throwable $exception): void
    {
        $publicMessage = $this->resolvePublicMessage($exception);
        $internalDetails = $this->formatInternalDetails($exception);

        $exportRepository = app(ExportRepositoryInterface::class);
        $exportRepository->markFailed(
            $this->exportRequestId,
            $publicMessage,
            $internalDetails
        );

        Log::error('Export processing failed', [
            'export_request_id' => $this->exportRequestId,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
        ]);
    }

    private function resolvePublicMessage(Throwable $exception): string
    {
        $class = get_class($exception);

        if (isset(self::ERROR_MAP[$class])) {
            return self::ERROR_MAP[$class]['public'];
        }

        foreach (self::ERROR_MAP as $knownClass => $config) {
            if ($exception instanceof $knownClass) {
                return $config['public'];
            }
        }

        return 'An unexpected error occurred while generating the export.';
    }

    private function formatInternalDetails(Throwable $exception): string
    {
        return sprintf(
            "[%s] %s\n\nStack Trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );
    }
}
