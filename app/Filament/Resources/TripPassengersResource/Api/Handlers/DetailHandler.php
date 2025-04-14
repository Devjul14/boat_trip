<?php

namespace App\Filament\Resources\TripPassengersResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TripPassengersResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TripPassengersResource\Api\Transformers\TripPassengersTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripPassengersResource::class;


    /**
     * Show TripPassengers
     *
     * @param Request $request
     * @return TripPassengersTransformer
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

        return new TripPassengersTransformer($query);
    }
}
