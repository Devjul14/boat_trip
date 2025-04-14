<?php
namespace App\Filament\Resources\TripTypeResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TripTypeResource;
use App\Filament\Resources\TripTypeResource\Api\Requests\CreateTripTypeRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TripTypeResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create TripType
     *
     * @param CreateTripTypeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTripTypeRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}