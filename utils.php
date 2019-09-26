<?php

function parse_csv(string $filename): array
{
    $rows   = array_map(function(string $input): array {
        return str_getcsv($input, ";");
    }, file($filename));

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

function is_sequential_array($arr)
{
    foreach (array_keys($arr) as $key)
        if (is_int($key)) return true;
    return false;
}

/**
 * array $params состоит из: 
 *   [0] - добавляемый элемент
 *   [1] - ветка родителя, по которой будет идти дальнейшая итерация
 *   [2] - ветка родителя, к которой будет присоединён элемент
 *   [3] - сравниваемая с айди родителя запись
 *   [4] - добавляемая ветка элемента, при отсутствии добавляется весь элемент
 */
function add_to_parent(&$parent, array $params): void
{
    if (!empty($parent) && is_sequential_array($parent)) {
        for ($i = 0; $i < count($parent); ++$i) {
            add_to_parent($parent[$i], $params);
        }

        return;
    }

    $node = $params[0];
    $branch = $params[1];
    $adding_to = $params[2];
    $checking = $params[3];

    if (
        array_key_exists('id', $parent) && ($parent['id'] === $node[$checking])
    ) {
        if (!isset($params[4])) {
            $parent[$adding_to][] = $node;
        } else {
            $parent[$adding_to][] = $node[$params[4]];
        }

        return;
    }

    if (!empty($parent[$branch]) && is_array($parent[$branch])) {
        for ($i = 0; $i < count($parent[$branch]); ++$i) {
            add_to_parent($parent[$branch][$i], $params);
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

    add_to_parent($tree, [$node, 'children', 'children', 'parent']);
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
        } else {
            $output = preg_replace($regex, 'UNDEFINED', $output, 1);
        }
    }

    return $output;
}

function inherit_format(array &$tree, array $parent)
{
    if (
        ($parent['description_format'] !== '') && $parent['inheritable']
    ) {
        return $parent['description_format'];
    } else {
        $grandparent = find_parent($tree, $parent['parent']);
        if ($grandparent !== []) {
            $output = inherit_format($tree, $grandparent);

            if ($output) {
                return $output;
            }
        }
    }
}

function get_format(array &$tree, array $parent, array $product)
{
    if ($parent['description_format'] !== '') {
        return $parent['description_format'];
    }

    return null;
}

function add_product(array &$tree, array $product): void
{
    $parent = find_parent($tree, $product['категория']);

    $format = get_format($tree, $parent, $product);

    if (!$format) {
        $format = inherit_format($tree, $parent);
    }

    $product['formatted_text'] = replace_placeholder($format, $product);

    add_to_parent($tree, [$product, 'children', 'products', 'категория', 'formatted_text']);
}

/**
 * Таб сделан так (не с помощью \t) для понятного отображения в терминале.
 */
function get_tabs(int $count): string
{
    $tabs = '';

    for ($i = 0; $i < $count; ++$i) {
        $tabs .= '	';
    }

    return $tabs;
}

/**
 * Ньюлайн сделан так (не с помощью \n) для понятного отображения в терминале.
 */
function html_from_group(array &$parent, int $count): string
{
    $tab_count = $count * 2;

    $html = '';

    $html .= get_tabs($tab_count);
    $html .= "<h$count>";
    $html .= $parent['name'];
    $html .= "</h$count>";
    $html .= '
';

    $html .= get_tabs($tab_count);
    $html .= '<ul>';
    $html .= '
';
    foreach ($parent['products'] as $product) {
        $html .= get_tabs($tab_count + 1);
        $html .= '<li><b>';
        $html .= $product;
        $html .= '</b></li>';
        $html .= '
';
    }

    foreach ($parent['children'] as $child) {
        $html .= get_tabs($tab_count + 1);
        $html .= '<li>';
        $html .= '
';
        $html .= html_from_group($child, $count + 1);
        $html .= get_tabs($tab_count + 1);
        $html .= '</li>';
        $html .= '
';
    }

    $html .= get_tabs($tab_count);
    $html .= '</ul>';
    $html .= '
';


    return $html;
}

function html_from(array &$tree): string
{
    $html = '<ul>';
    $html .= '
';
    $html .= get_tabs(1);
    $html .= '<li>';
    $html .= '
';

    foreach ($tree as $group) {
        $html .= html_from_group($group, 1);
    }

    $html .= '
';
    $html .= get_tabs(1);
    $html .= '</li>';
    $html .= '
';
    $html .= '</ul>';

    return $html;
}
