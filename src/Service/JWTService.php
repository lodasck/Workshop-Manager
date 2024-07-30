<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    /**
     * @param array $header
     * @param array $payload
     * @param string $secret
     * @param int $validity
     * @param string
     */
    public function generate(array $header, array $payload, string $secret, int $validity= 10800): string
    {
        if($validity > 0)
        {
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;

            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        # encodes in base 64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        # clean encoded values
        $base64Header = str_replace(['+', '/', '='], ['_', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['_', '_', ''], $base64Payload);

        # generate signature
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret);

        $base64Signature = base64_encode($signature);

        $signature = str_replace(['+', '/', '='], ['+', '/', '='], $base64Signature);

        # create token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
    }

    #check that the token is valid
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    # recovering the paylord
    public function getPayload(string $token): array
    {
        # dismantling the token
        $array = explode('.', $token);

        # decode the payload
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    # recover header
    public function getHeader(string $token): array
    {
        # dismantling the token
        $array = explode('.', $token);

        # decode the header
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    # check if the token has expired
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);

        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    # check the token signature
    public function check(string $token, string $secret)
    {
        # recover header and payload
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        # regenerate a token
        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }
}