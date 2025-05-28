<?php
namespace App\Filament\Resources\InvoicesResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Invoices;

/**
 * @property Invoices $resource
 */
class InvoicesTransformer extends JsonResource
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

    // Related hotel
    $hotel = $this->resource->hotel;
    $baseFileName = "{$hotel->name}" . date('YmdHis', strtotime($this->resource->created_at));
    $baseFileName = \Illuminate\Support\Str::slug($baseFileName);
    $fileName = $baseFileName . '.pdf';

    // Attach hotel data
    $data['hotel'] = $hotel->toArray();
    $data['inv_pdf'] = url('storage/pdf/' . $fileName);

    // Get tickets where hotel_id and trip_id match this invoice
    $tickets = \App\Models\Ticket::where('hotel_id', $this->resource->hotel_id)
                ->where('trip_id', $this->resource->trip_id)
                ->get()
                ->toArray();

    $data['tickets'] = $tickets;

    return $data;
}

}
