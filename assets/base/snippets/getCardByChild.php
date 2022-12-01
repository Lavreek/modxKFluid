<?php
global $modx;

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $selectQuery = "SELECT `id`, `pagetitle`, `uri` FROM `modx_site_content` WHERE `published` = '1' AND `parent` = '$page_id'";
        $childs = $modx->query($selectQuery);
        echo "<div class='product-grid' style='display: grid; grid-template-columns: repeat(4, auto); align-items: center; justify-content: space-between; column-gap: 40px; row-gap: 20px;'>";

        foreach ($childs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo $modx->getChunk('product_card', ['pagetitle' => $row['pagetitle'], 'id' => $row['id'], 'uri' => $row['uri']]);
        }

        echo "</div>";
        return;
    }