<?php
namespace App\Filament\Resources\InvoicesResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\InvoicesResource;
use App\Filament\Resources\InvoicesResource\Api\Requests\CreateInvoicesRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = InvoicesResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Invoices
     *
     * @param CreateInvoicesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateInvoicesRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}