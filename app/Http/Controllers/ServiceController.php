<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('services.index', [
            'services' => Service::withCount('appointments')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('services.create', [
            'service' => new Service,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:services,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $service = Service::create($validated);

        return redirect()->route('services.show', $service)->with('success', 'Service created successfully.');
    }

    public function show(Service $service): View
    {
        return view('services.show', [
            'service' => $service->loadCount('appointments'),
        ]);
    }

    public function edit(Service $service): View
    {
        return view('services.edit', [
            'service' => $service,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:services,name,'.$service->id],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $service->update($validated);

        return redirect()->route('services.show', $service)->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->appointments()->exists()) {
            return back()->with('error', 'This service is used by clinic appointments and cannot be deleted.');
        }

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }
}
