<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

    global $modx;

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $selectChilds = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '$page_id' AND `published` = '1' AND `template` = '2'";
        $childs = $modx->query($selectChilds);

        $childs = $childs->fetchAll(PDO::FETCH_ASSOC);

        $countChilds = count($childs);

        if ($countChilds >= 5) {
            $extra = "gr-5";
        } else {
            $extra = "gr-$countChilds";
        }

        $childCard = "";

        $resources = [2];

        foreach ($childs as $row) {
            $selectSubParams = "SELECT `tmplvarid`, `value` FROM `modx_site_tmplvar_contentvalues` WHERE `contentid` = '{$row['id']}' AND `tmplvarid` IN (" . implode(", ", $resources) . ")";
            $params = ($modx->query($selectSubParams))->fetchAll(PDO::FETCH_ASSOC);

            foreach ($params as $param) {
                switch ($param['tmplvarid']) {
                    case 2:
                        $row['image'] = $param['value'];
                        break;
                }
            }
            
            $childCard .= $modx->getChunk('product_card', $row);
        }

        if ($childCard != "") {
            echo "<div class='product-grid $extra'> $childCard </div>";
        }

        return;
    }