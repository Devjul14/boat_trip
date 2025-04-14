<?php
namespace App\Filament\Resources\TripPassengersResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\TripPassengersResource;
use Illuminate\Routing\Router;


class TripPassengersApiService extends ApiService
{
    protected static string | null $resource = TripPassengersResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
