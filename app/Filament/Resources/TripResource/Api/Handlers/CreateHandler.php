<?php
namespace App\Filament\Resources\TripResource\Api\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripResource\Api\Requests\CreateTripRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TripResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Trip
     *
     * @param CreateTripRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTripRequest $request)
    {
        $model = new (static::getModel());
        $data = $request->all();
        $data['boatman_id'] = auth('api')->id();
        $model->fill($data);

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Trip");
    }
}