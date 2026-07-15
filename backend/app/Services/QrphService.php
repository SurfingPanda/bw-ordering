<?php

namespace App\Services;

/**
 * Builds a dynamic QR Ph (EMVCo merchant-presented QR) string from a base
 * merchant payload by injecting the transaction amount and recomputing the
 * CRC, so the generated code is amount-specific rather than a static image.
 *
 * Faithful PHP port of the deleted frontend's src/lib/qrph.js — same
 * algorithm, kept here because the Blade checkout has no JS bundler to run
 * the original client-side and instead renders the QR image server-side
 * (see CheckoutController::qr()).
 *
 * The base payload is the data encoded in a merchant's static QR Ph code
 * (decode a printed QR with any reader to get it, and save it via the Site
 * Editor's Payment QR field). We:
 *   - set tag 01 (Point of Initiation Method) to "12" (dynamic),
 *   - set/insert tag 54 (Transaction Amount) to the order total,
 *   - drop and recompute tag 63 (CRC16-CCITT) so the code stays valid.
 */
class QrphService
{
    /** CRC16-CCITT (poly 0x1021, init 0xFFFF) — same bit-for-bit algorithm as qrph.js. */
    private static function crc16(string $str): string
    {
        $crc = 0xFFFF;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($str[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private static function field(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    /**
     * Parse an EMVCo string into ordered [id, value] TLV pairs, or null if it
     * isn't a well-formed TLV sequence.
     *
     * @return array<int, array{id: string, value: string}>|null
     */
    private static function parse(string $payload): ?array
    {
        $out = [];
        $i = 0;
        $len = strlen($payload);
        while ($i + 4 <= $len) {
            $id = substr($payload, $i, 2);
            $lenStr = substr($payload, $i + 2, 2);
            if (! ctype_digit($lenStr)) {
                return null;
            }
            $fieldLen = (int) $lenStr;
            $value = substr($payload, $i + 4, $fieldLen);
            if (strlen($value) !== $fieldLen) {
                return null;
            }
            $out[] = ['id' => $id, 'value' => $value];
            $i += 4 + $fieldLen;
        }

        return $i === $len ? $out : null;
    }

    /**
     * Returns the amount-injected payload string, or null if `$base` isn't a
     * valid EMVCo / QR Ph string (so callers can fall back to a demo QR).
     */
    public static function buildPayload(?string $base, float $amount): ?string
    {
        $fields = self::parse(trim((string) $base));
        if (! $fields) {
            return null;
        }
        // A real EMVCo QR starts with a Payload Format Indicator (tag 00).
        $hasIndicator = false;
        foreach ($fields as $f) {
            if ($f['id'] === '00') {
                $hasIndicator = true;
                break;
            }
        }
        if (! $hasIndicator) {
            return null;
        }

        $out = array_values(array_filter($fields, fn (array $f) => $f['id'] !== '63'));

        $initIndex = null;
        foreach ($out as $idx => $f) {
            if ($f['id'] === '01') {
                $initIndex = $idx;
                break;
            }
        }
        if ($initIndex !== null) {
            $out[$initIndex]['value'] = '12';
        } else {
            array_splice($out, 1, 0, [['id' => '01', 'value' => '12']]);
        }

        $amt = number_format($amount, 2, '.', '');
        $amtIndex = null;
        foreach ($out as $idx => $f) {
            if ($f['id'] === '54') {
                $amtIndex = $idx;
                break;
            }
        }
        if ($amtIndex !== null) {
            $out[$amtIndex]['value'] = $amt;
        } else {
            $curIndex = null;
            foreach ($out as $idx => $f) {
                if ($f['id'] === '53') {
                    $curIndex = $idx;
                    break;
                }
            }
            if ($curIndex !== null) {
                array_splice($out, $curIndex + 1, 0, [['id' => '54', 'value' => $amt]]);
            } else {
                $out[] = ['id' => '54', 'value' => $amt];
            }
        }

        $body = implode('', array_map(fn (array $f) => self::field($f['id'], $f['value']), $out)).'6304';

        return $body.self::crc16($body);
    }
}
