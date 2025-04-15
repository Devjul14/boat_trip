<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class PhoneUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || 
            (!isset($credentials['phone']) && !isset($credentials['email']))) {
            return null;
        }

        // Prioritize phone login
        if (isset($credentials['phone'])) {
            return $this->createModel()->where('phone', $credentials['phone'])->first();
        }

        // Fall back to email login
        return parent::retrieveByCredentials($credentials);
    }
}