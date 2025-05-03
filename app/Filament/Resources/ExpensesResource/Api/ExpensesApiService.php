<?php
namespace App\Filament\Resources\ExpensesResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\ExpensesResource;
use Illuminate\Routing\Router;


class ExpensesApiService extends ApiService
{
    protected static string | null $resource = ExpensesResource::class;

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
