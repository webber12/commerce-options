<?php

class CommerceOptions
{
    private $params = [];

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
                    $price += $price * $amount;
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

                $prices[implode('-', $values['options'])] = [
                    'price' => $currency->formatWithActive($setPrice),
                    'id'    => $values['row']['id'],
                ];
            }

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

    public function renderForm()
    {
        return '';
    }
}
