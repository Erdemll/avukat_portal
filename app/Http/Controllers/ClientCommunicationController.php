<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientCommunicationRequest;
use App\Http\Requests\UpdateClientCommunicationRequest;
use App\Models\CaseFile;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Services\ClientCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientCommunicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ClientCommunication::class);
        $communications = ClientCommunication::query()->visibleTo($request->user())->with(['client.party', 'caseFile', 'user'])
            ->when($request->filled('client'), fn ($query) => $query->where('client_id', $request->integer('client')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->latest('communication_at')->paginate(25)->withQueryString();

        return view('client-communications.index', compact('communications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', ClientCommunication::class);

        return view('client-communications.form', ['clientCommunication' => new ClientCommunication(['client_id' => $request->integer('client') ?: null, 'case_file_id' => $request->integer('case_file') ?: null]), ...$this->formData($request)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientCommunicationRequest $request, ClientCommunicationService $service): RedirectResponse
    {
        $communication = $service->create($request->validated(), $request->user());

        return redirect()->route('client-communications.edit', $communication)->with('success', 'İletişim kaydedildi.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ClientCommunication $clientCommunication): View
    {
        Gate::authorize('view', $clientCommunication);
        $clientCommunication->load(['client.party', 'caseFile', 'user']);

        return view('client-communications.show', [
            'clientCommunication' => $clientCommunication,
            'canUpdate' => $request->user()->can('update', $clientCommunication),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ClientCommunication $clientCommunication): View
    {
        Gate::authorize('update', $clientCommunication);

        return view('client-communications.form', ['clientCommunication' => $clientCommunication, ...$this->formData($request)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientCommunicationRequest $request, ClientCommunication $clientCommunication, ClientCommunicationService $service): RedirectResponse
    {
        $service->update($clientCommunication, $request->validated(), $request->user());

        return redirect()->route('client-communications.index')->with('success', 'İletişim güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClientCommunication $clientCommunication): never
    {
        abort(405);
    }

    /** @return array<string, mixed> */
    private function formData(Request $request): array
    {
        return [
            'clients' => Client::query()->visibleTo($request->user())->with('party')->orderByDesc('updated_at')->limit(200)->get(),
            'caseFiles' => CaseFile::query()->visibleTo($request->user())->orderBy('case_no')->get(),
        ];
    }
}
