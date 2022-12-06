<?php
global $modx;
    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;
        //SELECT * FROM `modx_site_content` AS msc JOIN `modx_site_tmplvar_contentvalues` AS mstc ON msc.id = mstc.contentid
        $selectQuery = "SELECT msc.`id` as id, msc.`pagetitle` as pagetitle, mstc.`value` as image FROM `modx_site_content` AS msc JOIN `modx_site_tmplvar_contentvalues` AS mstc ON msc.id = mstc.contentid WHERE msc.`published` = '1' AND msc.`parent` = '$page_id' AND mstc.`tmplvarid` = '2' AND msc.`template` = '2'";

        $childs = $modx->query($selectQuery);
        $childs = $childs->fetchAll(PDO::FETCH_ASSOC);

        $countChilds = count($childs);

        if ($countChilds >= 5) {
            $extra = "gr-5";
        } else {
            $extra = "gr-$countChilds";
        }

        $childCard = "";

        foreach ($childs as $row) {
            $childCard .= $modx->getChunk('product_card', ['pagetitle' => $row['pagetitle'], 'id' => $row['id'], 'image' => $row['image']]);
        }

        if ($childCard != "") {
            echo "<div class='product-grid $extra'> $childCard </div>";
        }

        return;
    }