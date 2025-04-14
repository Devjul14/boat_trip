<?php
namespace App\Filament\Resources\BoatResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\BoatResource;
use App\Filament\Resources\BoatResource\Api\Requests\CreateBoatRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = BoatResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Boat
     *
     * @param CreateBoatRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateBoatRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}