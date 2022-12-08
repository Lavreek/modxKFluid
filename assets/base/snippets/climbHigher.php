<?php
global $modx;

if (isset($modx->resourceIdentifier)) {
    $parent = $modx->resource->get('parent');

    if ($parent < 1) {
        $parent = 1;
    }

    echo $modx->getChunk('climb_higher_button', ['id' => $parent]);
}

return;