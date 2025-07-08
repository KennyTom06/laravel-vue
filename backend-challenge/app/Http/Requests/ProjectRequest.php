<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:30|regex:/^[a-zA-Z0-9\s]+$/',
            'description' => 'required|string|max:400|regex:/^[a-zA-Z0-9\s.,!?-]+$/',
            'promoter_id' => 'required|exists:promoters,id',
            'wallet_balance' => 'nullable|integer|min:0',
            'investment_goal' => 'nullable|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Project name is required.',
            'name.max' => 'Project name cannot exceed 30 characters.',
            'name.regex' => 'Project name cannot contain special characters.',
            'description.required' => 'Project description is required.',
            'description.max' => 'Project description cannot exceed 400 characters.',
            'description.regex' => 'Project description cannot contain special characters.',
            'promoter_id.required' => 'Promoter is required.',
            'promoter_id.exists' => 'Selected promoter does not exist.',
            'wallet_balance.integer' => 'Wallet balance must be a number.',
            'wallet_balance.min' => 'Wallet balance cannot be negative.',
            'investment_goal.integer' => 'Investment goal must be a number.',
            'investment_goal.min' => 'Investment goal cannot be negative.',
        ];
    }
} 
