<?php

declare(strict_types=1);

/**
 * Verifica id_tokens de Apple (JWKS + firma RS256) y Google (endpoint tokeninfo).
 * Sin esto, cualquier JWT con el "sub" de otra persona era aceptado sin validar
 * su firma ni su emisor.
 */
class JwtVerifier {
    private static function base64UrlDecode(string $data): string {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($data) ?: '';
    }

    private static function derLength(int $len): string {
        if ($len < 0x80) {
            return chr($len);
        }
        $bytes = '';
        while ($len > 0) {
            $bytes = chr($len & 0xff) . $bytes;
            $len >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function derInteger(string $bin): string {
        // Bit alto en 1 se confundiria con un numero negativo en DER; se prefija con 0x00.
        if ($bin === '' || ord($bin[0]) > 0x7f) {
            $bin = "\x00" . $bin;
        }
        return "\x02" . self::derLength(strlen($bin)) . $bin;
    }

    // Arma un PEM SubjectPublicKeyInfo a partir del modulo/exponente RSA de un JWK.
    // No requiere bcmath/gmp: n y e ya vienen como bytes crudos base64url, solo se
    // envuelven en la estructura ASN.1/DER esperada por openssl.
    private static function jwkToPem(string $nB64, string $eB64): string {
        $n = self::derInteger(self::base64UrlDecode($nB64));
        $e = self::derInteger(self::base64UrlDecode($eB64));
        $seq = "\x30" . self::derLength(strlen($n) + strlen($e)) . $n . $e;

        $rsaOid = hex2bin('300d06092a864886f70d0101010500'); // AlgorithmIdentifier: rsaEncryption
        $bitString = "\x00" . $seq;
        $bitStringDer = "\x03" . self::derLength(strlen($bitString)) . $bitString;
        $spki = "\x30" . self::derLength(strlen($rsaOid) + strlen($bitStringDer)) . $rsaOid . $bitStringDer;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function fetchJson(string $url): ?array {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }
        $json = json_decode($raw, true);
        return is_array($json) ? $json : null;
    }

    private static function verifyRs256Jwt(string $jwt, string $jwksUrl, string $expectedIss, array $allowedAudiences): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        [$headerB64, $payloadB64, $sigB64] = $parts;

        $header = json_decode(self::base64UrlDecode($headerB64), true);
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($header) || !is_array($payload)) {
            return null;
        }
        if (($header['alg'] ?? '') !== 'RS256') {
            return null;
        }

        $jwks = self::fetchJson($jwksUrl);
        if (!$jwks || empty($jwks['keys'])) {
            return null;
        }

        $kid = $header['kid'] ?? null;
        $matchedKey = null;
        foreach ($jwks['keys'] as $key) {
            if (($key['kid'] ?? null) === $kid) {
                $matchedKey = $key;
                break;
            }
        }
        if (!$matchedKey || empty($matchedKey['n']) || empty($matchedKey['e'])) {
            return null;
        }

        $pem = self::jwkToPem($matchedKey['n'], $matchedKey['e']);
        $pubKey = openssl_pkey_get_public($pem);
        if (!$pubKey) {
            return null;
        }

        $signedData = $headerB64 . '.' . $payloadB64;
        $signature = self::base64UrlDecode($sigB64);
        if (openssl_verify($signedData, $signature, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        if (($payload['iss'] ?? null) !== $expectedIss) {
            return null;
        }
        if (empty($payload['exp']) || (int)$payload['exp'] < time()) {
            return null;
        }

        $aud = $payload['aud'] ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (count(array_intersect($audList, $allowedAudiences)) === 0) {
            return null;
        }

        return $payload;
    }

    /**
     * Verifica un id_token de Sign in with Apple contra las claves publicas de Apple.
     * Devuelve el payload verificado (con 'sub', 'email' si viene) o null si es invalido.
     */
    public static function verifyAppleIdToken(string $idToken, array $allowedAudiences): ?array {
        return self::verifyRs256Jwt($idToken, 'https://appleid.apple.com/auth/keys', 'https://appleid.apple.com', $allowedAudiences);
    }

    /**
     * Verifica un id_token de Google usando el endpoint oficial tokeninfo, que valida
     * la firma del lado de Google — evita reimplementar la verificacion JWKS dos veces.
     */
    public static function verifyGoogleIdToken(string $idToken, array $allowedAudiences): ?array {
        $payload = self::fetchJson('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
        if (!$payload || isset($payload['error'])) {
            return null;
        }
        if (empty($payload['exp']) || (int)$payload['exp'] < time()) {
            return null;
        }
        if (!in_array($payload['aud'] ?? null, $allowedAudiences, true)) {
            return null;
        }
        return $payload;
    }
}
