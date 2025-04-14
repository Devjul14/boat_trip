<?php

namespace App\Filament\Resources\TripResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TripResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TripResource\Api\Transformers\TripTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripResource::class;


    /**
     * Show Trip
     *
     * @param Request $request
     * @return TripTransformer
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

        return new TripTransformer($query);
    }
}
