<?php

namespace App\Repositories;

use Carbon\Carbon;

class MovieConverter
{

    /**
     * @param $runtime
     *
     * @return int
     */
    public static function toMiliSeconds($runtime)
    {
        $runtimeMinutes = explode(' ', $runtime);

        if($runtime == "N/A")
            return null;

        return (int)$runtimeMinutes[0];
    }

    /**
     * @param $released
     *
     * @return string
     */
    public static function toTimestamp($released)
    {
        if ($released != "N/A")
            return Carbon::parse($released)->toDateTimeString();
        else return null;
    }

    /**
     * @param $countries
     *
     * @return array
     */
    public static function explodeCountries($countries)
    {
        return self::explodeData($countries);

    }

    /**
     * @param $languages
     *
     * @return array
     */
    public static function explodeLanguages($languages)
    {
        return self::explodeData($languages);
    }

    /**
     * @param $genres
     *
     * @return array
     */
    public static function explodeGenres($genres)
    {
        return self::explodeData($genres);
    }

    /**
     * @param $directors
     *
     * @return array
     */
    public static function explodeDirectors($directors)
    {
        return self::explodeData($directors);

    }

    /**
     * @param $actors
     *
     * @return array
     */
    public static function explodeActors($actors)
    {
        return self::explodeData($actors);

    }

    /**
     * @param $writers
     *
     * @return array
     */
    public static function explodeWriters($writers)
    {
        if($writers == "N/A")
            return null;

        $writers = explode(',', $writers);

        $writers = array_filter($writers, function ($writer) {

            if ($writer == "" || is_null($writer)) {
                return false;
            }

            return true;
        });

        $writers = array_map(function ($item) {

            // mendapatkan kata yang di dalam kurung, semuanya!!
            preg_match_all('/\(.*?\)/', $item, $parenthesis);

            // get name, yang ga ada di dalam kurung, dengan cara mengganti yang ada
            // kurungnya dengan string kosong
            $name = str_replace($parenthesis[0], '', $item);

            // remove multiple space in this sentence
            $name = preg_replace('!\s+!', ' ', trim($name));

            return [

                'name' => $name,
                'as'   => isset($parenthesis[0][0]) ? substr($parenthesis[0][0], 1, - 1) : null
            ];

        }, $writers);

        return $writers;
    }

    /**
     * @param $genres
     *
     * @return array
     */
    public static function explodeData($data)
    {
        if($data == "N/A")
            return null;

        $data = explode(',', $data);

        $data = array_filter($data, function ($datum) {

            if ($datum == "" || is_null($datum)) {
                return false;
            }

            return true;
        });

        $data = array_map(function ($datum) {
            return utf8_encode(trim($datum));
        }, $data);

        return $data;
    }

    public static function changeEmptyStringToNull($string)
    {
        if ($string == "" || $string == "N/A")
            return null;
    }
}