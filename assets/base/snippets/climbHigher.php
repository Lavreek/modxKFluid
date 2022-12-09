<?php
global $modx;

/**
 * @var int $goTo - Параметр используется для перехода к определённому ресурсу.
 * @var string $caption - Параметр используется для использования произвольного текста.
 */

if (isset($modx->resourceIdentifier)) {
    if (!isset($goTo)) {
        $goTo = $modx->resource->get('parent');
    }

    if (!isset($caption)) {
        $caption = "Вернуться назад";
    }

    if ($goTo < 1) {
        $goTo = 1;
    }

    echo $modx->getChunk('climb_higher_button', ['id' => $goTo, 'caption' => $caption]);
}

return;