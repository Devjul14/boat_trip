<?php
namespace App\Filament\Resources\BoatResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\BoatResource;
use Illuminate\Routing\Router;


class BoatApiService extends ApiService
{
    protected static string | null $resource = BoatResource::class;

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
