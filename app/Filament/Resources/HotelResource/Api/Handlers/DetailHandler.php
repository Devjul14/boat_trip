<?php

namespace App\Filament\Resources\HotelResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\HotelResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\HotelResource\Api\Transformers\HotelTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = HotelResource::class;


    /**
     * Show Hotel
     *
     * @param Request $request
     * @return HotelTransformer
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

        return new HotelTransformer($query);
    }
}
