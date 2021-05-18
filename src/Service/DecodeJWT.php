<?php

namespace App\Service;

class DecodeJWT
{
    /**
     * @param string $token
     *
     * @return array
     */
    public function decodeJWT(string $token): array
    {
        $response = explode('.', $token);

        return json_decode(base64_decode($response[1]), true);
    }
}
