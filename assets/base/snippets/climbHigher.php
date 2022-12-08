<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

    global $modx;

    if (isset($modx->resourceIdentifier)) {
        $parent = $modx->resource->get('parent');

        if ($parent < 1) {
            $parent = 1;
        }

        echo $modx->getChunk('climb_higher_button', ['id' => $parent]);
    }

    return;