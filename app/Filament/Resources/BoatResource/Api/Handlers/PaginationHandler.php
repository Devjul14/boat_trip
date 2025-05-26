<?php
namespace App\Filament\Resources\BoatResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\BoatResource;
use App\Filament\Resources\BoatResource\Api\Transformers\BoatTransformer;

class PaginationHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = BoatResource::class;


    /**
     * List of Boat
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function handler(Request $request)
    {
        $user = $request->user(); 

        $query = static::getEloquentQuery();

        if ($user->hasRole('Boatman')) {
            $query->where('boatman_id', $user->id);
        }

        $query = QueryBuilder::for($query)
        ->allowedFields($this->getAllowedFields() ?? [])
        ->allowedSorts($this->getAllowedSorts() ?? [])
        ->allowedFilters($this->getAllowedFilters() ?? [])
        ->allowedIncludes($this->getAllowedIncludes() ?? [])
        ->paginate(request()->query('per_page'))
        ->appends(request()->query());

        return BoatTransformer::collection($query);
    }
}
