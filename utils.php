<?php

function semicolon_str_getcsv(string $input): array
{
    return str_getcsv($input, ";");
}

function parse_csv(string $filename): array
{
    $rows   = array_map('semicolon_str_getcsv', file($filename));
    $header = array_shift($rows);
    $csv    = array();

    foreach($rows as $row) {
        // В products.csv в заголовке на одну ; больше, чем в записях
        if (count($header) !== count($row)) {
            $min = min(count($header), count($row));

            $header = array_slice($header, 0, $min);
            $row = array_slice($row, 0, $min);
        }

        $csv[] = array_combine($header, $row);
    }

    return $csv;
}

/**
 * В условии ясно не указан порядок групп в .csv. 
 * Предполагаю их упорядоченность.  
 * Если родителя нет на конкретном этапе - группа не обрабатывается.
 */
function construct_group_tree(array $groups): array
{
    function node_from_group(array $group): array
    {
        return [
            'id' => $group['id'],
            'name' => $group['наименование'],
            'parent' => $group['родитель'],
            'description_format' => $group['формат описания товаров'],
            'inheritable' => (bool) $group['наследовать дочерним'],
            'products' => [],
            'children' => []
        ];
    }

    function add_to_parent(array &$parent, $key, array $node): void
    {
        if ($parent['id'] === $node['parent']) {
            $parent['children'][] = $node;
        }

        if (!empty($parent['children'])) {
            array_walk($parent['children'], 'add_to_parent', $node);
        }
    }

    function add_node(array $node, array &$tree): void
    {
        if ($node['parent'] === '') {
            $tree[] = $node;
        }
        
        array_walk($tree, 'add_to_parent', $node);
    }

    $tree = [];

    foreach ($groups as $group) {
        add_node(node_from_group($group), $tree);
    }

    return $tree;
}

function replace_placeholder(string $input, array $replacements): string
{
    $output = $input;

    $regex = '/%([\p{L}]*)%/u';

    $match = [];

    while (preg_match($regex, $output, $match)) {
        if  (array_key_exists($match[1], $replacements)) {
            $output = preg_replace($regex, $replacements[$match[1]], $output, 1);
        }
    }

    return $output;
}

function get_product_row(string $filename): array
{
    $product = [];

    return $product;
}
