<?php
/**
 * Hàm thay thế khi PHP thiếu extension mbstring (thường gặp ở PHP dòng lệnh dùng cho cron).
 * Chỉ khai báo những hàm web có dùng; xử lý UTF-8 và chữ tiếng Việt.
 */
if (!function_exists('mb_substr')) {

function _u_chars(string $s): array
{
    $c = @preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    return $c === false ? str_split($s) : $c; // chuỗi không phải UTF-8 hợp lệ → tách theo byte
}

function mb_internal_encoding($enc = null)
{
    return $enc === null ? 'UTF-8' : true;
}

function mb_strlen($s, $enc = null): int
{
    $s = (string)$s;
    $n = @preg_match_all('/./us', $s);
    return $n === false ? strlen($s) : $n;
}

function mb_substr($s, $start, $length = null, $enc = null): string
{
    $c = _u_chars((string)$s);
    return implode('', array_slice($c, (int)$start, $length === null ? null : (int)$length));
}

function mb_strtolower($s, $enc = null): string
{
    static $map = null;
    if ($map === null) {
        $up = _u_chars('ÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ');
        $lo = _u_chars('àáảãạăằắẳẵặâầấẩẫậđèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵ');
        $map = array_combine($up, $lo);
    }
    return strtr(strtolower((string)$s), $map);
}

function mb_stripos($haystack, $needle, $offset = 0, $enc = null)
{
    $h = mb_strtolower((string)$haystack);
    $n = mb_strtolower((string)$needle);
    if ($n === '') return false;
    $byteOffset = strlen(mb_substr($h, 0, (int)$offset));
    $p = strpos($h, $n, $byteOffset);
    return $p === false ? false : mb_strlen(substr($h, 0, $p));
}
}
