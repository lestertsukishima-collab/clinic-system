<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
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

        return view('patient-directory', [
            'patients' => $patients->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }
}
