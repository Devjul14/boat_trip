<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\UserResource\Api\Transformers\UserTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SelfController extends Controller
{
    public function viewSelf(Request $request)
    {
        $user = $request->user();
        return new UserTransformer($user);
    }

    
    public function updateSelf(Request $request)
    {
        $user = $request->user();

        $requestData = $request->except(['role']);
        
        $validatedData = Validator::make($requestData, [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8|confirmed',
        ])->validate();

        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $user->update($validatedData);

        return new UserTransformer($user);
    }
}
