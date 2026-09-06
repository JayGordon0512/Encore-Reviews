<?php

namespace App\Http\Controllers\Api;

use App\Application\Invitations\ProcessMailgunDeliveryEventService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MailgunWebhookController extends Controller
{
    public function __invoke(Request $request, ProcessMailgunDeliveryEventService $processor): JsonResponse
    {
        $outcome = $processor->process(
            $request->all(),
            (string) $request->attributes->get('mailgun_signature_token_digest'),
        );

        return response()->json(['accepted' => true, 'outcome' => $outcome], 202);
    }
}
