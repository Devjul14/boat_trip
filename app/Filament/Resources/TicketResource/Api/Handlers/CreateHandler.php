<?php
namespace App\Filament\Resources\TicketResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\TicketResource\Api\Requests\CreateTicketRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TicketResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Ticket
     *
     * @param CreateTicketRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTicketRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Ticket");
    }
}