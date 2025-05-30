<?php
namespace App\Filament\Resources\TripResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Trip;

/**
 * @property Trip $resource
 */
class TripTransformer extends JsonResource
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
