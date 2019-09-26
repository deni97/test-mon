<?php

use PHPUnit\Framework\TestCase;

include('./utils.php');

class UtilsTest extends Testcase
{
    public function group_provider()
    {
        return [
            [
                'id' => 1,
                'наименование' => 'Группа 1',
                'родитель' => '',
                'формат описания товаров' => 'Купите %наименование% по цене %цена%',
                'наследовать дочерним' => 1
            ],
            [
                'id' => 2,
                'наименование' => 'Группа 1.1',
                'родитель' => 1,
                'формат описания товаров' => '',
                'наследовать дочерним' => 0
            ],
            [
                'id' => 3,
                'наименование' => 'Группа 1.2',
                'родитель' => 1,
                'формат описания товаров' => 'Покупайте больше %name%',
                'наследовать дочерним' => 1
            ],
            [
                'id' => 4,
                'наименование' => 'Группа 1.2.1',
                'родитель' => 3,
                'формат описания товаров' => '',
                'наследовать дочерним' => 0
            ]
        ];
    }

    public function tree_provider()
    {
        return [
            [
                'id' => 1,
                'name' => 'Группа 1',
                'parent' => '',
                'description_format' => 'Купите %наименование% по цене %цена%',
                'inheritable' => true,
                'products' => [],
                'children' => [
                    [
                        'id' => 2,
                        'name' => 'Группа 1.1',
                        'parent' => 1,
                        'description_format' => '',
                        'inheritable' => false,
                        'products' => [],
                        'children' => []
                    ],
                    [
                        'id' => 3,
                        'name' => 'Группа 1.2',
                        'parent' => 1,
                        'description_format' => 'Покупайте больше %name%',
                        'inheritable' => true,
                        'products' => [],
                        'children' => [
                            [
                                'id' => 4,
                                'name' => 'Группа 1.2.1',
                                'parent' => 3,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [],
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function product_provider()
    {
        return [
            [
                'id' => 1,
                'категория' => 1,
                'наименование' => 'супрадин',
                'цена' => 100
            ],
            [
                'id' => 2,
                'категория' => 2,
                'наименование' => 'аспирин',
                'цена' => 200
            ],
            [
                'id' => 3,
                'категория' => 3,
                'наименование' => 'йод',
                'цена' => 15
            ],
            [
                'id' => 4,
                'категория' => 4,
                'наименование' => 'анальгин',
                'цена' => 20
            ]
        ];
    }

    public function test_parse_csv()
    {
        $groups = parse_csv('./tests/data/groups.csv');

        $expected_groups = $this->group_provider();

        $this->assertEquals(
            $expected_groups,
            $groups
        );
    }

    public function test_construct_group_tree()
    {
        $groups = $this->group_provider();
        $tree = construct_group_tree($groups);

        $expected_tree = $this->tree_provider();

        $this->assertEquals(
            $expected_tree,
            $tree
        );
    }

    public function test_replace_placeholder()
    {
        $expected_string = 'Купите тавегил по цене 1234, с целью UNDEFINED';

        $output = replace_placeholder(
            'Купите %наименование% по цене %цена%, с целью %цель%',
            [
                'наименование' => 'тавегил',
                'цена' => '1234',
                'поставщик' => 'pharmco'
            ]
        );

        $this->assertEquals(
            $expected_string,
            $output
        );
    }

    public function test_inherit_format()
    {
        $tree = $this->tree_provider();

        $product = $this->product_provider()[0];

        $expected_string = 'Купите %наименование% по цене %цена%';

        $parent = find_parent($tree, $product['категория']);

        $output = inherit_format($tree, $parent);

        $this->assertEquals(
            $expected_string,
            $output
        );

        $product = $this->product_provider()[3];

        $parent = find_parent($tree, $product['категория']);

        $expected_string = 'Покупайте больше %name%';

        $output = inherit_format($tree, $parent);

        $this->assertEquals(
            $expected_string,
            $output
        );
    }

    public function test_find_parent()
    {
        $tree = $this->tree_provider();

        $expected_parent = $tree[0];

        $parent = find_parent($tree, 1);

        $this->assertEquals(
            $expected_parent,
            $parent
        );

        $expected_parent = $tree[0]['children'][0];

        $parent = find_parent($tree, 2);

        $this->assertEquals(
            $expected_parent,
            $parent
        );

        $expected_parent = [
            'id' => 4,
            'name' => 'Группа 1.2.1',
            'parent' => 3,
            'description_format' => '',
            'inheritable' => false,
            'products' => [],
            'children' => []
        ];

        $parent = find_parent($tree, 4);

        $this->assertEquals(
            $expected_parent,
            $parent
        );
    }

    public function test_add_product()
    {
        $tree = $this->tree_provider();

        $product = $this->product_provider()[0];

        add_product($tree, $product);

        $product = $this->product_provider()[3];

        add_product($tree, $product);

        $expected_tree = [
            [
                'id' => 1,
                'name' => 'Группа 1',
                'parent' => '',
                'description_format' => 'Купите %наименование% по цене %цена%',
                'inheritable' => true,
                'products' => [
                    'Купите супрадин по цене 100'
                ],
                'children' => [
                    [
                        'id' => 2,
                        'name' => 'Группа 1.1',
                        'parent' => 1,
                        'description_format' => '',
                        'inheritable' => false,
                        'products' => [],
                        'children' => []
                    ],
                    [
                        'id' => 3,
                        'name' => 'Группа 1.2',
                        'parent' => 1,
                        'description_format' => 'Покупайте больше %name%',
                        'inheritable' => true,
                        'products' => [],
                        'children' => [
                            [
                                'id' => 4,
                                'name' => 'Группа 1.2.1',
                                'parent' => 3,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [
                                    'Покупайте больше UNDEFINED'
                                ],
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $expected_tree,
            $tree
        );
    }

    /**
     * @dataProvider add_to_parent_provider
     * @param array $params
     * @param array $expected_tree
     */
    public function test_add_to_parent(array $params, array $expected_tree)
    {
        $tree = $this->tree_provider();

        add_to_parent($tree, $params);

        $this->assertEquals(
            $expected_tree,
            $tree
        );
    }

    public function add_to_parent_provider()
    {
        $product1 = $this->product_provider()[0];
        $product1['formatted_text'] = 'Купите супрадин по цене 100';

        $product2 = $this->product_provider()[1];
        $product2['formatted_text'] = 'Купите аспирин по цене 200';

        $product4 = $this->product_provider()[3];
        $product4['formatted_text'] = 'Покупайте больше анальгин';

        $group5 = node_from_group([
            'id' => 5,
            'наименование' => 'Группа 1.2.1.1',
            'родитель' => 4,
            'формат описания товаров' => '1234',
            'наследовать дочерним' => 1
        ]);

        $group6 = node_from_group([
            'id' => 6,
            'наименование' => 'Группа 2',
            'родитель' => '',
            'формат описания товаров' => '',
            'наследовать дочерним' => 0
        ]);

        return [
            'group 5' => [
                'params' => [
                    $group5, 'children', 'children', 'parent'
                ],
                'expected_tree' => [
                    [
                        'id' => 1,
                        'name' => 'Группа 1',
                        'parent' => '',
                        'description_format' => 'Купите %наименование% по цене %цена%',
                        'inheritable' => true,
                        'products' => [],
                        'children' => [
                            [
                                'id' => 2,
                                'name' => 'Группа 1.1',
                                'parent' => 1,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [],
                                'children' => []
                            ],
                            [
                                'id' => 3,
                                'name' => 'Группа 1.2',
                                'parent' => 1,
                                'description_format' => 'Покупайте больше %name%',
                                'inheritable' => true,
                                'products' => [],
                                'children' => [
                                    [
                                        'id' => 4,
                                        'name' => 'Группа 1.2.1',
                                        'parent' => 3,
                                        'description_format' => '',
                                        'inheritable' => false,
                                        'products' => [],
                                        'children' => [
                                            [
                                                'id' => 5,
                                                'name' => 'Группа 1.2.1.1',
                                                'parent' => 4,
                                                'description_format' => '1234',
                                                'inheritable' => true,
                                                'products' => [],
                                                'children' => []
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'product 1' => [
                'params' => [
                    $product1,
                    'children', 'products', 'категория', 'formatted_text'
                ],
                'expected_tree' => [
                    [
                        'id' => 1,
                        'name' => 'Группа 1',
                        'parent' => '',
                        'description_format' => 'Купите %наименование% по цене %цена%',
                        'inheritable' => true,
                        'products' => [
                            'Купите супрадин по цене 100'
                        ],
                        'children' => [
                            [
                                'id' => 2,
                                'name' => 'Группа 1.1',
                                'parent' => 1,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [],
                                'children' => []
                            ],
                            [
                                'id' => 3,
                                'name' => 'Группа 1.2',
                                'parent' => 1,
                                'description_format' => 'Покупайте больше %name%',
                                'inheritable' => true,
                                'products' => [],
                                'children' => [
                                    [
                                        'id' => 4,
                                        'name' => 'Группа 1.2.1',
                                        'parent' => 3,
                                        'description_format' => '',
                                        'inheritable' => false,
                                        'products' => [],
                                        'children' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'product 2' => [
                'params' => [
                    $product2,
                    'children', 'products', 'категория', 'formatted_text'
                ],
                'expected_tree' => [
                    [
                        'id' => 1,
                        'name' => 'Группа 1',
                        'parent' => '',
                        'description_format' => 'Купите %наименование% по цене %цена%',
                        'inheritable' => true,
                        'products' => [],
                        'children' => [
                            [
                                'id' => 2,
                                'name' => 'Группа 1.1',
                                'parent' => 1,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [
                                    'Купите аспирин по цене 200'
                                ],
                                'children' => []
                            ],
                            [
                                'id' => 3,
                                'name' => 'Группа 1.2',
                                'parent' => 1,
                                'description_format' => 'Покупайте больше %name%',
                                'inheritable' => true,
                                'products' => [],
                                'children' => [
                                    [
                                        'id' => 4,
                                        'name' => 'Группа 1.2.1',
                                        'parent' => 3,
                                        'description_format' => '',
                                        'inheritable' => false,
                                        'products' => [],
                                        'children' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'product 4' => [
                'params' => [
                    $product4,
                    'children', 'products', 'категория', 'formatted_text'
                ],
                'expected_tree' => [
                    [
                        'id' => 1,
                        'name' => 'Группа 1',
                        'parent' => '',
                        'description_format' => 'Купите %наименование% по цене %цена%',
                        'inheritable' => true,
                        'products' => [],
                        'children' => [
                            [
                                'id' => 2,
                                'name' => 'Группа 1.1',
                                'parent' => 1,
                                'description_format' => '',
                                'inheritable' => false,
                                'products' => [],
                                'children' => []
                            ],
                            [
                                'id' => 3,
                                'name' => 'Группа 1.2',
                                'parent' => 1,
                                'description_format' => 'Покупайте больше %name%',
                                'inheritable' => true,
                                'products' => [],
                                'children' => [
                                    [
                                        'id' => 4,
                                        'name' => 'Группа 1.2.1',
                                        'parent' => 3,
                                        'description_format' => '',
                                        'inheritable' => false,
                                        'products' => [
                                            'Покупайте больше анальгин'
                                        ],
                                        'children' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function test_is_sequential_array()
    {
        $sequential_array = [
            1, 2, 3, 4, 'asdf', [1, 2, 3, 4]
        ];

        $associative_array = [
            'asdf' => 1234,
            '1234' => 'asdf',
            'zxcv' => [1, 2, 3, 4]
        ];

        $sequential_result = is_sequential_array($sequential_array);

        $this->assertTrue($sequential_result);

        $associative_result = is_sequential_array($associative_array);

        $this->assertFalse($associative_result);
    }

    public function test_node_from_group()
    {
        $group = $this->group_provider()[0];

        $node = node_from_group($group);

        $expected_node = [
            'id' => 1,
            'name' => 'Группа 1',
            'parent' => '',
            'description_format' => 'Купите %наименование% по цене %цена%',
            'inheritable' => true,
            'products' => [],
            'children' => []
        ];

        $this->assertEquals(
            $expected_node,
            $node
        );
    }

    public function test_html_from_group()
    {
        $group = $this->tree_provider()[0]['children'][1];
        $product = $this->product_provider()[3];
        $group['children'][0]['products'][] = replace_placeholder($group['description_format'], $product);

        $expected_html = <<<HTML

\t\t<h1>Группа 1.2</h1>
\t\t<ul>
\t\t\t<li>
\t\t\t\t<h2>Группа 1.2.1</h2>
\t\t\t\t<ul>
\t\t\t\t\t<li><b>Покупайте больше UNDEFINED</b></li>
\t\t\t\t</ul>
\t\t\t</li>
\t\t</ul>
HTML;

        $html = html_from_group($group, 1);

        $this->assertEquals(
            $expected_html,
            $html
        );
    }
}
