<?php

namespace App\Http\Controllers;

use App\DataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request, DataTable $table): View|JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');
        $patients = User::query()->where('role', 'patient')->withCount('appointments');

        if ($search !== '') {
            $patients->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->has('draw')) {
            return $table->response($request, $patients->orderBy('name'),
                ['name', 'email', 'created_at', 'appointments_count'],
                ['name', 'email'], 'patients._row', 'patient');
        }

        return view('patient-directory', [
            'patients' => $patients->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }
}
