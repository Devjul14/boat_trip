<?php
namespace App\Filament\Resources\HotelResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\HotelResource;
use Illuminate\Routing\Router;


class HotelApiService extends ApiService
{
    protected static string | null $resource = HotelResource::class;

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
