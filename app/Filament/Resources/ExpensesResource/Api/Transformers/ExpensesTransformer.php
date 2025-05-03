<?php
namespace App\Filament\Resources\ExpensesResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Expenses;

/**
 * @property Expenses $resource
 */
class ExpensesTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
