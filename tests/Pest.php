<?php

use App\CaseAssignmentRole;
use App\Models\CaseFile;
use App\Models\CaseFileAssignment;
use App\Models\CaseType;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function role(string $slug): Role
{
    return Role::query()->firstOrCreate(['slug' => $slug], ['name' => $slug]);
}

function userWithRole(string $slug, bool $active = true): User
{
    return User::factory()->create(['role_id' => role($slug), 'is_active' => $active]);
}

function legalEvent(User $creator, User $lawyer): Event
{
    return Event::factory()->create([
        'event_type_id' => EventType::factory()->create(),
        'created_by' => $creator,
        'assigned_lawyer_id' => $lawyer,
    ]);
}

function eventPayload(User $lawyer): array
{
    return [
        'event_type_id' => EventType::factory()->create()->id,
        'title' => 'Ödeme uyuşmazlığı',
        'description' => 'Açıklama',
        'assigned_lawyer_id' => $lawyer->id,
        'priority' => 'normal',
    ];
}

/** @param array<int, User> $lawyers */
function legalCaseFile(User $creator, array $lawyers = []): CaseFile
{
    $caseFile = CaseFile::factory()->create([
        'case_type_id' => CaseType::factory()->create(),
        'created_by' => $creator,
    ]);

    foreach ($lawyers as $index => $lawyer) {
        CaseFileAssignment::factory()->create([
            'case_file_id' => $caseFile,
            'lawyer_id' => $lawyer,
            'role' => $index === 0 ? CaseAssignmentRole::Lead : CaseAssignmentRole::Lawyer,
            'assigned_by' => $creator,
        ]);
    }

    return $caseFile->refresh();
}
