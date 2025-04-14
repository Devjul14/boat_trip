<?php
namespace App\Filament\Resources\BoatResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Boat;

/**
 * @property Boat $resource
 */
class BoatTransformer extends JsonResource
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
