<?php

if (!function_exists('toArray')) {
    function toArray($value, array $separators = [" E ", " e ", ", ", "/", ";"])
    {
        foreach ($separators as $separator) {
            $value = str_replace($separator, "+", $value);
        }

        $array = explode("+", $value);
        $array = array_map(fn($v) => normalize($v), $array);

        return $array;
    }
}

if (!function_exists('normalize')) {
    function normalize($value)
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $value = str_replace(".", "", $value);
        $value = str_replace("  ", " ", $value);
        $value = trim($value);
        // $value = Str::title($value);

        return $value;
    }
}
