<?php

namespace Wulkanowy\TimetablesListGenerator;

class FilterHelper
{
    public static function wwwFilters(array $array) : array
    {
        return array_filter($array, function (array $a) {
            return '' !== $a['www']
                && '0' !== $a['www']
                && '-' !== $a['www']
                && '--' !== $a['www']
                && '---' !== $a['www']
                && '----' !== $a['www']
                && 'brak' !== $a['www'];
        });
    }

    public static function complexityFilter(array $array)
    {
        return array_filter($array, function (array $a) {
            return '001' === $a['complexity'];
        });
    }

    public static function removeDuplicatesByValue(array $array, string $value) : array
    {
        $newArr = [];
        foreach ($array as $val) {
            $newArr[$val[$value]] = $val;
        }

        return array_values($newArr);
    }

    public static function removeInvalidWww(array $array) : array
    {
        return array_filter($array, function (array $a) {
            return filter_var($a['www'], FILTER_VALIDATE_URL) !== false;
        });
    }
}
