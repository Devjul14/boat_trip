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
        $data = $this->resource->toArray();
        $data['boatman_id'] = $this->resource->boatman_id;
        
        $data['trip_type'] = $this->resource->tripType;
        if (isset($data['trip_type']) && isset($data['trip_type']['image'])) {
            $data['trip_type']['image'] = url('storage/' . $data['trip_type']['image']);
        }
        $data['boat'] = $this->resource->boat;
        $data['boatman'] = $this->resource->boatman;
        $data['tickets'] = $this->resource->ticket;
        
        $invoices = $this->resource->invoices;
        foreach ($invoices as $invoice) {
            $hotel = $invoice->hotel;
            $invoice->inv_pdf = route('invoices.view', ['invoice' => $invoice->id]);
        }
        $data['invoices'] = $invoices;
        return $data;
    }
}
