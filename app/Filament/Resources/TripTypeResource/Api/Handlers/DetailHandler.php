<?php

namespace App\Filament\Resources\TripTypeResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TripTypeResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TripTypeResource\Api\Transformers\TripTypeTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripTypeResource::class;


    /**
     * Show TripType
     *
     * @param Request $request
     * @return TripTypeTransformer
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

        return new TripTypeTransformer($query);
    }
}
