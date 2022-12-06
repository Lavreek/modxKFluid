<?php
global $modx;

    function qualifier($var) {
        if (isset($var))
            return $var;

        return "";
    }

    /**
     * Дополнительные колонки в таблице исходя из TV параметра
     *
     * @var string $extraCols
     */
    $extraCols = qualifier($extraCols);

    /**
     * Объединение ресурсов
     *
     * @var boolean $unity
     */

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $selectCategory = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '$page_id' AND `template` = '4'";
        $categories = $modx->query($selectCategory);

        foreach ($categories->fetchAll(PDO::FETCH_ASSOC) as $category) {
            $trProduction = $trHeader = "";

            $selectQuery = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `template` = '3' AND `published` = '1' AND `parent` = '{$category['id']}'";
            $tableRows = $modx->query($selectQuery);

            $translate = ['id' => '#', 'pagetitle' => 'Кодировка'];

            $trHeader = ""; $fill = true;

            $theadExtra = "";

            foreach ($tableRows->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $trProduction .= "<tr>";
                $tbodyExtra = "";

                if ($extraCols != "") {
                    $extraCols = explode(",", $extraCols);
                    $selectSubParams = "SELECT `caption`, `value` FROM  `modx_site_tmplvars` AS mst INNER JOIN `modx_site_tmplvar_contentvalues` AS mstc ON mst.`id` = mstc.`tmplvarid` WHERE mstc.`contentid` = '{$row['id']}' AND `caption` IN ('" . implode("', '", array_map('trim', $extraCols)) . "')";
                    $params = ($modx->query($selectSubParams))->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($params as $param) {
                        if ($fill)
                            $theadExtra .= "<th scope='col'>{$param['caption']}</th>";
                        $tbodyExtra .= "<td>{$param['value']}</td>";
                    }
                }

                foreach ($row as $col => $value) {

                    if ($fill) {
                        $trHeader .= "<th scope='col'>$translate[$col]</th>";
                    }

                    if ($col == "id") {
                        $trProduction .= "<th scope='row'>$value</th>";

                    } else {
                        $trProduction .= "<td>$value</td>";
                    }
                }

                $trProduction .= $tbodyExtra . "</tr>";
                $fill = false;
            }

            if ($trProduction != "") {
                echo
                "<div class='product-table'>
                <h1>{$category['pagetitle']}</h1>
                <table class='table'>
                    <thead class='thead-light'>
                        <tr>
                            ". $trHeader . $theadExtra ."
                        </tr>
                    </thead>
                    <tbody>
                        $trProduction
                    </tbody>
                </table>
            </div>";
            }
        }


        return;
    }