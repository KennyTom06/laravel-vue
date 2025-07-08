<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:30',
                'min:2',
                // Allow letters, numbers, spaces, hyphens, apostrophes, and basic punctuation
                // Supports international characters (Unicode)
                'regex:/^[\p{L}\p{N}\s\-\'\.\&]+$/u'
            ],
            'description' => [
                'required',
                'string',
                'max:400',
                'min:10',
                // Allow letters, numbers, spaces, and common punctuation
                // Supports international characters (Unicode)
                'regex:/^[\p{L}\p{N}\s\-\'\.\,\!\?\(\)\&\:\;\"\"\'\']+$/u'
            ],
            'promoter_id' => 'required|integer|exists:promoters,id',
            'wallet_balance' => 'nullable|integer|min:0|max:999999999999', // Add max limit for safety
            'investment_goal' => 'nullable|integer|min:0|max:999999999999', // Add max limit for safety
        ];
    }

    /**
     * Get custom error messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required.',
            'name.min' => 'Project name must be at least 2 characters.',
            'name.max' => 'Project name cannot exceed 30 characters.',
            'name.regex' => 'Project name contains invalid characters. Only letters, numbers, spaces, hyphens, apostrophes, periods, and ampersands are allowed.',
            'description.required' => 'Project description is required.',
            'description.min' => 'Project description must be at least 10 characters.',
            'description.max' => 'Project description cannot exceed 400 characters.',
            'description.regex' => 'Project description contains invalid characters. Only letters, numbers, spaces, and common punctuation are allowed.',
            'promoter_id.required' => 'Promoter is required.',
            'promoter_id.integer' => 'Promoter ID must be a valid number.',
            'promoter_id.exists' => 'Selected promoter does not exist.',
            'wallet_balance.integer' => 'Wallet balance must be a number.',
            'wallet_balance.min' => 'Wallet balance cannot be negative.',
            'wallet_balance.max' => 'Wallet balance is too large.',
            'investment_goal.integer' => 'Investment goal must be a number.',
            'investment_goal.min' => 'Investment goal cannot be negative.',
            'investment_goal.max' => 'Investment goal is too large.',
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'description' => 'project description',
            'promoter_id' => 'promoter',
            'wallet_balance' => 'wallet balance',
            'investment_goal' => 'investment goal',
        ];
    }
} 