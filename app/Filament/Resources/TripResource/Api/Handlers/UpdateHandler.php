<?php
namespace App\Filament\Resources\TripResource\Api\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripResource\Api\Requests\UpdateTripRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = TripResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update Trip
     *
     * @param UpdateTripRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateTripRequest $request)
    {
        $id = $request->route('id');
    
        $model = static::getModel()::find($id);
        
        if (!$model) return static::sendNotFoundResponse();
        
        $data = $request->all();
        $data['boatman_id'] = Auth::id();
        $model->fill($data);
        
        $model->save();        
        return static::sendSuccessResponse($model, "Successfully Update Trip");
    }
}