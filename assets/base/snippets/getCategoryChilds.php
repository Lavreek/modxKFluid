<?php
global $modx;

if (isset($modx->resourceIdentifier)) {
    $resources = [2]; // Card - TV Params
    $pageId = $modx->resourceIdentifier;

    $selectCategories = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '$pageId' AND `published` = '1'";
    $categories = $modx->query($selectCategories);

    if (!is_bool($categories)) {
        $categories = $categories->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $category) {
            echo "<h3 class='page-title py-em-2'><a style='all: unset; cursor: pointer;' href='[[~{$category['id']}]]'>{$category['pagetitle']}</a></h3>";

            $selectChilds = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '{$category['id']}' AND `published` = '1'";
            $childs = $modx->query($selectChilds);

            if (!is_bool($childs)) {
                $childs = $childs->fetchAll(PDO::FETCH_ASSOC);
                $countChilds = count($childs);

                foreach ($childs as $child) {
                    $selectSubParams = "SELECT `tmplvarid`, `value` FROM `modx_site_tmplvar_contentvalues` WHERE `contentid` = '{$child['id']}' AND `tmplvarid` IN (" . implode(", ", $resources) . ")";
                    $params = ($modx->query($selectSubParams))->fetchAll(PDO::FETCH_ASSOC);

                    if ($countChilds >= 5) {
                        $extra = "gr-5";
                    } elseif ($countChilds > 0) {
                        $extra = "gr-$countChilds";
                    }

                    foreach ($params as $param) {
                        switch ($param['tmplvarid']) {
                            case 2:
                                $child['image'] = $param['value'];
                                break;
                        }
                    }

                    $childCard .= $modx->getChunk('product_card', $child);

                }

                if ($childCard != "") {
                    echo "<div class='product-grid $extra'> $childCard </div>";
                }
            }
        }
    }
}

return;