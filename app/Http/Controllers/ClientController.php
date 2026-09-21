<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Client::class);
        $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->trim()->toString());
        $clients = Client::query()
            ->visibleTo($request->user())
            ->with('party')
            ->when($search !== '', fn ($query) => $query->whereHas('party', fn ($party) => $party->where(fn ($partySearch) => $partySearch
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('surname', 'like', '%'.$search.'%')
                ->orWhere('company_name', 'like', '%'.$search.'%'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Client::class);

        return view('clients.form', ['client' => new Client]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request, ClientManagementService $clients): RedirectResponse
    {
        $client = $clients->create($request->validated(), $request->user());

        return redirect()->route('clients.show', $client)->with('success', 'Müvekkil oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Client $client): View
    {
        Gate::authorize('view', $client);
        $client->load(['party', 'party.caseFiles' => fn ($query) => $query->visibleTo($request->user())->with('caseType')->latest(), 'communications' => fn ($query) => $query->visibleTo($request->user())->with(['caseFile', 'user'])->latest('communication_at')->limit(20)]);
        $canViewIdentifiers = $request->user()->can('viewIdentifiers', $client->party);
        if ($canViewIdentifiers) {
            $client->load('party.identifiers');
        }

        return view('clients.show', compact('client', 'canViewIdentifiers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client): View
    {
        Gate::authorize('update', $client);
        $client->load('party');
        if (auth()->user()->can('viewIdentifiers', $client->party)) {
            $client->load('party.identifiers');
        }

        return view('clients.form', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client, ClientManagementService $clients): RedirectResponse
    {
        $client = $clients->update($client, $request->validated(), $request->user());

        return redirect()->route('clients.show', $client)->with('success', 'Müvekkil güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Client $client, ClientManagementService $clients): RedirectResponse
    {
        $clients->delete($client, $request->user());

        return redirect()->route('clients.index')->with('success', 'Müvekkil silindi.');
    }
}
