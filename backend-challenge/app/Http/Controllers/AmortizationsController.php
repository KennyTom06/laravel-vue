<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Amortization;
use App\Http\Resources\AmortizationResource;
use App\Http\Requests\AmortizationRequest;

class AmortizationsController extends Controller
{
    public function index(Request $request)
    {
        $query = Amortization::with(['project', 'promoter.user']);

        // Filter by state
        if ($request->has('state') && $request->input('state') !== '') {
            $query->where('state', $request->input('state'));
        }

        // Filter by amount range
        if ($request->has('amount_from') && $request->input('amount_from') !== '') {
            $query->where('amount', '>=', $request->input('amount_from'));
        }

        if ($request->has('amount_to') && $request->input('amount_to') !== '') {
            $query->where('amount', '<=', $request->input('amount_to'));
        }

        // Filter by exact amount (backward compatibility)
        if ($request->has('amount') && $request->input('amount') !== '') {
            $query->where('amount', $request->input('amount'));
        }

        $perPage = $request->input('per_page', 20);
        $amortizations = $query->paginate($perPage);

        return AmortizationResource::collection($amortizations)->additional([
            'pagination' => [
                'current_page' => $amortizations->currentPage(),
                'last_page' => $amortizations->lastPage(),
                'per_page' => $amortizations->perPage(),
                'total' => $amortizations->total(),
                'from' => $amortizations->firstItem(),
                'to' => $amortizations->lastItem(),
            ]
        ]);
    }

    public function show($id)
    {
        $amortization = Amortization::with(['project', 'promoter.user'])->findOrFail($id);
        return new AmortizationResource($amortization);
    }

    public function update(AmortizationRequest $request, $id)
    {
        $amortization = Amortization::findOrFail($id);

        $oldProjectId = $amortization->project_id;
        $oldPromoterId = $amortization->promoter_id;

        $amortization->update([
            'amount' => $request->amount,
            'state' => $request->state,
            'schedule_date' => $request->schedule_date,
        ]);

        $attemptedProjectId = $request->input('project_id');
        $attemptedPromoterId = $request->input('promoter_id');

        $message = 'Amortization updated successfully. ';
        if ($attemptedProjectId && $attemptedProjectId != $oldProjectId) {
            $message .= 'Project ID change was ignored for security. ';
        }
        if ($attemptedPromoterId && $attemptedPromoterId != $oldPromoterId) {
            $message .= 'Promoter ID change was ignored for security. ';
        }

        return response()->json([
            'message' => $message,
            'data' => new AmortizationResource($amortization)
        ]);
    }
}
