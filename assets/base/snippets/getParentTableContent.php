<?php
global $modx;

    if (isset($modx->resourceIdentifier)) {

        $parentId = $modx->resource->get('parent');

        function getParentContent($id) {
            global $modx;

            $selectParent = "SELECT `id`, `content`, `parent`, `template` FROM `modx_site_content` WHERE `id` = '$id'";
            $fetch = $modx->query($selectParent);

            if (!is_bool($fetch)) {
                $response = $fetch->fetch(PDO::FETCH_ASSOC);

                if ($response['template'] == "4") {
                    return getParentContent($response['parent']);
                }

                $canExecute = ['getTableByProductElement'];

                preg_match_all('#\[\[\!([|\w|\s]*?)\?[\w|\s|\W]*?\]\]#', $response['content'], $matches);
                if (isset($matches[1][0])) {
                    foreach ($matches[1] as $matchIndex => $match) {
                        if (in_array($match, $canExecute)) {
                            $str_search = ['[', ']', '!', "\t", "\n", "\r"];

                            $execute = explode("?", str_replace($str_search, '', $matches[0][$matchIndex]));
                            $executeParams = array_map('trim', explode('&', trim($execute[1])));

                            $snippetCols = [];
                            foreach ($executeParams as $param) {
                                preg_match('#(\w*)\=`(.*?)`#', $param, $match);
                                if (isset($match[1], $match[2]))  {
                                    $snippetCols += [$match[1] => $match[2]];
                                }
                            }
                            $snippetCols += ['pageId' => $id];
                            echo $modx->runSnippet($execute[0], $snippetCols);
                        }
                    }
                }

            }
        }

        echo getParentContent($parentId);
    }