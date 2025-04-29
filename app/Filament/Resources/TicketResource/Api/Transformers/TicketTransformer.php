<?php
namespace App\Filament\Resources\TicketResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Ticket;
use App\Models\Hotel;

/**
 * @property Ticket $resource
 */
class TicketTransformer extends JsonResource
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
        
        // Check if the ticket is not a hotel ticket
        if ($this->resource->is_hotel_ticket === 0) {
            // Get hotel with ID 0
            $defaultHotel = Hotel::find(0);
            
            // Replace hotel name in the returned data
            if ($defaultHotel) {
                $data['hotel_name'] = $defaultHotel->name;
                
                // If your response includes hotel directly
                if (isset($data['hotel']) && is_array($data['hotel'])) {
                    $data['hotel']['name'] = $defaultHotel->name;
                }
            }
        }
        
        return $data;
    }
}
