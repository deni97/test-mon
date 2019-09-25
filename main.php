<?php

include('utils.php');

$groups_file = './data/groups.csv';
$products_file = './data/products.csv';

$groups = parse_csv($groups_file);
$group_tree = construct_group_tree($groups);

$products = parse_csv($products_file);

foreach ($products as $product) {
    add_product($group_tree, $product);
}

echo html_from($group_tree);
