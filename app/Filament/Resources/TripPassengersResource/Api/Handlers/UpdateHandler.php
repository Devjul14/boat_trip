<?php
namespace App\Filament\Resources\TripPassengersResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TripPassengersResource;
use App\Filament\Resources\TripPassengersResource\Api\Requests\UpdateTripPassengersRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripPassengersResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update TripPassengers
     *
     * @param UpdateTripPassengersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateTripPassengersRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}