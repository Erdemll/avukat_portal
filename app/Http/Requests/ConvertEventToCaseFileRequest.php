<?php

namespace App\Http\Requests;

use App\Models\CaseFile;

class ConvertEventToCaseFileRequest extends StoreCaseFileRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event !== null
            && ($this->user()?->can('view', $event) ?? false)
            && ($this->user()?->can('create', CaseFile::class) ?? false);
    }
}
