<?php

namespace App\Filament\Resources\TripTypeResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\TripType $resource
 */
class TripTypeTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'image' => $this->image, // if you want to include image path
            'expenses' => $this->expenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'name' => $expense->name,
                ];
            }),
        ];
    }
}
