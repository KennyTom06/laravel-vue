<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use Illuminate\Http\Request;

class PromotersController extends Controller
{
    public function index()
    {
        $promoters = Promoter::with('user')->get();
        return response()->json([
            'data' => $promoters->map(function ($promoter) {
                return [
                    'id' => $promoter->id,
                    'name' => $promoter->user->name ?? 'Unknown',
                    'email' => $promoter->user->email ?? 'Unknown'
                ];
            })
        ]);
    }
} 