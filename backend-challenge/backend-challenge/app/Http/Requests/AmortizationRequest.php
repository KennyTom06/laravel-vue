<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmortizationRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:999999999999' // Add reasonable max limit
            ],
            'state' => [
                'sometimes',
                'string',
                'in:pending,paid'
            ],
            'schedule_date' => [
                'required',
                'date',
                'after_or_equal:today' // Prevent scheduling in the past for new amortizations
            ],
            // These fields are protected but we add validation for completeness
            'promoter_id' => 'sometimes|integer|exists:promoters,id',
            'project_id' => 'sometimes|integer|exists:projects,id',
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
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than zero.',
            'amount.max' => 'Amount is too large.',
            'state.in' => 'State must be either pending or paid.',
            'schedule_date.required' => 'Schedule date is required.',
            'schedule_date.date' => 'Schedule date must be a valid date.',
            'schedule_date.after_or_equal' => 'Schedule date cannot be in the past.',
            'promoter_id.integer' => 'Promoter ID must be a valid number.',
            'promoter_id.exists' => 'Selected promoter does not exist.',
            'project_id.integer' => 'Project ID must be a valid number.',
            'project_id.exists' => 'Selected project does not exist.',
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
            'amount' => 'amount',
            'state' => 'state',
            'schedule_date' => 'schedule date',
            'promoter_id' => 'promoter',
            'project_id' => 'project',
        ];
    }

    /**
     * Prepare the data for validation by removing protected fields
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Remove promoter_id and project_id from validation to prevent unauthorized updates
        // These fields are protected and should not be modifiable after creation
        $this->replace($this->except(['promoter_id', 'project_id']));
    }
} 