<?php

namespace Wulkanowy\TimetablesListGenerator;

class SortHelper
{
    public static function sortByValue(array &$array, string $name)
    {
        usort($array, function ($a, $b) use ($name) {
            return $a[$name] <=> $b[$name];
        });
    }
}
