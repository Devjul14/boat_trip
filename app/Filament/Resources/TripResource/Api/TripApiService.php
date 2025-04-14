<?php
namespace App\Filament\Resources\TripResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\TripResource;
use Illuminate\Routing\Router;


class TripApiService extends ApiService
{
    protected static string | null $resource = TripResource::class;

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
