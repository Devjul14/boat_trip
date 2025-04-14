<?php
namespace App\Filament\Resources\TripTypeResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TripType;

/**
 * @property TripType $resource
 */
class TripTypeTransformer extends JsonResource
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
