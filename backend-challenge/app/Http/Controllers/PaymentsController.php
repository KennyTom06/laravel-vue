<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user');
        
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
        
        if ($request->has('user') && $request->input('user') !== '') {
            $query->where('user_id', $request->input('user'));
        }
        
        $perPage = $request->input('per_page', 20);
        $payments = $query->paginate($perPage);
        
        return PaymentResource::collection($payments)->additional([
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }
}
