<?php

use Helpers\Lexicon;
use Commerce\Module\Renderer;

class CommerceOptions
{
    use Commerce\Module\CustomizableFieldsTrait;

    public $lexicon;

    private $params = [];
    private $view;

    private $tableProductOptions;
    private $tableProductOptionValues;
    private $tableOptionValues;
    private $tableOptions;

    public function __construct($params)
    {
        $this->params = $params;

        $modx = ci()->modx;
        $this->tableProductOptions      = $modx->getFullTablename('commerce_product_options');
        $this->tableProductOptionValues = $modx->getFullTablename('commerce_product_option_values');
        $this->tableOptionValues        = $modx->getFullTablename('commerce_option_values');
        $this->tableOptions             = $modx->getFullTablename('commerce_options');

        $this->lexicon = new Lexicon($modx, [
            'langDir' => 'assets/plugins/commerceoptions/lang/',
            'lang'    => $modx->getConfig('manager_language'),
        ]);
    }

    public function beforeItemAdding($instance, &$item)
    {
        if ($instance == 'cart') {
            $modx  = ci()->modx;

            if (!empty($item['meta']['comoptions_set']) && is_numeric($item['meta']['comoptions_set'])) {
                $query = $modx->db->select('*', $this->tableProductOptions, "`id` = '" . intval($item['meta']['comoptions_set']) . "' AND `product_id` = '" . $item['id'] . "'");
                $row = $modx->db->getRow($query);

                if ($row) {
                    $item['price'] = ci()->optionsProcessor->modifyPrice($item['price'], $row['modifier'], $row['amount']);
                }
            } else {
                $query = $modx->db->select('*', $this->tableProductOptions, "`product_id` = '" . $item['id'] . "'");

                if ($modx->db->getRecordCount($query) > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function modifyPrice($price, $modifier, $amount)
    {
        if ($amount > 0) {
            switch ($modifier) {
                case 'add': {
                    $price += $amount;
                    break;
                }

                case 'subtract': {
                    $price -= $amount;
                    break;
                }

                case 'multiply': {
                    $price *= $amount;
                    break;
                }

                case 'replace': {
                    $price = $amount;
                    break;
                }
            }
        }

        return $price;
    }

    /**
     * Called at OnManagerBeforeDefaultCurrencyChange event.
     * When manager changes the default currency, we recalculate all modifiers
     */
    public function changeOptionsCurrency()
    {
        $db = ci()->db;
        $currency = ci()->currency;
        $query = $db->select('*', $this->tableProductOptions, "`amount` != 0");

        while ($row = $db->getRow($query)) {
            $amount = $currency->convert($row['amount'], $this->params['old']['code'], $this->params['new']['code']);
            $db->update(['amount' => $amount], $this->tableProductOptions, "`id` = '" . $row['id'] . "'");
        }
    }

    public function renderOptions($params)
    {
        $modx = ci()->modx;

        $query = $modx->db->query("
            SELECT po.*, ov.option_id, o.title AS option_title, pov.value_id, ov.title AS value_title, ov.image AS value_image
            FROM {$this->tableProductOptions} po
            JOIN {$this->tableProductOptionValues} pov ON pov.option_id = po.id
            JOIN {$this->tableOptionValues} ov ON pov.value_id = ov.id
            JOIN {$this->tableOptions} o ON ov.option_id = o.id
            WHERE po.product_id = '" . $params['docid'] . "'
            ORDER BY o.sort, ov.sort
        ");

        $options = [];
        $sets = [];

        while ($row = $modx->db->getRow($query)) {
            if (!isset($options[$row['option_id']])) {
                $options[$row['option_id']] = [
                    'id'     => $row['option_id'],
                    'title'  => $row['option_title'],
                    'values' => [],
                ];
            }

            $options[$row['option_id']]['values'][$row['value_id']] = [
                'id'    => $row['value_id'],
                'title' => $row['value_title'],
                'image' => $row['value_image'],
            ];

            if (!isset($sets[$row['id']])) {
                $sets[$row['id']] = [
                    'row'     => array_intersect_key($row, array_flip(['id', 'modifier', 'amount'])),
                    'options' => [],
                ];
            }

            $sets[$row['id']]['options'][] = $row['value_id'];
        }

        $relations = [];
        $prices    = [];
        $currency  = ci()->currency;

        foreach ($sets as $id => $values) {
            sort($values['options'], SORT_NUMERIC);

            if ($values['row']['amount'] > 0) {
                $setPrice = $this->modifyPrice($params['price'], $values['row']['modifier'], $values['row']['amount']);
                $setPrice = $currency->formatWithActive($setPrice);
            } else {
                $setPrice = true;
            }

            $prices[implode('-', $values['options'])] = [
                'price' => $setPrice,
                'id'    => $values['row']['id'],
            ];

            foreach ($values['options'] as $val) {
                if (!isset($relations[$val])) {
                    $relations[$val] = [$val => $val];
                }

                foreach ($values['options'] as $relval) {
                    if ($relval != $val) {
                        $relations[$val][$relval] = $relval;
                    }
                }
            }
        }

        foreach ($relations as $id => $ids) {
            $relations[$id] = array_values($ids);
        }

        require_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';
        $DLTemplate = DLTemplate::getInstance($modx);

        $out = '';

        foreach ($options as $option) {
            $_out = '';

            $option['e'] = [
                'title' => htmlentities($option['title']),
            ];

            foreach ($option['values'] as $value) {
                $_out .= $DLTemplate->parseChunk($params['valueTpl'], array_merge($value, [
                    'option' => $option,
                    'e' => [
                        'title' => htmlentities($value['title']),
                    ],
                ]));
            }

            $out .= $DLTemplate->parseChunk($params['optionTpl'], array_merge($option, [
                'values' => $_out,
            ]));
        }

        if (!empty($out)) {
            $out = $DLTemplate->parseChunk($params['ownerTPL'], [
                'wrap'   => $out,
                'hidden' => '<input type="hidden" name="meta[comoptions_set]" value="">',
            ]);

            $modx->regClientScript('<script type="text/javascript">
                var _co = {
                    rel: ' . json_encode($relations) . ',
                    prices: ' . json_encode($prices) . '
                };
            </script>');

            $modx->regClientScript('assets/plugins/commerceoptions/js/front.js');
        }

        return $out;
    }

    public function loadAttributes($cached = true)
    {
        if (is_null($this->attributes)) {
            $this->attributes = ci()->cache->getOrCreate('comoptions_attributes', function() {
                $db = ci()->db;
                $result = [];
                $query = $db->select('*', $this->tableOptions, null, "sort ASC");

                while ($row = $db->getRow($query)) {
                    $result[$row['id']] = $row;
                }

                $query = $db->select('*', $this->tableOptionValues, null, "sort ASC");

                while ($row = $db->getRow($query)) {
                    if (!isset($result[$row['option_id']])) {
                        continue;
                    }

                    if (!isset($result[$row['option_id']]['values'])) {
                        $result[$row['option_id']]['values'] = [];
                    }

                    $result[$row['option_id']]['values'][$row['id']] = $row;
                }

                return $result;
            });
        }

        return $this->attributes;
    }

    public function saveForm($params)
    {

        $docid = $params['id'];

        if (!empty($_POST['comoptions']) && is_array($_POST['comoptions'])) {
            $data = $_POST['comoptions'];

            ci()->modx->invokeEvent('OnManagerBeforeComerceOptionsSaving', [
                'data' => &$data,
            ]);

            $modx = ci()->modx;
            $db = ci()->db;

            $db->query("START TRANSACTION;");

            try {
                $exists = [];

                array_walk($data, function($option) use (&$exists) {
                    if (!empty($option['id']) && is_numeric($option['id'])) {
                        $exists[] = $option['id'];
                    }
                });

                $delete = [];
                $query  = $db->select('id', $this->tableProductOptions, "`product_id` = '$docid' AND `id` NOT IN ('" . implode("','", $exists) . "')");

                while ($row = $db->getRow($query)) {
                    $delete[] = $row['id'];
                }

                if (!empty($delete)) {
                    $delete = "('" . implode("','", $delete) . "')";
                    $db->delete($this->tableProductOptions, "`id` IN $delete");
                    $db->delete($this->tableProductOptionValues, "`option_id` IN $delete");
                }

                foreach ($data as $option) {
                    $optionHasId = !empty($option['id']);

                    if (!empty($option['attributes']) && is_array($option['attributes'])) {
                        $meta = json_encode($option['meta'], JSON_UNESCAPED_UNICODE);
                        $insert = [
                            'product_id'   => $docid,
                            'code'         => $db->escape($option['code']),
                            'title'        => $db->escape($option['title']),
                            'title_locked' => !empty($option['title_locked']) ? 1 : 0,
                            'image'        => $db->escape($option['image']),
                            'modifier'     => $db->escape($option['modifier']),
                            'amount'       => floatval($option['amount']),
                            'count'        => floatval($option['count']),
                            'meta'         => !empty($meta) && $meta != 'null' ? $db->escape($meta) : null,
                            'active'       => !empty($option['active']) ? 1 : 0,
                        ];

                        if ($optionHasId) {
                            $update = [];

                            foreach ($insert as $key => $value) {
                                if (!is_null($value)) {
                                    $insert[$key] = "'$value'";
                                } else {
                                    $insert[$key] = 'NULL';
                                }

                                $update[] = "`$key` = " . (is_null($value) ? "NULL" : "'$value'");
                            }

                            $insert['id'] = intval($option['id']);

                            $db->query("INSERT INTO " . $this->tableProductOptions . " (`" . implode('`, `', array_keys($insert)) . "`) VALUES (" . implode(", ", $insert) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update));

                            $result = $db->getInsertId();
                            if (!empty($result)) {
                                $option['id'] = $result;
                            }
                        } else {
                            $option['id'] = $db->insert($insert, $this->tableProductOptions);
                        }

                        $exists = [];
                        $delete = [];

                        array_walk($option['attributes'], function($attr) use (&$exists) {
                            if (!empty($attr['id']) && is_numeric($attr['id'])) {
                                $exists[] = $attr['id'];
                            }
                        });

                        $query  = $db->select('id', $this->tableProductOptionValues, "`option_id` = '" . $option['id'] . "' AND `id` NOT IN ('" . implode("','", $exists) . "')");

                        while ($row = $db->getRow($query)) {
                            $delete[] = $row['id'];
                        }

                        if (!empty($delete)) {
                            $db->delete($this->tableProductOptionValues, "`id` IN ('" . implode("','", $delete) . "')");
                        }

                        foreach ($option['attributes'] as $attr) {
                            $insert = [
                                'id'           => isset($attr['id']) ? intval($attr['id']) : null,
                                'option_id'    => intval($option['id']),
                                'attribute_id' => intval($attr['attribute']),
                                'value_id'     => intval($attr['value']),
                            ];

                            if (!empty($insert['id'])) {
                                foreach ($insert as $key => $value) {
                                    if (!is_null($value)) {
                                        $insert[$key] = "'$value'";
                                    } else {
                                        $insert[$key] = 'NULL';
                                    }
                                }

                                $db->query("INSERT INTO " . $this->tableProductOptionValues . " (`" . implode('`, `', array_keys($insert)) . "`) VALUES (" . implode(", ", $insert) . ") ON DUPLICATE KEY UPDATE `value_id` = " . $insert['value_id']);
                            } else {
                                $db->insert($insert, $this->tableProductOptionValues);
                            }
                        }
                    } else {
                        if ($optionHasId) {
                            $db->delete($this->tableProductOptions, "`id` = '" . $option['id'] . "'");
                            $db->delete($this->tableProductOptionValues, "`option_id` = '" . $option['id'] . "'");
                        }

                        continue;
                    }
                }
            } catch (\Throwable $e) {
                $db->query("ROLLBACK;");
                var_dump($e->getMessage());
                die();
            }

            $db->query("COMMIT;");
        } else {
            $delete = [];
            $query  = $db->select('id', $this->tableProductOptions, "`product_id` = '$docid'");

            while ($row = $db->getRow($query)) {
                $delete[] = $row['id'];
            }

            if (!empty($delete)) {
                $delete = "('" . implode("','", $delete) . "')";
                $db->delete($this->tableProductOptions, "`id` IN $delete");
                $db->delete($this->tableProductOptionValues, "`option_id` IN $delete");
            }
        }
    }

    public function renderForm($params)
    {
        $modx = ci()->modx;
        $this->view = new Renderer($modx, null, ['path' => __DIR__ . '/../templates']);
        $modx->regClientScript('../assets/plugins/commerceoptions/js/product.js');

        $lang = $this->lexicon->loadLang(['common', 'tab']);
        $modifiers = ['add', 'subtract', 'multiply', 'replace'];

        $options = [];
        $query = $modx->db->select('*', $this->tableProductOptions, "`product_id` = '" . $params['id'] . "'");

        while ($row = $modx->db->getRow($query)) {
            $options[$row['id']] = $row;
        }

        if (!empty($options)) {
            $query = $modx->db->select('*', $this->tableProductOptionValues, "`option_id` IN (" . implode(',', array_keys($options)) . ")");

            while ($row = $modx->db->getRow($query)) {
                if (!isset($options[$row['option_id']]['values'])) {
                    $options[$row['option_id']]['values'] = [];
                }

                $options[$row['option_id']]['values'][] = $row;
            }

            $options = array_values($options);
        }

        $columns = $this->getOptionsColumns($modifiers, $lang);
        $fields  = $this->getOptionFields($modifiers, $lang);
        $config  = [];

        $modx->invokeEvent('OnManagerBeforeCommerceOptionsRender', [
            'id'      => $params['id'],
            'columns' => &$columns,
            'fields'  => &$fields,
        ]);

        $columns = $this->sortFields($columns);
        $fields  = $this->sortFields($fields);

        $blankData = [
            'iteration' => '{%iteration%}',
            'title'     => $lang['tab.new_option_caption'],
            'count'     => 1,
            'active'    => 1,
        ];

        $blankData = array_merge($blankData, [
            'cells'  => $this->processFields($columns, ['data' => $blankData]),
            'fields' => $this->processFields($fields, ['data' => $blankData]),
        ]);

        foreach ($options as $iteration => &$option) {
            $data = array_merge($option, [
                'iteration' => $iteration,
            ]);

            $option['cells']  = $this->processFields($columns, ['data' => $data]);
            $option['fields'] = $this->processFields($fields, ['data' => $data]);
        }

        unset($option);

        return $this->view->render('product_tab.tpl', [
            'columns'     => $columns,
            'fields'      => $fields,
            'options'     => $options,
            'attributes'  => $this->loadAttributes(false),
            'lang'        => $lang,
            'blankData'   => $blankData,
            'browseUrl'   => MODX_MANAGER_URL . 'media/browser/' . $modx->getConfig('which_browser') . '/browse.php',
            'thumbsDir'   => $modx->getConfig('thumbsDir'),
        ]);
    }

    private function getOptionsColumns($modifiers, $lang)
    {
        $modx = ci()->modx;

        return [
            'iteration' => [
                'title'   => '#',
                'content' => function($data) {
                    return $data['id'];
                },
                'style'   => 'width: 1%; text-align: center;',
                'sort'    => -999999,
            ],
            'image' => [
                'title' => $lang['common.image'],
                'content' => function($data) use ($modx) {
                    if (!empty($data['image'])) {
                        $image = $modx->getConfig('site_url') . $modx->runSnippet('phpthumb', [
                            'input'   => $data['image'],
                            'options' => 'w=80,h=50,f=jpg,bg=FFFFFF,far=C',
                        ]);
                    } else {
                        $image = '';
                    }

                    return '<img src="' . $image . '" alt="" class="option-image-preview">';
                },
                'sort' => 20,
            ],
            'title' => [
                'title'   => $lang['common.option_name'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][title]" value="' . htmlentities($data['title']) . '">';
                },
                'sort' => 30,
            ],
            'modifier' => [
                'title'   => $lang['common.option_price_modifier'],
                'content' => function($data) use ($modifiers, $lang) {
                    $out = '';

                    foreach ($modifiers as $modifier) {
                        $out .= '<option value="' . $modifier . '"' . ($modifier == $data['modifier'] ? ' selected' : '') . '>' . $lang['common.modifier_' . $modifier] . '</option>';
                    }

                    return '
                        <select class="form-control" name="comoptions[' . $data['iteration'] . '][modifier]" size="1">
                            ' . $out . '
                        </select>
                        <input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][amount]" value="' . ($data['amount'] != 0 ? $data['amount'] : '') . '" style="width: 50px; text-align: right;">
                    ';
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 40,
            ],
            'count' => [
                'title'   => $lang['tab.count'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][count]" value="' . htmlentities($data['count']) . '" style="width: 70px; text-align: right;">';
                },
                'sort'    => 50,
                'style'   => 'text-align: right;',
            ],
        ];
    }

    private function getOptionFields($modifiers, $lang)
    {
        $modx = ci()->modx;

        return [
            'image' => [
                'title'   => $lang['common.image'],
                'content' => function($data) use ($lang) {
                    return '
                        <div class="option-image">
                            <div class="preview"></div>
                            <input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][image]" value="' . htmlentities($data['image']) . '"><button type="button" class="btn btn-seconday show-browser">' . $lang['common.select_image'] . '</button>
                        </div>
                    ';
                },
                'sort' => 10,
            ],
            'modifier' => [
                'title'   => $lang['tab.modifier'],
                'content' => function($data) use ($modifiers, $lang) {
                    $out = '';

                    foreach ($modifiers as $modifier) {
                        $out .= '<option value="' . $modifier . '"' . ($modifier == $data['modifier'] ? ' selected' : '') . '>' . $lang['common.modifier_' . $modifier] . '</option>';
                    }

                    return '
                        <select name="comoptions[' . $data['iteration'] . '][modifier]" class="form-control">
                            ' . $out . '
                        </select>
                    ';
                },
                'sort'  => 20,
                'width' => '33.333%',
            ],
            'amount' => [
                'title' => $lang['tab.amount'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][amount]" value="' . ($data['amount'] != 0 ? $data['amount'] : '') . '" style="text-align: right;">';
                },
                'sort'  => 30,
                'style' => 'text-align: right;',
                'width' => '33.333%',
            ],
            'count' => [
                'title'   => $lang['tab.count'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="comoptions[' . $data['iteration'] . '][count]" value="' . htmlentities($data['count']) . '" style="text-align: right;">';
                },
                'sort'  => 40,
                'style' => 'text-align: right;',
                'width' => '33.333%',
            ],
        ];
    }
}
