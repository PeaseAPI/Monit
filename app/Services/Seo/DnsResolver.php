<?php

namespace App\Services\Seo;

use App\Support\Typed;
use Throwable;

/**
 * 极简 UDP DNS 解析器（指定 DNS 服务器查询）
 *
 * PHP dns_get_record 只走系统 resolver，无法指定服务器；对标 chinaz
 * 「多线多地 DNS 检测」（114/阿里/腾讯/谷歌/Cloudflare 节点一致性）需要
 * 自实现 DNS 报文。参考 DomainMonitor 的纯 socket whois 先例，仅依赖
 * stream 扩展：构造标准查询报文 → UDP:53 → 解析应答（含压缩指针）。
 * 报文与解析均为只读查询（RD=1），不承担权威/递归解析器职责。
 */
class DnsResolver
{
    /** 查询类型名 => 类型码 */
    public const TYPES = [
        'A' => 1,
        'NS' => 2,
        'CNAME' => 5,
        'SOA' => 6,
        'PTR' => 12,
        'MX' => 15,
        'TXT' => 16,
        'AAAA' => 28,
        'SRV' => 33,
        'CAA' => 257,
    ];

    /** 类型码 => 显示名 */
    public const TYPE_NAMES = [
        1 => 'A', 2 => 'NS', 5 => 'CNAME', 6 => 'SOA', 12 => 'PTR',
        15 => 'MX', 16 => 'TXT', 28 => 'AAAA', 33 => 'SRV', 257 => 'CAA',
    ];

    /**
     * 向指定 DNS 服务器查询一条记录
     *
     * @param  int  $timeoutMs  单次查询超时（毫秒）
     * @return array{ok: bool, error?: string, nxdomain?: bool, answers: list<array{type: string, value: string, ttl: int}>}
     */
    public function query(string $domain, string $resolver, string $type = 'A', int $timeoutMs = 1500): array
    {
        $typeCode = self::TYPES[strtoupper($type)] ?? null;

        if ($typeCode === null) {
            return ['ok' => false, 'error' => '不支持的记录类型', 'answers' => []];
        }

        $packet = $this->buildQuery($domain, $typeCode);

        if ($packet === null) {
            return ['ok' => false, 'error' => '域名格式无效', 'answers' => []];
        }

        $raw = $this->send($packet, $resolver, $timeoutMs);

        if ($raw === null) {
            return ['ok' => false, 'error' => 'DNS 服务器无响应（可能超时或网络不可达）', 'answers' => []];
        }

        return $this->parseResponse($raw, $typeCode);
    }

    /**
     * 构造标准查询报文（不启用 EDNS0，保持在 512 字节内）
     */
    protected function buildQuery(string $domain, int $typeCode): ?string
    {
        $domain = rtrim(trim($domain), '.');
        $qname = '';

        foreach (explode('.', $domain) as $label) {
            $len = strlen($label);

            if ($len === 0 || $len > 63) {
                return null;
            }

            $qname .= chr($len).$label;
        }

        $qname .= "\x00";

        return pack('n6', random_int(0, 0xFFFF), 0x0100, 1, 0, 0, 0).$qname.pack('nn', $typeCode, 1);
    }

