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
        $isBoatmanOwner = $this->resource->boatman_id === auth()->id();
        
        $data = $this->resource->toArray();
        $data['boatman_id'] = $isBoatmanOwner;
        
        $data['trip_type'] = $this->resource->tripType;
        if (isset($data['trip_type']) && isset($data['trip_type']['image'])) {
            $data['trip_type']['image'] = url('storage/' . $data['trip_type']['image']);
        }
        $data['boat'] = $this->resource->boat;
        $data['boatman'] = $this->resource->boatman;
        $data['tickets'] = $this->resource->ticket;
        
        return $data;
    }
}
