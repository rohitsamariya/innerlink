<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Resources;

use Illuminate\Http\Request;

class ExportDetailResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $filePath = isset($this->file_path) ? $this->file_path : (isset($this->filePath) ? $this->filePath : null);
        $errorMessage = isset($this->error_message) ? $this->error_message : (isset($this->errorMessage) ? $this->errorMessage : null);

        $data['file_available'] = $filePath !== null;

        $data['error_message'] = $errorMessage;

        return $data;
    }
}
