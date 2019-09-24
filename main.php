<?php

include('utils.php');

$groups_file = 'groups.csv';
$products_file = 'groups.csv';

$groups = parse_scv($groups_file);
$group_tree = construct_group_tree($groups);

$product = get_product_row($products_file);
add_product($product, $group_tree);

echo html_from($group_tree);
