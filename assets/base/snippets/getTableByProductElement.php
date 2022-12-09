<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

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
     * Объединение ресурсов по заданному полю
     *
     * @var boolean $unityCol
     */
    $unityCol = qualifier($unityCol);

    /**
     * Идентификатор ресурса, от которого происходит поиск продукции
     *
     * @var int $pageId
     */

    if (isset($modx->resourceIdentifier)) {
        if (!isset($pageId)) {
            $pageId = $modx->resourceIdentifier;
        }

        $selectCategory = "SELECT `id`, `pagetitle` FROM `modx_site_content` WHERE `parent` = '$pageId' AND `template` = '4'";
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
                                if ($unityCol != "") {
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
                    if ($unityCol != "") {
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
            $thead = $tbody = $theadGroupPrice = $tbodyGroup = "";
            $fill = true;

            $col = array_diff($tableHeader, ['Группа']);
            $row = &$tableRows;

            for ($i = 0; $i < count($tableRows); $i++) {
                $tbody .= "<tr>";

                for ($j = 0; $j < count($col); $j++) {
                    $colKey = key($col);

                    if ($fill) {
                        $thead .= "<th scope='col'>". $col[$colKey] ."</th>";
                    }

                    $rowKey = key($row);

                    if (!isset($tableRows[$rowKey]['Группа'])) {
                        if (isset($tableRows[$rowKey][$colKey])) {
                            $tbody .= "<td style='text-align: center;'>{$tableRows[$rowKey][$colKey]}</td>";

                        } else {
                            $tbody .= "<td></td>";
                        }
                    }

                    next($col);
                }

                if ($fill) {
                    $fill = false;
                }

                next($row);
                reset($col);
                $tbody .= "</tr>";
            }

            $fill = true;

            if ($unityCol == "Цена") {
                $unityRow = [];
                $unityHeader = [];

                $getCanon = $getUnity = "";

                $theadGroup = "";

                foreach ($tableHeader as $tableCol => $colValue) {
                    if ($tableCol == "Цена") {
                        $theadGroup .= "<th style='text-align: center;' scope='col' colspan='3'>" . $colValue . "</th>"; // colspan - max group size

                    } elseif (in_array($tableCol, $getParams)) {
                        continue;

                    } else {
                        $theadGroup .= "<th scope='col' rowspan='2'>" . $colValue . "</th>";
                    }
                }

                foreach ($tableRows as $tableRow) {
                    if (isset($tableRow['Группа'])) {
                        array_push($groupRows[$tableRow['Группа']], $tableRow);

                    } else {
                        $tbodyGroup .= "<tr>";
                        foreach ($tableRow as $tRow) {
                            $tbodyGroup .= "<td>$tRow</td>";
                        }
                        $tbodyGroup .= "</tr>";
                    }
                }

                /**
                 * @var $groupName - Название группы
                 * @var  $groupRow - Элементы группы
                 */
                $tableGroups = [];
                $tableGroupCols = [];

                foreach ($groupRows as $groupName => $groupRow) {
                    $groupParams = [];

                    foreach ($groupRow as $rowElement) {
                        foreach ($rowElement as $eParam => $eValue) {
                            if (!in_array($eParam, $getParams)) {
                                if (!isset($groupParams[$eParam])) {
                                    if (!isset($groupParams['id'])) {
                                        $groupParams += ['id' => $rowElement['id']];
                                    }
                                    if ($eParam == 'Цена') {
                                        $tableColValue = str_replace([$rowElement['Группа'], '-'], '', $rowElement['pagetitle']);

                                        if (!in_array($tableColValue, $tableGroupCols)) {
                                            array_push($tableGroupCols, $tableColValue);
                                        }

                                        $groupParams += [$eParam => [$tableColValue => $eValue]];

                                    } else {
                                        $groupParams += [$eParam => $eValue];
                                    }
                                }
                                if (isset($groupParams[$eParam])) {
                                    if ($eParam == 'Цена') {
                                        $tableColValue = str_replace([$rowElement['Группа'], '-'], '', $rowElement['pagetitle']);

                                        if (!in_array($tableColValue, $tableGroupCols)) {
                                            array_push($tableGroupCols, $tableColValue);
                                        }

                                        $groupParams['Цена'] += [$tableColValue => $eValue];
                                    }
                                }
                            }
                        }
                    }

                    array_push($tableGroups, $groupParams);
                }

                $extraGroupLine = "";
                foreach ($tableGroups as $tGroup) {
                    $extraGroupLine .= "<tr>";

                    foreach ($tGroup as $tCol => $tValue) {
                        if (!in_array($tCol, $getParams)) {
                            if ($tCol == "Цена") {
                                foreach ($tableGroupCols as $tgCol) {
                                    if (isset($tValue[$tgCol])) {
                                        $extraGroupLine .= "<td>$tValue[$tgCol]</td>";

                                    } else {
                                        $extraGroupLine .= "<td></td>";
                                    }
                                }
                            } elseif ($tCol == "Группа") {
                                $extraGroupLine .= "<td><a href='[[~{$tGroup['id']}]]'>$tValue</a></td>";

                            } else {
                                $extraGroupLine .= "<td>$tValue</td>";
                            }
                        }
                    }

                    $extraGroupLine .= "</tr>";
                }

                $theadGroup .= "<tr>";

                foreach ($tableGroupCols as $value) {
                    $theadGroup .= "<td>$value</td>";
                }

                $theadGroup .= "</tr>";

                echo
                    "<div class='product-table'>
                        <h1>{$category['pagetitle']}</h1>
                        <table class='table table-hover table-bordered' style='text-align: center'>
                            <thead class='table-light' style='vertical-align: middle;'>
                                $theadGroup
                            </thead>
                            <tbody>
                                $extraGroupLine
                            </tbody>
                        </table>
                        
                        <table class='table' style='text-align: center'>
                            $thead
                            <tbody>
                                $tbody
                            </tbody>
                        </table>
                    </div>";
            }
        }

        return;
    }