<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmortizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'state' => $this->state,
            'schedule_date' => $this->schedule_date,
            'project_id' => $this->project_id,
            'promoter_id' => $this->promoter_id,
            'project' => [
                'id' => $this->project->id ?? null,
                'name' => $this->project->name ?? null,
                'wallet_balance' => $this->project->wallet_balance ?? null,
            ],
            'promoter' => [
                'id' => $this->promoter->id ?? null,
                'name' => $this->promoter->user->name ?? null,
                'email' => $this->promoter->user->email ?? null,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
