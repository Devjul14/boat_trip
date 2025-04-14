<?php
namespace App\Filament\Resources\HotelResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Hotel;

/**
 * @property Hotel $resource
 */
class HotelTransformer extends JsonResource
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
