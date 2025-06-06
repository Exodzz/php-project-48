<?php

namespace Differ\Formatters\Json;

function formatResult(array $diff, array $acc = [], string $path = ''): string|bool
{
    return json_encode($diff, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
