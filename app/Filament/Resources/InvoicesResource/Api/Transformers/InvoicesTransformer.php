<?php
namespace App\Filament\Resources\InvoicesResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Invoices;
use App\Models\Ticket;

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

        // Tambahkan link view invoice
        $data['inv_pdf'] = route('invoices.view', ['invoice' => $this->resource->id]);

        // Attach hotel data
        $hotel = $this->resource->hotel;
        $data['hotel'] = $hotel ? $hotel->toArray() : null;

        // Get tickets where hotel_id and trip_id match this invoice
        $tickets = \App\Models\Ticket::where('hotel_id', $this->resource->hotel_id)
                    ->where('trip_id', $this->resource->trip_id)
                    ->get()
                    ->toArray();

        $data['tickets'] = $tickets;

        return $data;
    }


}
