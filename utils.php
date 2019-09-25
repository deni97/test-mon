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

    foreach ($rows as $row) {
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

function add_to_parent(&$parent, $key, array $params): void
{
    if (is_string($parent)) {
        return;
    }

    $node = $params[0];
    $branch = $params[1];
    $adding_to = $params[2];
    $checking = $params[3];

    if (!empty($parent[$branch]) && is_array($parent[$branch])) {
        array_walk($parent[$branch], 'add_to_parent', $params);
    }

    if (
        array_key_exists('id', $parent) && ($parent['id'] === $node[$checking])
    ) {
        if (!isset($params[4])) {
            $parent[$adding_to][] = $node;
        } else {
            $parent[$adding_to][] = $node[$params[4]];
        }
    }
}

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

function add_node(array &$tree, array $node): void
{
    if ($node['parent'] === '') {
        $tree[] = $node;
    }

    array_walk($tree, 'add_to_parent', [$node, 'children', 'children', 'parent']);
}

/**
 * В условии ясно не указан порядок групп в .csv. 
 * Предполагаю их упорядоченность.  
 * Если родителя нет на конкретном этапе - группа не обрабатывается.
 */
function construct_group_tree(array $groups): array
{
    $tree = [];

    foreach ($groups as $group) {
        add_node($tree, node_from_group($group));
    }

    return $tree;
}

function find_parent(&$tree, $id)
{
    $output = [];

    foreach ($tree as $parent) {
        if ($parent['id'] === $id) {
            return $parent;
        } else if (!array_key_exists('children', $parent)) {
            return null;
        } else {
            $output = find_parent($parent['children'], $id);
            if ($output) {
                return $output;
            }
        }
    }
}

function replace_placeholder(string $input, array $replacements): string
{
    $output = $input;

    $regex = '/%([\p{L}]*)%/u';

    $match = [];

    while (preg_match($regex, $output, $match)) {
        if (array_key_exists($match[1], $replacements)) {
            $output = preg_replace($regex, $replacements[$match[1]], $output, 1);
        }
    }

    return $output;
}

function inherit_format_helper(array &$tree, array $parent)
{
    if (
        ($parent['description_format'] !== '') && $parent['inheritable']
    ) {
        return $parent['description_format'];
    } else {
        $grandparent = find_parent($tree, $parent['parent']);
        if ($grandparent !== []) {
            $output = inherit_format_helper($tree, $grandparent);

            if ($output) {
                return $output;
            }
        }
    }
}

function inherit_format(array &$tree, array $product): string
{
    $parent = find_parent($tree, $product['категория']);
    $output = '';

    $output = inherit_format_helper($tree, $parent);
    return $output;
}

function get_format(array &$tree, array $parent, array $product): string
{
    if ($parent['description_format'] !== '') {
        return $parent['description_format'];
    }

    return inherit_format($tree, $product);
}

function add_product(array &$tree, array $product): void
{
    $parent = find_parent($tree, $product['категория']);

    $format = get_format($tree, $parent, $product);

    $product['formatted_text'] = replace_placeholder($format, $product);

    array_walk($tree, 'add_to_parent', [$product, 'children', 'products', 'категория', 'formatted_text']);
}
