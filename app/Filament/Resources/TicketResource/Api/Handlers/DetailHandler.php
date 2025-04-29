<?php

namespace App\Filament\Resources\TicketResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TicketResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TicketResource\Api\Transformers\TicketTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TicketResource::class;


    /**
     * Show Ticket
     *
     * @param Request $request
     * @return TicketTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new TicketTransformer($query);
    }
}
