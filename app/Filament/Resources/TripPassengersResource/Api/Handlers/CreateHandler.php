<?php
namespace App\Filament\Resources\TripPassengersResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TripPassengersResource;
use App\Filament\Resources\TripPassengersResource\Api\Requests\CreateTripPassengersRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TripPassengersResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create TripPassengers
     *
     * @param CreateTripPassengersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTripPassengersRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}