<?php

namespace App\Services\Gateways;

class OniPay {
    public static function getDetails(): array {
        return[
            'name' => 'Oni Pay',
            'fields' =>['api_key' => 'API Key'],
        ];
    }
}