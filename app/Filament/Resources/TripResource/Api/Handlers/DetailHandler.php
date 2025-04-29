<?php

namespace App\Filament\Resources\TripResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TripResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TripResource\Api\Transformers\TripDetailTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripResource::class;


    /**
     * Show Trip with invoice details
     *
     * @param Request $request
     * @return TripDetailTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->with('invoices')
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new TripDetailTransformer($query);
    }
}