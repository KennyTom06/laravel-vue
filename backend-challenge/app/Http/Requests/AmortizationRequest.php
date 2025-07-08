<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmortizationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0',
            'state' => 'sometimes|string|in:pending,paid',
            'schedule_date' => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'state.in' => 'State must be either pending or paid.',
            'schedule_date.required' => 'Schedule date is required.',
            'schedule_date.date' => 'Schedule date must be a valid date.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->replace($this->except(['promoter_id', 'project_id']));
    }
}
