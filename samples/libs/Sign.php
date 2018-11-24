<?php

class Sign
{
    private static $secret = 'abc';

    public static function build($request, $time = '')
    {
        if (!$time) {
            $time = time();
        }

        foreach ($request as $key => $val) {
            if (is_array($val)) {
                krsort($val);
                $data[$key] = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
        }

        ksort($request);

        return md5(date('YmdHis', $time) . substr(md5(json_encode($request, JSON_UNESCAPED_UNICODE) . $time), 8, 16) . self::$secret);
    }

    public static function verify($request)
    {
        if (!isset($request['sign']) || !isset($request['time'])) return false;
        $sign = $request['sign'];
        $time = $request['time'];

        if (time() - $time > 10) return false;

        unset($request['sign'], $request['time']);

        $verify = self::build($request, $time);

        if ($verify === $sign) return true;

        return false;
    }
}