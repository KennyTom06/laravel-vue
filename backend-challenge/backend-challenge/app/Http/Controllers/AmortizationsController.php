<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Amortization;
use App\Http\Resources\AmortizationResource;
use App\Http\Requests\AmortizationRequest;
use App\Http\Traits\FilterableRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AmortizationsController extends Controller
{
    use FilterableRequest;

    /**
     * Display a listing of amortizations with filtering
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Amortization::with(['project', 'promoter.user']);
        
        // Apply filters using trait
        $query = $this->applyFilters($request, $query);
        
        $perPage = $this->getPerPage($request);
        $amortizations = $query->paginate($perPage);
        
        return AmortizationResource::collection($amortizations)
            ->additional($this->formatPaginationResponse($amortizations));
    }

    /**
     * Display the specified amortization
     *
     * @param int $id
     * @return AmortizationResource
     */
    public function show(int $id): AmortizationResource
    {
        $amortization = Amortization::with(['project', 'promoter.user'])->findOrFail($id);
        return new AmortizationResource($amortization);
    }

    /**
     * Update the specified amortization
     *
     * @param AmortizationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(AmortizationRequest $request, int $id): JsonResponse
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
        
        $message = 'Amortization updated successfully.';
        $warnings = [];
        
        if ($attemptedProjectId && $attemptedProjectId != $oldProjectId) {
            $warnings[] = 'Project ID change was ignored for security.';
        }
        if ($attemptedPromoterId && $attemptedPromoterId != $oldPromoterId) {
            $warnings[] = 'Promoter ID change was ignored for security.';
        }
        
        if (!empty($warnings)) {
            $message .= ' ' . implode(' ', $warnings);
        }
        
        return response()->json([
            'message' => $message,
            'data' => new AmortizationResource($amortization->fresh(['project', 'promoter.user']))
        ]);
    }
}
