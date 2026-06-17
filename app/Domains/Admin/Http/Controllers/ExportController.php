<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Requests\CreateExportRequest;
use App\Domains\Admin\Http\Resources\ExportResource;
use App\Domains\Admin\Http\Resources\ExportDetailResource;
use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Admin\Actions\RequestExportAction;
use App\Domains\Admin\DTOs\ExportConfigData;
use App\Domains\Admin\Enums\ExportFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    /**
     * Request a new export. Restricted via admin.only middleware in routes.
     *
     * @param CreateExportRequest $request
     * @param RequestExportAction $action
     * @return ExportResource
     */
    public function store(
        CreateExportRequest $request,
        RequestExportAction $action
    ): ExportResource {
        $config = new ExportConfigData(
            format: ExportFormat::from($request->input('format')),
            type: 'users',
            filters: $request->input('filters', [])
        );

        $exportData = $action->execute(
            adminId: $request->user()->id,
            config: $config
        );

        return new ExportResource($exportData);
    }

    /**
     * Retrieve export history for the admin. Restricted via admin.only middleware in routes.
     *
     * @param Request $request
     * @param ExportRepositoryInterface $exportRepository
     * @return AnonymousResourceCollection
     */
    public function index(
        Request $request,
        ExportRepositoryInterface $exportRepository
    ): AnonymousResourceCollection {
        $adminId = $request->user()->id;
        $exports = $exportRepository->getAdminHistory($adminId);

        return ExportResource::collection($exports);
    }

    /**
     * Retrieve a specific export request by ID, authorizing via ExportPolicy.
     *
     * @param string $id
     * @param Request $request
     * @param ExportRepositoryInterface $exportRepository
     * @return ExportDetailResource
     */
    public function show(
        string $id,
        Request $request,
        ExportRepositoryInterface $exportRepository
    ): ExportDetailResource {
        $export = $exportRepository->findRequest((int) $id);

        abort_if(!$export, 404);

        Gate::authorize('view', $export);

        return new ExportDetailResource($export);
    }
}
