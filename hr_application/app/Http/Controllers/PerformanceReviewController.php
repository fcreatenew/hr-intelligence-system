<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerformanceReviewRequest;
use App\Http\Requests\UpdatePerformanceReviewRequest;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $reviews = PerformanceReview::query()
            ->with(['employee', 'reviewer'])
            ->when($request->filled('period'), fn ($q) => $q->where('period', $request->string('period')))
            ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->string('kategori')))
            ->orderByDesc('period')
            ->paginate(20)
            ->withQueryString();

        return view('performance.reviews.index', [
            'reviews' => $reviews,
            'statuses' => PerformanceReview::STATUSES,
            'kategoris' => ['A', 'B', 'C', 'D'],
            'filters' => [
                'period' => $request->string('period')->toString(),
                'kategori' => $request->string('kategori')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PerformanceReview::class);

        return view('performance.reviews.create', [
            'review' => new PerformanceReview([
                'period' => now()->format('Y').'-Q'.now()->quarter,
                'status' => PerformanceReview::STATUS_DRAFT,
                'review_date' => now(),
            ]),
            'statuses' => PerformanceReview::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function store(StorePerformanceReviewRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['kategori'] = PerformanceReview::kategoriFromRating($data['rating'] ?? null);

        PerformanceReview::create($data);

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', 'Review kinerja dibuat.');
    }

    public function edit(PerformanceReview $performanceReview): View
    {
        $this->authorize('update', $performanceReview);

        return view('performance.reviews.edit', [
            'review' => $performanceReview,
            'statuses' => PerformanceReview::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function update(UpdatePerformanceReviewRequest $request, PerformanceReview $performanceReview): RedirectResponse
    {
        $data = $request->validated();
        $data['kategori'] = PerformanceReview::kategoriFromRating($data['rating'] ?? null);

        $performanceReview->update($data);

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', 'Review kinerja diperbarui.');
    }

    public function destroy(PerformanceReview $performanceReview): RedirectResponse
    {
        $this->authorize('delete', $performanceReview);

        $performanceReview->delete();

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', 'Review kinerja dihapus.');
    }
}
