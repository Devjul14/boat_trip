<?php
namespace App\Filament\Resources\InvoicesResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\InvoicesResource;
use Illuminate\Routing\Router;


class InvoicesApiService extends ApiService
{
    protected static string | null $resource = InvoicesResource::class;

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
