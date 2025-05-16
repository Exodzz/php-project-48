<?php

namespace Gendiff\CompareArrays;

function isArray($value): bool
{
    return is_array($value) && !empty($value);
}

function compareArrays(array $arr1, array $arr2): array
{
    $result = [];
    $keys = array_unique(array_merge(array_keys($arr1), array_keys($arr2)));
    sort($keys);

    foreach ($keys as $key) {
        if (!array_key_exists($key, $arr1)) {
            $result[] = ['key' => $key, 'value' => $arr2[$key], 'mark' => 1];
        } elseif (!array_key_exists($key, $arr2)) {
            $result[] = ['key' => $key, 'value' => $arr1[$key], 'mark' => -1];
        } elseif ($arr1[$key] === $arr2[$key]) {
            $result[] = ['key' => $key, 'value' => $arr1[$key], 'mark' => 0];
        } else {
            if (isArray($arr1[$key]) && isArray($arr2[$key])) {
                $children = compareArrays($arr1[$key], $arr2[$key]);
                if (!empty($children)) {
                    $result[] = [
                        'key' => $key,
                        'children' => $children,
                        'mark' => 0
                    ];
                }
            } else {
                $result[] = ['key' => $key, 'value' => $arr1[$key], 'mark' => -1];
                $result[] = ['key' => $key, 'value' => $arr2[$key], 'mark' => 1];
            }
        }
    }

    return $result;
}