    /**
     * 发送 UDP 查询并接收应答
     */
    protected function send(string $packet, string $resolver, int $timeoutMs): ?string
    {
        try {
            $target = str_contains($resolver, ':') ? '['.$resolver.']' : $resolver;
            $socket = @stream_socket_client('udp://'.$target.':53', $errorCode, $errorString, $timeoutMs / 1000);

            if ($socket === false) {
                return null;
            }

            stream_set_timeout($socket, intdiv($timeoutMs, 1000), ($timeoutMs % 1000) * 1000);
            fwrite($socket, $packet);
            $response = fread($socket, 4096);
            fclose($socket);

            return is_string($response) && $response !== '' ? $response : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * 解析应答报文
     *
     * @return array{ok: bool, error?: string, nxdomain?: bool, answers: list<array{type: string, value: string, ttl: int}>}
     */
    protected function parseResponse(string $raw, int $typeCode): array
    {
        if (strlen($raw) < 12) {
            return ['ok' => false, 'error' => '应答报文过短', 'answers' => []];
        }

        $header = unpack('nid/nflags/nqd/nan/nns/nar', substr($raw, 0, 12));

        // flags 最高位是 QR：1=响应（0=查询请求不应出现）
        if ($header === false || ($header['flags'] & 0x8000) === 0) {
            return ['ok' => false, 'error' => '异常应答', 'answers' => []];
        }

        $rcode = $header['flags'] & 0x000F;

        if ($rcode === 3) {
            return ['ok' => true, 'nxdomain' => true, 'answers' => []];
        }

        if ($rcode !== 0) {
            return ['ok' => false, 'error' => 'DNS 服务器返回错误码 RCODE='.$rcode, 'answers' => []];
        }

        $offset = 12;
        $offset = $this->skipName($raw, $offset); // QNAME
        $offset += 4; // QTYPE + QCLASS

        $answers = [];
        $count = (int) $header['an'];

        for ($i = 0; $i < $count; $i++) {
            $offset = $this->skipName($raw, $offset);

            if (strlen($raw) - $offset < 10) {
                break;
            }

            $meta = unpack('ntype/nclass/Nttl/nrdlength', substr($raw, $offset, 10));

            if ($meta === false) {
                break;
            }

            $offset += 10;
            $rdata = substr($raw, $offset, (int) $meta['rdlength']);
            $rdStart = $offset; // RDATA 在原始报文中的绝对偏移（压缩指针换算基准）
            $offset += (int) $meta['rdlength'];

            $value = $this->formatRdata($raw, $meta['type'], $rdata, $rdStart);

            if ($value !== null) {
                $answers[] = [
                    'type' => self::TYPE_NAMES[$meta['type']] ?? (string) $meta['type'],
                    'value' => $value,
                    'ttl' => (int) $meta['ttl'],
                ];
            }
        }

        return ['ok' => true, 'answers' => $answers];
    }

    /**
     * 跳过（可能含压缩指针的）域名段，返回下一偏移
     */
    protected function skipName(string $raw, int $offset): int
    {
        while ($offset < strlen($raw)) {
            $len = ord($raw[$offset]);

            if ($len === 0) {
                return $offset + 1;
            }

            if (($len & 0xC0) === 0xC0) {
                return $offset + 2;
            }

            $offset += $len + 1;
        }

        return $offset;
    }

    /**
     * 按类型格式化 RDATA；不支持/为空返回 null（该条不计入展示）
     *
     * $rdStart 为 RDATA 在原始报文中的绝对偏移——域名压缩指针给出的是
     * 整个报文内的绝对偏移，域名解码统一在 $raw 上以绝对偏移进行。
     */
    protected function formatRdata(string $raw, int $type, string $rdata, int $rdStart): ?string
    {
        switch ($type) {
            case 1: // A
                $packed = unpack('N', $rdata);

                return strlen($rdata) === 4 && $packed !== false ? long2ip((int) $packed[1]) : null;

            case 28: // AAAA
                return strlen($rdata) === 16 ? @inet_ntop($rdata) : null;

            case 2: // NS
            case 5: // CNAME
            case 12: // PTR
                return $this->decodeName($raw, $rdStart);

            case 15: // MX
                if (strlen($rdata) < 3) {
                    return null;
                }

                $pref = unpack('n', $rdata);

                return ($pref === false ? '-' : (int) $pref[1]).' '.$this->decodeName($raw, $rdStart + 2);

            case 16: // TXT
                $texts = [];
                $pos = 0;

                while ($pos < strlen($rdata)) {
                    $len = ord($rdata[$pos]);
                    $texts[] = substr($rdata, $pos + 1, $len);
                    $pos += $len + 1;
                }

                return $texts === [] ? null : implode('', $texts);

            default:
                return $this->formatRdataComplex($raw, $type, $rdata, $rdStart);
        }
    }

    /**
     * 复合类型 RDATA（SOA / SRV / CAA）格式化，与简单类型拆开便于维护
     */
    protected function formatRdataComplex(string $raw, int $type, string $rdata, int $rdStart): ?string
    {
        switch ($type) {
            case 6: // SOA: mname rname serial refresh retry expire minimum
                if (strlen($rdata) < 20) {
                    return null;
                }

                $mname = $this->decodeName($raw, $rdStart);
                $after = $this->skipName($rdata, 0);
                $rname = $this->decodeName($raw, $rdStart + $after);
                $after2 = $this->skipName($rdata, $after);
                $nums = unpack('N5', substr($rdata, $after2, 20));

                return $mname.' '.$rname.' (serial='.($nums[1] ?? '-').' refresh='.($nums[2] ?? '-')
                    .' retry='.($nums[3] ?? '-').' expire='.($nums[4] ?? '-').' min='.($nums[5] ?? '-').')';

            case 33: // SRV: priority weight port target
                if (strlen($rdata) < 7) {
                    return null;
                }

                $nums = unpack('n3', substr($rdata, 0, 6));

                return ($nums[1] ?? '-').' '.($nums[2] ?? '-').' '.($nums[3] ?? '-').' '.$this->decodeName($raw, $rdStart + 6);

            case 257: // CAA: flags tag value
                if (strlen($rdata) < 2) {
                    return null;
                }

                $tagLen = ord($rdata[1]);
                $tag = substr($rdata, 2, $tagLen);
                $value = substr($rdata, 2 + $tagLen);

                return ord($rdata[0]).' '.$tag.' "'.$value.'"';

            default:
                return null;
        }
    }

    /**
     * 从原始应答报文的绝对偏移解码域名（支持压缩指针链，防环深 16）
     */
    protected function decodeName(string $raw, int $offset, int $depth = 0): string
    {
        $labels = [];
        $len = strlen($raw);

        while ($offset < $len && $depth < 16) {
            $byte = ord($raw[$offset]);

            if ($byte === 0) {
                break;
            }

            if (($byte & 0xC0) === 0xC0) {
                if ($offset + 1 >= $len) {
                    break;
                }

                $pointer = (($byte & 0x3F) << 8) | ord($raw[$offset + 1]);

                return trim(implode('.', array_merge($labels, [$this->decodeName($raw, $pointer, $depth + 1)])), '.');
            }

            $labels[] = substr($raw, $offset + 1, $byte);
            $offset += $byte + 1;
        }

        return implode('.', $labels);
    }

    /**
     * 便利方法：查询 A 记录并返回 IP 列表
     *
     * @return list<string>
     */
    public function resolveA(string $domain, string $resolver, int $timeoutMs = 1500): array
    {
        $result = $this->query($domain, $resolver, 'A', $timeoutMs);

        return array_values(array_map(
            fn (array $a): string => Typed::string($a['value']),
            array_filter($result['answers'] ?? [], fn (array $a): bool => $a['type'] === 'A')
        ));
    }
}
