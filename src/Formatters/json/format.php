<?php

namespace Gendiff\Formatters\json;

function format(array $diff): string
{
    return json_encode($diff, JSON_PRETTY_PRINT);
}
