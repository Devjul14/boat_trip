<?php

namespace App\Filament\Resources\ExpensesResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ExpensesResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\ExpensesResource\Api\Transformers\ExpensesTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = ExpensesResource::class;


    /**
     * Show Expenses
     *
     * @param Request $request
     * @return ExpensesTransformer
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

        return new ExpensesTransformer($query);
    }
}
