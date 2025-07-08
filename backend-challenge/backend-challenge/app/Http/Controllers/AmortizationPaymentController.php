<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AmortizationPaymentService;
use Illuminate\Support\Facades\Validator;

class AmortizationPaymentController extends Controller
{
    public function pay(Request $request, AmortizationPaymentService $service)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $date = $request->input('date') ?? now()->toDateString();
        $service->payAmortizations($date);
        return response()->json(['message' => 'Payments processed for amortizations scheduled on or before ' . $date]);
    }
} 