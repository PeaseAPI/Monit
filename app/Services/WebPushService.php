<?php

namespace App\Services;

use App\Models\PushNotificationSubscriber;
use App\Support\PluginManager;

/**
 * Web Push 推送服务（纯 PHP 实现，规格书 §14.5）
 * - VAPID 鉴权：RFC 8292（ES256 JWT，openssl 签名）
 * - 载荷加密：RFC 8291 + RFC 8188 aes128gcm（ECDH P-256 + HKDF + AES-128-GCM）
 * 无任何外部 Composer 依赖。
 */
class WebPushService
{
    /** 最近一次发送的结果状态 */
    public array $lastResults = [];

    /**
     * 向单个订阅发送推送（活动营销批量任务使用，规格书 §14.5）
     * VAPID 密钥取自 push-notifications 插件设置。
     */
    public function sendOne(PushNotificationSubscriber $subscriber, string $title, string $body, string $url = '/'): bool
    {
        return $this->send(
            (string) $subscriber->endpoint,
            (string) $subscriber->keys_p256dh,
            (string) $subscriber->keys_auth,
            ['title' => $title, 'body' => $body, 'url' => $url],
            (string) PluginManager::setting('push-notifications', 'vapid_public_key', ''),
            (string) PluginManager::setting('push-notifications', 'vapid_private_key', ''),
        );
    }

