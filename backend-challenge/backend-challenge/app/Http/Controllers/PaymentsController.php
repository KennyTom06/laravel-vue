<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use App\Http\Traits\FilterableRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentsController extends Controller
{
    use FilterableRequest;

    /**
     * Display a listing of payments with filtering
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::with('user');
        
        // Apply filters using trait
        $query = $this->applyFilters($request, $query);
        
        $perPage = $this->getPerPage($request);
        $payments = $query->paginate($perPage);
        
        return PaymentResource::collection($payments)
            ->additional($this->formatPaginationResponse($payments));
    }
}
