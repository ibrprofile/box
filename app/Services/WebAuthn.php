<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Самодостаточная реализация WebAuthn (Passkey) для регистрации и входа.
 * Поддерживаются ключи ES256 (P-256) и RS256 — этого достаточно для
 * подавляющего большинства платформенных аутентификаторов (Face ID, Touch ID,
 * Windows Hello, Android).
 *
 * Проверка attestation умышленно не выполняется ("none"): для входа на сайт
 * достаточно доказать владение приватным ключом. Так делает большинство
 * потребительских сервисов.
 */
final class WebAuthn
{
    public function __construct(
        private string $rpId,
        private string $rpName,
        private string $origin
    ) {}

    /* --- Опции регистрации ------------------------------------------- */
    public function registrationOptions(int $userId, string $email, string $displayName, array $excludeIds = []): array
    {
        $challenge = $this->randomChallenge();
        $_SESSION['webauthn_challenge'] = $challenge;

        return [
            'challenge' => $this->b64url($challenge),
            'rp'        => ['id' => $this->rpId, 'name' => $this->rpName],
            'user'      => [
                'id'          => $this->b64url((string) $userId),
                'name'        => $email,
                'displayName' => $displayName,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],    // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'timeout'     => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'residentKey'      => 'preferred',
                'userVerification'  => 'preferred',
            ],
            'excludeCredentials' => array_map(fn ($id) => [
                'type' => 'public-key',
                'id'   => $id,
            ], $excludeIds),
        ];
    }

    /**
     * Проверяет ответ создания ключа и извлекает credentialId + публичный ключ.
     * @return array{credentialId:string, publicKey:string, signCount:int}
     */
    public function verifyRegistration(string $clientDataJSON, string $attestationObject): array
    {
        $clientData = $this->verifyClientData($clientDataJSON, 'webauthn.create');

        $attestation = $this->cborDecode($this->b64urlDecode($attestationObject));
        if (!isset($attestation['authData'])) {
            throw new \RuntimeException('Некорректный attestationObject');
        }

        $parsed = $this->parseAuthData($attestation['authData']);
        if ($parsed['credentialId'] === null || $parsed['publicKey'] === null) {
            throw new \RuntimeException('В ответе нет данных ключа');
        }

        return [
            'credentialId' => $this->b64url($parsed['credentialId']),
            'publicKey'    => $this->coseToPem($parsed['publicKey']),
            'signCount'    => $parsed['signCount'],
        ];
    }

    /* --- Опции входа -------------------------------------------------- */
    public function loginOptions(array $allowIds = []): array
    {
        $challenge = $this->randomChallenge();
        $_SESSION['webauthn_challenge'] = $challenge;

        return [
            'challenge'        => $this->b64url($challenge),
            'rpId'             => $this->rpId,
            'timeout'          => 60000,
            'userVerification'  => 'preferred',
            'allowCredentials' => array_map(fn ($id) => [
                'type' => 'public-key',
                'id'   => $id,
            ], $allowIds),
        ];
    }

    /** Проверяет assertion (вход): подпись над authenticatorData + hash(clientData). */
    public function verifyLogin(string $clientDataJSON, string $authenticatorData, string $signature, string $publicKeyPem): bool
    {
        $this->verifyClientData($clientDataJSON, 'webauthn.get');

        $authData = $this->b64urlDecode($authenticatorData);
        $this->assertRpIdHash(substr($authData, 0, 32));

        $clientHash = hash('sha256', $this->b64urlDecode($clientDataJSON), true);
        $signed = $authData . $clientHash;
        $sig = $this->b64urlDecode($signature);

        $ok = openssl_verify($signed, $sig, $publicKeyPem, OPENSSL_ALGO_SHA256);
        return $ok === 1;
    }

    /* --- Общие проверки ---------------------------------------------- */
    private function verifyClientData(string $clientDataJSON, string $expectedType): array
    {
        $json = json_decode($this->b64urlDecode($clientDataJSON), true);
        if (!is_array($json)) {
            throw new \RuntimeException('Некорректный clientDataJSON');
        }
        if (($json['type'] ?? '') !== $expectedType) {
            throw new \RuntimeException('Неверный тип операции');
        }
        if (!hash_equals($_SESSION['webauthn_challenge'] ?? '', $this->b64urlDecode($json['challenge'] ?? ''))) {
            throw new \RuntimeException('Неверный challenge');
        }
        if (!hash_equals($this->origin, $json['origin'] ?? '')) {
            throw new \RuntimeException('Неверный origin');
        }
        unset($_SESSION['webauthn_challenge']);
        return $json;
    }

    private function assertRpIdHash(string $hash): void
    {
        if (!hash_equals(hash('sha256', $this->rpId, true), $hash)) {
            throw new \RuntimeException('Неверный rpId');
        }
    }

    /* --- Разбор authenticatorData ------------------------------------ */
    private function parseAuthData(string $authData): array
    {
        $signCount = unpack('N', substr($authData, 33, 4))[1];
        $flags = ord($authData[32]);
        $result = ['signCount' => $signCount, 'credentialId' => null, 'publicKey' => null];

        // Бит 6 (0x40) — присутствуют attested credential data
        if (($flags & 0x40) === 0) {
            return $result;
        }

        $offset = 37 + 16; // rpIdHash(32)+flags(1)+signCount(4)+aaguid(16)
        $credLen = unpack('n', substr($authData, $offset, 2))[1];
        $offset += 2;
        $result['credentialId'] = substr($authData, $offset, $credLen);
        $offset += $credLen;

        $result['publicKey'] = $this->cborDecode(substr($authData, $offset));
        return $result;
    }

    /* --- COSE -> PEM ------------------------------------------------- */
    private function coseToPem(array $cose): string
    {
        $kty = $cose[1] ?? null;

        if ($kty === 2) { // EC2 (ES256, P-256)
            $x = $cose[-2];
            $y = $cose[-3];
            $der = $this->ecDer($x, $y);
            return $this->derToPem($der);
        }

        if ($kty === 3) { // RSA (RS256)
            $n = $cose[-1];
            $e = $cose[-2];
            $der = $this->rsaDer($n, $e);
            return $this->derToPem($der);
        }

        throw new \RuntimeException('Неподдерживаемый тип ключа');
    }

    private function ecDer(string $x, string $y): string
    {
        // SubjectPublicKeyInfo для EC P-256, uncompressed point 0x04 || X || Y
        $point = "\x04" . $x . $y;
        $algo = $this->seq(
            $this->oid("\x2a\x86\x48\xce\x3d\x02\x01") .          // id-ecPublicKey
            $this->oid("\x2a\x86\x48\xce\x3d\x03\x01\x07")        // prime256v1
        );
        $bitString = "\x03" . $this->len(strlen($point) + 1) . "\x00" . $point;
        return $this->seq($algo . $bitString);
    }

    private function rsaDer(string $n, string $e): string
    {
        $rsaKey = $this->seq($this->uint($n) . $this->uint($e));
        $algo = $this->seq(
            $this->oid("\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01") .  // rsaEncryption
            "\x05\x00"                                            // NULL
        );
        $bitString = "\x03" . $this->len(strlen($rsaKey) + 1) . "\x00" . $rsaKey;
        return $this->seq($algo . $bitString);
    }

    /* --- Минимальный DER-энкодер ------------------------------------- */
    private function seq(string $content): string
    {
        return "\x30" . $this->len(strlen($content)) . $content;
    }

    private function oid(string $bytes): string
    {
        return "\x06" . $this->len(strlen($bytes)) . $bytes;
    }

    private function uint(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00" . $bytes; // ведущий ноль, чтобы число оставалось положительным
        }
        return "\x02" . $this->len(strlen($bytes)) . $bytes;
    }

    private function len(int $n): string
    {
        if ($n < 0x80) {
            return chr($n);
        }
        $bytes = '';
        while ($n > 0) {
            $bytes = chr($n & 0xff) . $bytes;
            $n >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function derToPem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /* --- Минимальный CBOR-декодер ------------------------------------ */
    private function cborDecode(string $data): mixed
    {
        $offset = 0;
        $value = $this->cborValue($data, $offset);
        return $value;
    }

    private function cborValue(string $data, int &$offset): mixed
    {
        $byte = ord($data[$offset++]);
        $major = $byte >> 5;
        $info = $byte & 0x1f;
        $length = $this->cborLength($data, $offset, $info);

        switch ($major) {
            case 0: return $length;              // unsigned int
            case 1: return -1 - $length;         // negative int
            case 2:                              // byte string
                $out = substr($data, $offset, $length);
                $offset += $length;
                return $out;
            case 3:                              // text string
                $out = substr($data, $offset, $length);
                $offset += $length;
                return $out;
            case 4:                              // array
                $arr = [];
                for ($i = 0; $i < $length; $i++) {
                    $arr[] = $this->cborValue($data, $offset);
                }
                return $arr;
            case 5:                              // map
                $map = [];
                for ($i = 0; $i < $length; $i++) {
                    $key = $this->cborValue($data, $offset);
                    $map[$key] = $this->cborValue($data, $offset);
                }
                return $map;
            default:
                throw new \RuntimeException('Неподдерживаемый CBOR тип');
        }
    }

    private function cborLength(string $data, int &$offset, int $info): int
    {
        if ($info < 24) {
            return $info;
        }
        $bytes = match ($info) {
            24 => 1,
            25 => 2,
            26 => 4,
            27 => 8,
            default => throw new \RuntimeException('Некорректная длина CBOR'),
        };
        $value = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $value = ($value << 8) | ord($data[$offset++]);
        }
        return $value;
    }

    /* --- Base64url --------------------------------------------------- */
    private function randomChallenge(): string
    {
        return random_bytes(32);
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($data) ?: '';
    }
}
