<?php

namespace App\Infrastructure\Action\Data;

/**
 * Class Type
 * @package App\Infrastructure\Action\Data
 */
class Type
{
    /**
     * Type Parser is designed to normalised all request data types into assoc array
     * @TODO Add extra type parser logic, for simplicity just parse json for now.
     * @param string $type
     * @param $data
     * @return false|float|int|mixed|\Services_JSON_Error|string|void
     */
    public static function parse(string $type, $data) {
        return \json_decode($data, true);
    }
}