    /**
     * 向单个订阅者发送推送
     *
     * @param  array  $payload  {title, body, url, icon}
     * @return bool 发送是否成功（404/410 表示订阅已失效可删除）
     */
    public function send(
        string $endpoint,
        string $p256dhB64,
        string $authB64,
        array $payload,
        string $vapidPublicKey,
        string $vapidPrivateKey,
        string $subject = 'mailto:admin@example.com',
    ): bool {
        $payloadJson = json_encode([
            'title' => $payload['title'] ?? '',
            'body' => $payload['body'] ?? '',
            'url' => $payload['url'] ?? '/',
            'icon' => $payload['icon'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ciphertext = $this->encryptPayload($payloadJson, $p256dhB64, $authB64);
        $vapid = $this->buildVapidHeader($endpoint, $subject, $vapidPublicKey, $vapidPrivateKey);

        [$status] = $this->post($endpoint, $ciphertext, [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Urgency: normal',
            'Authorization: '.$vapid,
        ]);

        $this->lastResults = ['status' => $status, 'expired' => in_array($status, [404, 410], true)];

        return $status >= 200 && $status < 300;
    }

    /* ---------------- RFC 8291 / 8188：aes128gcm 载荷加密 ---------------- */

    protected function encryptPayload(string $plaintext, string $p256dhB64, string $authB64): string
    {
        $uaPublic = $this->b64urlDecode($p256dhB64);   // 65 字节 04||X||Y
        $authSecret = $this->b64urlDecode($authB64);    // 16 字节

        // 1. 应用服务器临时密钥对（P-256）
        $asPrivate = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $asPublicRaw = $this->publicKeyToRaw($asPrivate);

        // 2. ECDH 共享密钥
        $uaPublicDer = $this->rawPublicToDer($uaPublic);
        $sharedSecret = openssl_pkey_derive($uaPublicDer, $asPrivate, 32);

        // 3. IKM = HKDF(salt=auth, ikm=shared, info="WebPush: info\0"||ua||as)
        $ikm = $this->hkdf($authSecret, $sharedSecret, 'WebPush: info'."\0".$uaPublic.$asPublicRaw, 32);

        // 4. 派生 CEK(16) / NONCE(12)
        $salt = random_bytes(16);
        $cek = $this->hkdf($salt, $ikm, 'Content-Encoding: aes128gcm'."\0".$uaPublic.$asPublicRaw, 16);
        $nonce = $this->hkdf($salt, $ikm, 'Content-Encoding: nonce'."\0".$uaPublic.$asPublicRaw, 12);

        // 5. AES-128-GCM（aes128gcm padding：明文后接 0x02 定界符）
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        // 6. 拼装：salt(16) || rs(4=4096) || idlen(1=65) || asPublic || ciphertext+tag
        return $salt.pack('N', 4096).chr(strlen($asPublicRaw)).$asPublicRaw.$ciphertext.$tag;
    }

    /* ---------------- RFC 8292：VAPID ES256 JWT ---------------- */

    protected function buildVapidHeader(string $endpoint, string $subject, string $publicKeyB64, string $privateKeyB64): string
    {
        $parts = parse_url($endpoint);
        $origin = $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '');

        $header = $this->b64urlEncode((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = $this->b64urlEncode((string) json_encode([
            'aud' => $origin,
            'exp' => time() + 43200,
            'sub' => $subject,
        ]));
        $signingInput = $header.'.'.$claims;

        $pem = $this->rawPrivateToPem($this->b64urlDecode($privateKeyB64));
        openssl_sign($signingInput, $derSignature, $pem, OPENSSL_ALGO_SHA256);

        $jwt = $signingInput.'.'.$this->b64urlEncode($this->derToRawSignature($derSignature));

        return 'vapid t='.$jwt.', k='.$publicKeyB64;
    }

    /* ---------------- 工具函数 ---------------- */

    protected function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $okm = '';
        $t = '';
        $counter = 1;

        while (strlen($okm) < $length) {
            $t = hash_hmac('sha256', $t.$info.chr($counter++), $prk, true);
            $okm .= $t;
        }

        return substr($okm, 0, $length);
    }

    /** EC 密钥资源 -> raw 65 字节公钥（04||X||Y） */
    protected function publicKeyToRaw($pkey): string
    {
        $details = openssl_pkey_get_details($pkey);

        return "\x04".str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
            .str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
    }

    /** raw 65 字节公钥 -> openssl 可读 SPKI DER */
    protected function rawPublicToDer(string $raw): string
    {
        return hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$raw;
    }

    /** raw 32 字节私钥 -> SEC1 EC PRIVATE KEY PEM */
    protected function rawPrivateToPem(string $raw): string
    {
        $body = "\x02\x01\x01"."\x04\x20".$raw."\xa0\x07".hex2bin('06072a8648ce3d0201');
        $der = "\x30".$this->derLength(strlen($body)).$body;

        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64)."-----END EC PRIVATE KEY-----\n";
    }

    protected function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\0");

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    /** DER ECDSA 签名 -> raw r||s（64 字节） */
    protected function derToRawSignature(string $der): string
    {
        $pos = 2; // 跳过 SEQUENCE 头
        $r = $this->readDerInt($der, $pos);
        $s = $this->readDerInt($der, $pos);

        return str_pad($r, 32, "\0", STR_PAD_LEFT).str_pad($s, 32, "\0", STR_PAD_LEFT);
    }

    protected function readDerInt(string $der, int &$pos): string
    {
        $pos++; // INTEGER tag
        $len = ord($der[$pos]);
        $pos++;
        if ($len & 0x80) {
            $numBytes = $len & 0x7F;
            $len = (int) hexdec(bin2hex(substr($der, $pos, $numBytes)));
            $pos += $numBytes;
        }
        $int = ltrim(substr($der, $pos, $len), "\0");
        $pos += $len;

        return $int;
    }

    /** 生成 VAPID 密钥对（供 Admin 初始化使用） */
    public static function generateVapidKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($key);

        $public = "\x04".str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
            .str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        // 从 SEC1 DER 中提取 32 字节私钥 d（"\x04\x20" 定位）
        $pem = $details['key'];
        $pos = strpos($pem, "\x04\x20");
        $d = substr($pem, $pos + 2, 32);

        return [
            'public_key' => rtrim(strtr(base64_encode($public), '+/', '-_'), '='),
            'private_key' => rtrim(strtr(base64_encode($d), '+/', '-_'), '='),
        ];
    }

    /* ---------------- base64url ---------------- */

    protected function b64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    protected function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /* ---------------- HTTP ---------------- */

    protected function post(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status];
    }
}
