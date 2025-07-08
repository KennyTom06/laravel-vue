<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\ProjectRequest;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();
        
        // Filter by name
        if ($request->has('name') && $request->input('name') !== '') {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        $perPage = $request->input('per_page', 20);
        $projects = $query->paginate($perPage);
        
        return response()->json([
            'data' => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ]
        ]);
    }

    public function store(ProjectRequest $request)
    {
        try {
            $data = $request->validated();
            // Set default values if not provided
            $data['wallet_balance'] = $data['wallet_balance'] ?? 0;
            $data['investment_goal'] = $data['investment_goal'] ?? 0;
            
            $project = Project::create($data);
            return response()->json([
                'message' => 'Project created successfully',
                'data' => $project
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating project: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(ProjectRequest $request, Project $project)
    {
        try {
            $data = $request->validated();
            // Set default values if not provided
            $data['wallet_balance'] = $data['wallet_balance'] ?? 0;
            $data['investment_goal'] = $data['investment_goal'] ?? 0;
            
            $project->update($data);
            return response()->json([
                'message' => 'Project updated successfully',
                'data' => $project
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating project: ' . $e->getMessage()
            ], 500);
        }
    }
} 
