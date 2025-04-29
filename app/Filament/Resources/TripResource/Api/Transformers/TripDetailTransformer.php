<?php
namespace App\Filament\Resources\TripResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Trip;

/**
 * @property Trip $resource
 */
class TripDetailTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     * This transformer includes invoice data for detail view
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
        
        // Get invoices and add PDF URL to each
        $invoices = $this->resource->invoices;
        foreach ($invoices as $invoice) {
            $hotel = $invoice->hotel;
            $baseFileName = "invoices_{$hotel->name}_" . date('Y-m-d_His', strtotime($invoice->created_at));
            $baseFileName = \Illuminate\Support\Str::slug($baseFileName);
            $fileName = $baseFileName . '.pdf';
            
            // Add PDF URL to the invoice data
            $invoice->inv_pdf = url('storage/pdf/' . $fileName);
        }
        $data['invoices'] = $invoices;
        
        return $data;
    }
}