<?php
global $modx;

    const localeDelimiter = ":"; // Стандартный разделитель для локализации колонок
    const uninyDelimiter = ":"; // Стандартный разделитель для объединения
    const extraDelimiter = ","; // Стандартный разделитель для дополнительных колонок

    $variableSearch = ["\t", "\n", "\r"]; // Удаление табов, переносов, разрывов из строки параметра
    $acceptedTemplates = [3, 4]; // Разрешённые для поиска шаблоны

    /**
     * @var string $localeCol Изменение названий колонок в шапке таблицы
     *     &localeCol=`id:#,pagetitle:Кодировка` Разделитель - localeDelimiter
     *
     * @var string $extraCols Дополнительные колонки в таблице исходя из TV параметра
     *     &extraCols=`Подсоединение, Цена` Разделитель - extraDelimiter
     *
     * @var string $unityCol Объединение ресурсов по заданному полю
     *     &unityCol=`Цена:Кодировка` Разделитель - uninyDelimiter
     *
     * @var string $pageId Идентификатор ресурса, от которого происходит поиск продукции
     */


    function explodeVariable(&$string, &$array, $delimiter) {
        global $variableSearch;

        $multi = explode(",", str_replace($variableSearch, '', $string));

        foreach ($multi as $cols) {
            $exp = array_map('trim', explode($delimiter, $cols));

            if (isset($exp[0], $exp[1])) {
                $array += [$exp[0] => $exp[1]];
            }
        }

        if (count($array) < 1) {
            unset($string, $array);
        }
    }

    /**
     * @var array $defaultHeaderCols Набор всегда отображающихся параметров
     * @var string $defaultHeaderColsString Строка параметров для SQL запроса
     */
    $defaultHeaderCols = ['id', 'pagetitle'];
    $defaultHeaderColsString = "`" . implode("`, `", $defaultHeaderCols) . "`";

    /**
     * @var array $rootResources Собрание элементов, которые не имеют категории
     */
    $rootResources = [];

    /**
     * @var array $deleteCols удалить лишние колонки
     */
    $deleteCols = [];
    if (isset($unityCol)) {
        $deleteCols = $defaultHeaderCols;
    }

    if (isset($modx->resourceIdentifier)) {
        if (!isset($pageId)) {
            $pageId = $modx->resourceIdentifier;
        }

        $selectResources = "SELECT `id`, `pagetitle`, `template` FROM `modx_site_content` WHERE `parent` = '$pageId' AND `template` in (" . implode(", ", $acceptedTemplates) . ") and `published` = '1'";
        $resources = $modx->query($selectResources);

        if (!is_bool($resources)) {
            $resources = $resources->fetchAll(PDO::FETCH_ASSOC);
            $rootHeader = $defaultHeaderCols;

            foreach ($resources as $resourceIndex => $resource) {
                if (isset($extraCols)) {
                    $extraArray = array_map('trim', explode(extraDelimiter, $extraCols));
                }

                if (isset($_GET['debug-snippet'])) {
                    echo "&extraCols: ";
                    var_dump($extraCols, $extraArray);
                    echo "<br><br>";
                }

                if (isset($unityCol)) {
                    $unityArray = $unityValues = [];

                    explodeVariable($unityCol, $unityArray, uninyDelimiter);
                }

                if (isset($_GET['debug-snippet'])) {
                    echo "&unityCol: ";
                    var_dump($unityCol, $unityArray);
                    echo "<br><br>";
                }

                $localeArray = ['id' => '#', 'pagetitle' => 'Кодировка'];

                if (isset($localeCol)) {

                    explodeVariable($localeCol, $localeArray, localeDelimiter);
                }

                if (isset($_GET['debug-snippet'])) {
                    echo "&localeCol: ";
                    var_dump($localeCol, $localeArray);
                    echo "<br><br>";
                }

                if ($resource['template'] == 3) {
                    unset($resources[$resourceIndex]['template']);

                    if (isset($extraArray)) {
                        if (isset($unityArray)) {
                            array_push($extraArray, 'Группа');

                            $denied = ['pagetitle', 'Кодировка', 'id', '#'];

                            foreach ($unityArray as $unityKey => $unityValue) {
                                if (!in_array($unityValue, $denied)) {
                                    array_push($extraArray, $unityValue);
                                }
                            }
                        }

                        $selectExtraParams = "SELECT mst.`id`, `name`, `caption`, `value` FROM `modx_site_tmplvars` AS mst INNER JOIN `modx_site_tmplvar_contentvalues` AS mstc ON mst.`id` = mstc.`tmplvarid` WHERE mstc.`contentid` = '{$resource['id']}' AND `caption` IN ('" . implode("', '", $extraArray) . "')";
                        $extraParams = $modx->query($selectExtraParams);

                        if (!is_bool($extraParams)) {
                            foreach ($extraParams->fetchAll(PDO::FETCH_ASSOC) as $param) {

                                if (!in_array($param['name'], $resource)) {
                                    $resources[$resourceIndex] += [$param['name'] => $param['value']];
                                }

                                if (!in_array($param['name'], $rootHeader)) {
                                    array_push($rootHeader, $param['name']);
                                }

                                if (!in_array($param['name'], $localeArray)) {
                                    $localeArray += [$param['name'] => $param['caption']];
                                }
                            }
                        }
                    }

                    array_push($rootResources, $resources[$resourceIndex]);

                } elseif ($resource['template'] == 4) {
                    $categoryHeader = $defaultHeaderCols;
                    $categoryGroups = [];

                    $selectProduction = "SELECT $defaultHeaderColsString FROM `modx_site_content` WHERE `template` = '3' AND `published` = '1' AND `parent` = '{$resource['id']}'";
                    $productions = $modx->query($selectProduction);

                    if (!is_bool($productions)) {
                        $productions = $productions->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($productions as $rowIndex => $rowValue) {

                            if (isset($extraArray)) {
                                if (isset($unityArray)) {
                                    array_push($extraArray, 'Группа');

                                    $denied = ['pagetitle', 'Кодировка', 'id', '#'];

                                    foreach ($unityArray as $unityKey => $unityValue) {
                                        if (!in_array($unityValue, $denied)) {
                                            array_push($extraArray, $unityValue);
                                        }
                                    }
                                }

                                $selectExtraParams = "SELECT mst.`id`, `name`, `caption`, `value` FROM `modx_site_tmplvars` AS mst INNER JOIN `modx_site_tmplvar_contentvalues` AS mstc ON mst.`id` = mstc.`tmplvarid` WHERE mstc.`contentid` = '{$rowValue['id']}' AND `caption` IN ('" . implode("', '", $extraArray) . "')";
                                $extraParams = $modx->query($selectExtraParams);

                                if (!is_bool($extraParams)) {
                                    foreach ($extraParams->fetchAll(PDO::FETCH_ASSOC) as $param) {
                                        if (!in_array($param['name'], $rowValue)) {
                                            $productions[$rowIndex] += [$param['name'] => $param['value']];
                                        }

                                        if (!in_array($param['name'], $categoryHeader)) {
                                            array_push($categoryHeader, $param['name']);
                                        }

                                        if (!in_array($param['name'], $localeArray)) {
                                            $localeArray += [$param['name'] => $param['caption']];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (isset($unityArray)) {
                        $translit = [];
                        foreach ($unityArray as $unityIndex => $unityValue) {
                            if (in_array($unityIndex, $localeArray)) {
                                $unityKey = array_search($unityIndex, $localeArray);
                                $unityValue = array_search($unityValue, $localeArray);
                                $categoryGroups += [$unityValue => []];
                                array_push($deleteCols, $unityValue);
                                $translit += [$unityKey => $unityValue];
                            }

                        }
                        $unityArray = $translit;
                    }

                    if (isset($_GET['debug-snippet'])) {
                        echo "unityArray: ";
                        var_dump($unityArray);
                        echo "<br><br>";
                    }

                    $thead = $tbody = "";

                    if (isset($unityArray)) {
                        $groups = [];

                        foreach ($productions as $product) {
                            if (isset($product['resource_group'])) {
                                if (!isset($groups[$product['resource_group']])) {
                                    $groups += [$product['resource_group'] => []];
                                }

                                if (!in_array($product, $groups[$product['resource_group']])) {
                                    array_push($groups[$product['resource_group']], $product);
                                }
                            }
                            foreach ($product as $rowCol => $rowValue) {
                                if (isset($unityArray)) {
                                    if (isset($categoryGroups[$rowCol])) {
                                        if (!in_array($rowValue, $categoryGroups[$rowCol])) {
                                            array_push($categoryGroups[$rowCol], $rowValue);
                                        }
                                    }
                                }
                            }
                        }

                        for ($i = 0; $i < count($groups); $i++) {
                            $row = [];
                            $group = current($groups);

                            for ($j = 0; $j < count($group); $j++) {
                                $groupMember = current($group);

                                foreach ($groupMember as $gmKey => $gmValue) {
                                    if (is_array($row[$gmKey])) {
                                        array_push($row[$gmKey], $gmValue);
                                    }
                                    if (!isset($row[$gmKey])) {
                                        if (isset($unityArray[$gmKey])) {
                                            $row[$gmKey] = [$gmValue];
                                            continue;
                                        }

                                        $row[$gmKey] = $gmValue;
                                    }
                                }

                                next($group);
                            }
                            $tbody .= "<tr>";

                            foreach ($row as $rowCol => $rowValue) {
                                if (!in_array($rowCol, $deleteCols)) {
                                    if (is_array($rowValue)) {
                                        foreach ($rowValue as $value) {
                                            $tbody .= "<td>$value</td>";
                                        }

                                    } else {
                                        $tbody .= "<td>$rowValue</td>";
                                    }
                                }
                            }

                            $tbody .= "</tr>";

                            next($groups);
                        }

                    } else {
                        foreach ($productions as $product) {
                            $tbody .= "<tr>";

                            foreach ($product as $rowCol => $rowValue) {
                                $tbody .= "<td>$rowValue</td>";
                            }

                            $tbody .= "</tr>";
                        }
                    }

                    if (isset($_GET['debug-snippet'])) {
                        echo "UnityParams: ";
                        var_dump($categoryGroups);
                        echo "<br><br>";

                        echo "&localeArray: ";
                        var_dump($localeArray);
                        echo "<br><br>";
                    }

                    $thead .= "<tr>";
                    foreach (array_diff($categoryHeader, $deleteCols) as $colIndex => $colValue) {
                        $rowSpan = $colSpan = 1;

                        if (isset($unityArray)) {
                            $rowSpan = 2;

                            if (isset($unityArray[$colValue])) {
                                $rowSpan = 1;

                                $colSpan = count($categoryGroups[$unityArray[$colValue]]);
                            }
                        }

                        if (isset($localeArray[$colValue])) {
                            $colValue = $localeArray[$colValue];
                        }

                        $thead .= "<th rowspan='$rowSpan' colspan='$colSpan' scope='col'>" . $colValue . "</th>";
                    }
                    $thead .= "</tr>";

                    $thead .= "<tr>";

                    foreach ($categoryGroups as $unityParam) {
                        foreach ($unityParam as $param) {
                            $thead .= "<th scope='col'>" . $param . "</th>";
                        }
                    }

                    $thead .= "</tr>";

                    if ($resource['template'] == 4) {
                        echo "<h1>{$resource['pagetitle']}</h1>";
                    }

                    echo
                    "<div class='product-table'>
                        <table class='table' style='text-align: center'>
                            <thead class='table-light' style='vertical-align: middle;'>
                                $thead
                            </thead>
                            <tbody>
                                $tbody
                            </tbody>
                        </table>
                    </div>";
                }
            }

            /**
             * Отображение в таблицу элементов без вложенности в категорию (Таблицу)
             */
            if (count($rootResources) > 0) {
                foreach ($rootResources as $resource) {
                    $tbody .= "<tr>";

                    foreach ($resource as $resourceValue) {
                        $tbody .= "<td>$resourceValue</td>";
                    }

                    $tbody .= "</tr>";
                }

                foreach ($rootHeader as $colIndex => $colValue) {
                    if (isset($localeArray[$colValue])) {
                        $colValue = $localeArray[$colValue];
                    }

                    $thead .= "<th scope='col'>" . $colValue . "</th>";
                }

                echo
                "<div class='product-table'>
                        <table class='table' style='text-align: center'>
                            <thead class='table-light' style='vertical-align: middle;'>
                                $thead
                            </thead>
                            <tbody>
                                $tbody
                            </tbody>
                        </table>
                    </div>";
            }
        }

        return;
    }