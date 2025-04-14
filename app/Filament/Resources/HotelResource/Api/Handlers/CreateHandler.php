<?php
namespace App\Filament\Resources\HotelResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\HotelResource;
use App\Filament\Resources\HotelResource\Api\Requests\CreateHotelRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = HotelResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Hotel
     *
     * @param CreateHotelRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateHotelRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}