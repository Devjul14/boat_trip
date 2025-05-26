<?php
namespace App\Filament\Resources\TripTypeResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\TripTypeResource;
use Illuminate\Routing\Router;


class TripTypeApiService extends ApiService
{
    protected static string | null $resource = TripTypeResource::class;

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
