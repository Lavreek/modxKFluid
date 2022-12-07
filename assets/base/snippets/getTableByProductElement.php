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
     * Объединение ресурсов по группе
     *
     * @var boolean $unity
     */
    $unity = qualifier($unity);

    if (isset($modx->resourceIdentifier)) {
        $page_id = $modx->resourceIdentifier;

        $selectCategory = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '$page_id' AND `template` = '4'";
        $categories = $modx->query($selectCategory);

        $getParams = ['id', 'pagetitle'];

        foreach ($categories->fetchAll(PDO::FETCH_ASSOC) as $category) {
            $selectProduction = "SELECT `". implode("`, `", $getParams) ."` FROM `modx_site_content` WHERE `template` = '3' AND `published` = '1' AND `parent` = '{$category['id']}'";
            $fetch = $modx->query($selectProduction);

            $tableHeader = []; $locale = ['id' => '#', 'pagetitle' => 'Кодировка'];
            $tableRows = [];
            $groupRows = [];

            if (!is_bool($fetch)) {
                $productions = $fetch->fetchAll(PDO::FETCH_ASSOC);

                foreach ($productions as $product) {
                    /**
                     * Создание строки продукции
                     */
                    if (!isset($tableRows[$product['id']])) {
                        $tableRows += [$product['id'] => []];
                    }

                    foreach ($product as $index => $value) {
                        /**
                         * Создание заголовка таблицы
                         */
                        if (!isset($tableHeader[$index])) {
                            if (isset($locale[$index])) {
                                $tableHeader += [$index => $locale[$index]];

                            } else {
                                $tableHeader += [$index => $index];
                            }
                        }
                        /**
                         * Добавление продукции
                         */
                        if (!isset($tableRows[$product['id']][$index])) {
                            $tableRows[$product['id']] += [$index => $value];
                        }
                        /**
                         * Дополнение экстра столбцов
                         */
                        if ($extraCols != "") {
                            if (is_string($extraCols)) {
                                $extraColsExplode = explode(",", $extraCols);

                                /**
                                 * Создание объединения
                                 */
                                if ($unity != "") {
                                    array_push($extraColsExplode, "Группа");
                                }

                                $extraCols = array_map('trim', $extraColsExplode);
                            }

                            $selectExtraCols = "SELECT `caption`, `value` FROM `modx_site_tmplvars` AS mst INNER JOIN `modx_site_tmplvar_contentvalues` AS mstc ON mst.`id` = mstc.`tmplvarid` WHERE mstc.`contentid` = '{$product['id']}' AND `caption` IN ('". implode("', '", $extraCols)."')";
                            $fetch = $modx->query($selectExtraCols);
                            $extraParams = $fetch->fetchAll(PDO::FETCH_ASSOC);

                            if (!is_bool($extraParams)) {
                                foreach ($extraParams as $param) {
                                    /**
                                     * Добавление дополнительных колонок в заголовок
                                     */
                                    if (!isset($tableHeader[$param['caption']])) {
                                        if (isset($locale[$param['caption']])) {
                                            $tableHeader += [$index => $locale[$param['caption']]];

                                        } else {
                                            $tableHeader += [$param['caption'] => $param['caption']];
                                        }
                                    }
                                    /**
                                     * Добавление дополнительных колонок в строки
                                     */
                                    if (!isset($tableRows[$product['id']][$param['caption']])) {
                                        $tableRows[$product['id']] += [$param['caption'] => $param['value']];
                                    }
                                }
                            }
                        }
                    }
                    /**
                     * Создание группы
                     */
                    if ($unity == "1") {
                        if (isset($tableRows[$product['id']]['Группа'])) {
                            $group = $tableRows[$product['id']]['Группа'];

                            if (!in_array($group, $groupRows)) {
                                $groupRows += [$group => []];
                            }
                        }
                    }
                }
            }

            /**
             * Отрисовка таблицы продукции
             *
             * P.S: Решение создания строк таблицы без функций next(), key(), reset() на данный момент не закончено,
             *     из-за того, что согласованности данных сложно достичь другими методами.
             *
             * @var string $thead - Содержимое заголовка таблицы
             * @var string $tbody - Содержимое тела таблицы
             * @var bool $fill - Маяк заполнения заголовка таблицы
             */
            $thead = $tbody = $theadGroup = $theadGroupPrice = $tbodyGroup = "";
            $fill = true;

            $col = &$tableHeader;
            $row = &$tableRows;
            for ($i = 0; $i < count($tableRows); $i++) {
                $tbody .= "<tr>";

                for ($j = 0; $j < count($tableHeader); $j++) {
                    $colKey = key($col);

                    if ($fill) {
                        $thead .= "<th scope='col'>". $tableHeader[$colKey] ."</th>";
                    }

                    $rowKey = key($row);
                    if (isset($tableRows[$rowKey][$colKey])) {
                        $tbody .= "<td style='text-align: center;'>{$tableRows[$rowKey][$colKey]}</td>";

                    } else {
                        $tbody .= "<td></td>";
                    }

                    next($col);
                }

                if ($fill) {
                    $fill = false;
                }

                next($row);
                reset($tableHeader);
                $tbody .= "</tr>";
            }
            $fill = true;

            if ($unity == "1") {
                $unityRow = [];
                $unityHeader = [];

                $getCanon = $getUnity = "";

                $theadGroup = "";
                foreach ($tableHeader as $tableCol => $colValue) {
                    if ($tableCol == "Цена") {
                        $theadGroup .= "<th style='text-align: center;' scope='col' colspan='3'>". $colValue ."</th>"; // colspan - max group size

                    } elseif ($tableCol == "pagetitle" or $tableCol == "id") {
                        continue;

                    } else {
                        $theadGroup .= "<th scope='col' rowspan='2'>". $colValue ."</th>";
                    }
                }
                $theadGroup .= "<tr>$theadGroup</tr>";

//                var_dump($groupRows);
//                foreach ($tableRows as $tableRow1) {
//                    if (isset($tableRow1['Группа'])) {
//                        $groupRows[$tableRow1['Группа']] += $tableRow1['id'];
////                        foreach ($tableRows as $tas => $tro) {
////                            $groupRows[$tableRow1['Группа']][$tas] = $tro;
////                        }
////                        var_dump($tableRow1);
//                    }
//
//                }
//                var_dump($groupRows);
//                die();
//                foreach ($tableHeader as $col => $value) {
//
//                    if ($theadGroupPrice == "") {
//                        foreach ($groupRows as $group => $groupRow) {
//                            $price = [];
//                            $tTime += ['id' => "", 'Группа' => $group];
//
//                            foreach ($groupRows as $gRow) {
//                                foreach ($extraCols as $ex) {
//                                    if (!isset($tTime[$ex])) {
//                                        $tTime += [$ex => $gRow[0][$ex]];
//                                    }
//                                }
//
//                                foreach ($gRow as $gArray) {
//                                    array_push($price, $gArray['Цена']);
////                                    $theadGroupPrice .= "<td style='max-width: 100px; text-align: center;'>". str_replace([$group, '-'], '', $gArray['pagetitle']) ."</td>";
//                                }
//                            }
//                            $tTime['Цена'] = $price;
//                            $tbodyGroup .= "<tr>";
//                            foreach ($tTime as $tkey => $tvalue) {
//                                if ($tkey == "Цена") {
//                                    foreach ($tvalue as $sa) {
//                                        $tbodyGroup .= "<td style='text-align: center;'>$sa</td>";
//                                    }
//
//                                } else {
//                                    $tbodyGroup .= "<td style='text-align: center;'>$tvalue</td>";
//                                }
//                            }
//                            $tbodyGroup .= "</tr>";
//                        }
//                    }
//                }
            }

//            $thead = "<thead class='thead-light' style='vertical-align: middle; text-align: center;'>
//                            <tr>$thead</tr>
//                        </thead>";
//
//            $theadGroup = "<thead class='thead-light' style='vertical-align: middle; text-align: center;'>
//                            <tr>$theadGroup</tr><tr style='width: auto;'>$theadGroupPrice</tr>
//                        </thead>";
            echo
                "<div class='product-table'>
                    <h1>{$category['pagetitle']}</h1>
                    <table class='table'>
                        $theadGroup
                        <tbody>
                            $tbodyGroup
                        </tbody>
                    </table>
                    <table class='table'>
                        $thead
                        <tbody>
                            $tbody
                        </tbody>
                    </table>
                </div>";
//            echo '<table class="table">
//  <thead>
//    <tr>
//      <th scope="col">#</th>
//      <th scope="col">First</th>
//      <th scope="col">Last</th>
//      <th scope="col">Handle</th>
//    </tr>
//  </thead>
//  <tbody>
//    <tr>
//      <th scope="row">1</th>
//      <td data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">Mark</td>
//      <td>Otto</td>
//      <td>@mdo</td>
//    </tr>
//    <tr class="collapse" id="collapseExample">
//       <td colspan="4">
//      <div class="card card-body">
//        Some placeholder content for the collapse component. This panel is hidden by default but revealed when the user activates the relevant trigger.
//      </div>
//      </td>
//    </tr>
//    <tr>
//      <th scope="row">2</th>
//      <td>Jacob</td>
//      <td>Thornton</td>
//      <td>@fat</td>
//    </tr>
//    <tr>
//      <th scope="row">3</th>
//      <td colspan="2">Larry the Bird</td>
//      <td>@twitter</td>
//    </tr>
//  </tbody>
//</table>';
        }

        return;
    }