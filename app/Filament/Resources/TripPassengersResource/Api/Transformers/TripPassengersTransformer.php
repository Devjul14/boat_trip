<?php
namespace App\Filament\Resources\TripPassengersResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TripPassengers;

/**
 * @property TripPassengers $resource
 */
class TripPassengersTransformer extends JsonResource
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
