<?php

namespace App\Filament\Resources\BoatResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\BoatResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\BoatResource\Api\Transformers\BoatTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = BoatResource::class;


    /**
     * Show Boat
     *
     * @param Request $request
     * @return BoatTransformer
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

        return new BoatTransformer($query);
    }
}
