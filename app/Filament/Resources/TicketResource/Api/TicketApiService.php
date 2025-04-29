<?php
namespace App\Filament\Resources\TicketResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\TicketResource;
use Illuminate\Routing\Router;


class TicketApiService extends ApiService
{
    protected static string | null $resource = TicketResource::class;

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
