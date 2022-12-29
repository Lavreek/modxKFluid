<?php
global $modx;

    /**
     * @var array $acceptedTemplates Массив разрешённых шаблонов для отображения карточки
     */
    $acceptedTemplates = [2, 6];

if (isset($modx->resourceIdentifier) and $modx->resourceIdentifier > 0) {
    $page_id = $modx->resourceIdentifier;

    $selectChilds = "SELECT `id`, `pagetitle`, `template` FROM `modx_site_content` WHERE `parent` = '$page_id' AND `published` = '1' AND `template` in (". implode(",", $acceptedTemplates) .")";
    $childs = $modx->query($selectChilds);

    if (!is_bool($childs)) {
        $childs = $childs->fetchAll(PDO::FETCH_ASSOC);

        $countChilds = count($childs);

        if ($countChilds < 1) {
            return;
        }

        if ($countChilds >= 5) {
            $extra = "gr-5";
        } elseif ($countChilds > 0) {
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

            /**
             * Наложение pdf ссылки поверх карточки шаблона: "Шаблон продукции в виде файла"
             */
            if ($row['template'] == 6) {
                $selectLink = "SELECT `value` FROM `modx_site_tmplvar_contentvalues` WHERE `tmplvarid` = '11'";
                $pdfLink = $modx->query($selectLink);

                if (!is_bool($pdfLink)) {
                    $pdfLink = $pdfLink->fetch(PDO::FETCH_ASSOC);

                    if (isset($pdfLink['value'])) {
                        $row += ['url' => $pdfLink['value']];
                        $childCard .= $modx->getChunk('product_card_file', $row);
                    }
                }
                
            } else {
                $childCard .= $modx->getChunk('product_card', $row);
            }
        }

        if ($childCard != "") {
            echo "<div class='product-grid $extra'> $childCard </div>";
        }
    }
}

return;