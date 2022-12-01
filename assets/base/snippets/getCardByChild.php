<?php
global $modx;

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $selectQuery = "SELECT `id`, `pagetitle`, `uri` FROM `modx_site_content` WHERE `published` = '1' AND `parent` = '$page_id'";
        $childs = $modx->query($selectQuery);

        $childCard = "";

        foreach ($childs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $childCard .= $modx->getChunk('product_card', ['pagetitle' => $row['pagetitle'], 'id' => $row['id'], 'uri' => $row['uri']]);
        }

        if ($childCard != "") {
            echo "<div class='product-grid'> $childCard </div>";
        }

        return;
    }