<?php

namespace App\Services;

use App\AuditAction;
use App\Models\Client;
use App\Models\Party;
use App\Models\PartyIdentifier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ClientManagementService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Client
    {
        return DB::transaction(function () use ($data, $actor): Client {
            Gate::forUser($actor)->authorize('create', Client::class);
            $party = new Party([
                ...Arr::only($data, ['type', 'name', 'surname', 'company_name', 'phone', 'email', 'address']),
                'notes' => $data['party_notes'] ?? null,
            ]);
            $party->created_by = $actor->id;
            $party->save();

            $client = new Client([
                'status' => 'active',
                'client_since' => $data['client_since'] ?? null,
                'notes' => $data['client_notes'] ?? null,
            ]);
            $client->party_id = $party->id;
            $client->created_by = $actor->id;
            $client->responsible_lawyer_id = $actor->isLawyer() ? $actor->id : null;
            $client->save();

            $this->storeIdentifier($party, $data, $actor);
            $this->audit->log(AuditAction::ClientCreated, $actor, auditable: $client, description: 'Müvekkil oluşturuldu.', newValues: ['client_id' => $client->id, 'party_id' => $party->id, 'type' => $party->type->value]);

            return $client->load('party.identifiers');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Client $client, array $data, User $actor): Client
    {
        return DB::transaction(function () use ($client, $data, $actor): Client {
            $client = Client::query()->with('party')->lockForUpdate()->findOrFail($client->id);
            Gate::forUser($actor)->authorize('update', $client);
            if ($client->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Müvekkil başka bir kullanıcı tarafından güncellendi. Sayfayı yenileyip tekrar deneyin.']);
            }
            $oldValues = [
                'status' => $client->status,
                'display_name' => $client->party->display_name,
                'phone' => $client->party->phone,
                'email' => $client->party->email,
                'address' => $client->party->address,
                'client_since' => $client->client_since?->toDateString(),
            ];
            $client->party->fill([
                ...Arr::only($data, ['type', 'name', 'surname', 'company_name', 'phone', 'email', 'address']),
                'notes' => $data['party_notes'] ?? null,
            ])->save();
            $client->fill([
                'status' => $data['status'],
                'client_since' => $data['client_since'] ?? null,
                'notes' => $data['client_notes'] ?? null,
            ])->forceFill(['lock_version' => $client->lock_version + 1])->save();
            $this->storeIdentifier($client->party, $data, $actor);

            $this->audit->log(AuditAction::ClientUpdated, $actor, auditable: $client, description: 'Müvekkil güncellendi.', oldValues: $oldValues, newValues: [
                'status' => $client->status,
                'display_name' => $client->party->display_name,
                'phone' => $client->party->phone,
                'email' => $client->party->email,
                'address' => $client->party->address,
                'client_since' => $client->client_since?->toDateString(),
                'identifier_type' => $data['identifier_type'] ?? null,
            ]);

            return $client->refresh()->load('party.identifiers');
        });
    }

    public function delete(Client $client, User $actor): void
    {
        DB::transaction(function () use ($client, $actor): void {
            $client = Client::query()->with('party')->lockForUpdate()->findOrFail($client->id);
            Gate::forUser($actor)->authorize('delete', $client);

            if ($client->communications()->exists() || $client->party->activeCaseFiles()->exists()) {
                throw ValidationException::withMessages([
                    'client' => 'Aktif hukuki dosya ilişkisi veya iletişim kaydı bulunan müvekkil silinemez.',
                ]);
            }

            $party = $client->party;
            $hasCaseFiles = $party->caseFiles()->exists();
            $this->audit->log(
                AuditAction::ClientDeleted,
                $actor,
                auditable: $client,
                description: 'Müvekkil silindi.',
                oldValues: [
                    'client_id' => $client->id,
                    'party_id' => $party->id,
                    'display_name' => $party->display_name,
                    'status' => $client->status,
                ],
            );
            $client->delete();

            if (! $hasCaseFiles) {
                $party->delete();
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function storeIdentifier(Party $party, array $data, User $actor): void
    {
        if (empty($data['identifier_type']) || empty($data['identifier_value'])) {
            return;
        }

        $identifier = $party->identifiers()->where('type', $data['identifier_type'])->first() ?? new PartyIdentifier;
        $identifier->fill([
            'type' => $data['identifier_type'],
            'value' => $data['identifier_value'],
            'country_code' => 'TR',
        ]);
        $identifier->party_id = $party->id;
        $identifier->created_by ??= $actor->id;
        try {
            $identifier->save();
        } catch (QueryException $exception) {
            if (! in_array($exception->errorInfo[0] ?? null, ['23000', '23505'], true)) {
                throw $exception;
            }

            throw ValidationException::withMessages(['identifier_value' => 'Bu kimlik veya vergi numarası daha önce kaydedilmiş.']);
        }
    }
}
