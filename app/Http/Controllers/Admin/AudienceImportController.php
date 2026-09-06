<?php

namespace App\Http\Controllers\Admin;

use App\Application\Audience\ImportOrganiserAudienceService;
use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AudienceImportController extends Controller
{
    public function template(): Response
    {
        return response("name,email\nAlex Morgan,alex@example.com\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="encore-customer-import.csv"',
        ]);
    }

    public function store(Request $request, Show $show, ImportOrganiserAudienceService $importer): RedirectResponse
    {
        ManualEventController::authorizeOwnedManualEvent($request, $show);
        $validated = $request->validate([
            'performance_id' => ['required', 'uuid'],
            'customers_csv' => ['required', 'file', 'max:2048'],
            'attendance_confirmed' => ['accepted'],
        ]);
        $performance = $show->performances()
            ->whereNotIn('status', ['cancelled', 'archived', 'deleted'])
            ->findOrFail($validated['performance_id']);

        $audienceImport = $importer->import(
            $request->user(),
            $show,
            $performance,
            $validated['customers_csv'],
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with(
            'status',
            "Imported {$audienceImport->rows_imported} customer(s); skipped {$audienceImport->rows_skipped} invalid or duplicate row(s).",
        );
    }
}
