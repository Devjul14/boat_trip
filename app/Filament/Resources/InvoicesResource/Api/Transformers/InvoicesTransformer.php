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
        
        $hotel = $this->resource->hotel;
        $baseFileName = "invoices_{$hotel->name}_" . date('Y-m-d_His', strtotime($this->resource->created_at));
        $baseFileName = \Illuminate\Support\Str::slug($baseFileName);
        $fileName = $baseFileName . '.pdf';
        

        $data['hotel'] = $hotel->toArray();
        $data['inv_pdf'] = url('storage/pdf/' . $fileName);
        
        return $data;
    }
}
