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
        $tree = construct_group_tree($this->group_provider());

        $expected_tree = [
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

        $this->assertEquals(
            $expected_tree,
            $tree
        );
    }

    public function test_replace_placeholder()
    {
        $expected_string = 'Купите тавегил по цене 1234';

        $output = replace_placeholder(
            'Купите %наименование% по цене %цена%', 
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

    function test_add_product()
    {
        $tree = [
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

        $product = [
            'id' => 1,
            'категория' => 1,
            'наименование' => 'супрадин',
            'цена' => 100
        ];

        add_product($product, $tree);

        $product = [
            'id' => 4,
            'категория' => 4,
            'наименование' => 'анальгин',
            'цена' => 20
        ];

        add_product($product, $tree);

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
                                    'Покупайте больше анальгин'
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
}
