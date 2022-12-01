<?php
global $modx;

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $thead = ['id', 'pagetitle', 'uri'];

        $selectQuery = "SELECT " . implode(", ", $thead) . " FROM `modx_site_content` WHERE `template` = '3' AND `published` = '1' AND `parent` = '$page_id'";
        $tableRows = $modx->query($selectQuery);

        $trHeader = "";

        foreach ($thead as $value) {
            $trHeader .= "<th scope='col'>$value</th>";
        }

        $trProduction = "";

        foreach ($tableRows->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $trProduction .= "<tr>";

            foreach ($row as $col => $value) {
                if ($col == "id") {
                    $trProduction .= "<th scope='row'>$value</th>"; //$modx->getChunk('product_card', ['pagetitle' => $row['pagetitle'], 'id' => $row['id'], 'uri' => $row['uri']]);

                } else {
                    $trProduction .= "<td>$value</th>";
                }
            }

            $trProduction .= "</tr>";
        }

        if ($trProduction != "") {
            echo
            "<div class='product-table'>
                <table class='table'>
                    <thead class='thead-light'>
                        <tr>
                            $trHeader
                        </tr>
                    </thead>
                    <tbody>
                        $trProduction
                    </tbody>
                </table>
            </div>";
        }

        return;
    }