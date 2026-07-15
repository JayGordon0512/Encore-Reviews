<?php

namespace App\Http\Controllers\Api\TicketPal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketPal\UpsertPerformanceRequest;
use App\Services\TicketPal\PerformanceSyncService;
use Illuminate\Http\JsonResponse;

class PerformanceUpsertController extends Controller
{
    public function __invoke(
        UpsertPerformanceRequest $request,
        PerformanceSyncService $syncService
    ): JsonResponse {
        $result = $syncService->sync($request->validated());
        $performance = $result['performance'];

        return response()->json([
            'ok' => true,
            'created' => $result['created'],
            'performance' => [
                'id' => $performance->id,
                'show_id' => $performance->show_id,
                'venue_id' => $performance->venue_id,
                'status' => $performance->status,
            ],
        ]);
    }
}
