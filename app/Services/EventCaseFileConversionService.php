<?php

namespace App\Services;

use App\Models\CaseFile;
use App\Models\Event;
use App\Models\User;

class EventCaseFileConversionService
{
    public function __construct(private CaseFileManagementService $caseFiles) {}

    /** @param array<string, mixed> $data */
    public function convert(Event $event, array $data, User $actor): CaseFile
    {
        return $this->caseFiles->create($data, $actor, $event);
    }
}
