<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Http\Requests\UpdateEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $documents = EmployeeDocument::query()
            ->with('employee')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderBy('expires_at')
            ->paginate(20)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'categories' => EmployeeDocument::CATEGORIES,
            'statuses' => EmployeeDocument::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'filters' => [
                'category' => $request->string('category')->toString(),
                'status' => $request->string('status')->toString(),
                'employee_id' => $request->string('employee_id')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EmployeeDocument::class);

        return view('documents.create', [
            'document' => new EmployeeDocument([
                'category' => 'kontrak',
                'status' => EmployeeDocument::STATUS_ACTIVE,
                'issued_at' => now(),
            ]),
            'categories' => EmployeeDocument::CATEGORIES,
            'statuses' => EmployeeDocument::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function store(StoreEmployeeDocumentRequest $request): RedirectResponse
    {
        EmployeeDocument::create($request->validated());

        return redirect()
            ->route('employee-documents.index')
            ->with('status', 'Dokumen ditambahkan.');
    }

    public function edit(EmployeeDocument $employeeDocument): View
    {
        $this->authorize('update', $employeeDocument);

        return view('documents.edit', [
            'document' => $employeeDocument,
            'categories' => EmployeeDocument::CATEGORIES,
            'statuses' => EmployeeDocument::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function update(UpdateEmployeeDocumentRequest $request, EmployeeDocument $employeeDocument): RedirectResponse
    {
        $employeeDocument->update($request->validated());

        return redirect()
            ->route('employee-documents.index')
            ->with('status', 'Dokumen diperbarui.');
    }

    public function destroy(EmployeeDocument $employeeDocument): RedirectResponse
    {
        $this->authorize('delete', $employeeDocument);

        $employeeDocument->delete();

        return redirect()
            ->route('employee-documents.index')
            ->with('status', 'Dokumen dihapus.');
    }
}
