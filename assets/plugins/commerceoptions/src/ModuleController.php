<?php

class ModuleController extends Commerce\Module\Controllers\Controller
{
    private $table = 'commerce_options';
    private $tableValues = 'commerce_option_values';

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->view->setPath('assets/plugins/commerceoptions/templates/');
        $this->view->setLang(ci()->optionsProcessor->lexicon->loadLang('common'));
        $this->table = $modx->getFullTablename($this->table);
        $this->tableValues = $modx->getFullTablename($this->tableValues);
    }

    public function registerRoutes()
    {
        return [
            'index'  => 'index',
            'edit'   => 'edit',
            'save'   => 'save',
            'delete' => 'delete',
        ];
    }

    public function index()
    {
        $list = $this->modx->runSnippet('DocLister', [
            'controller'      => 'onetable',
            'table'           => 'commerce_options',
            'idType'          => 'documents',
            'orderBy'         => 'sort',
            'id'              => 'list',
            'showParent'      => '-1',
            'api'             => 1,
            'ignoreEmpty'     => 1,
            'makePaginateUrl' => function($link, $modx, $DL, $pager) use ($ordersUrl) {
                return $ordersUrl;
            },
        ]);

        $list = json_decode($list, true);

        return $this->view->render('attributes_list.tpl', [
            'list'   => $list,
            'custom' => $this->module->invokeTemplateEvent('OnManagerCommerceAttributesListRender'),
        ]);
    }

    public function edit()
    {
        $attr_id = filter_input(INPUT_GET, 'attr_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($attr_id)) {
            $db = ci()->db;
            $query = $db->select('*', $this->table, "`id` = '$attr_id'");
            $attr = $db->getRow($query);

            if (empty($attr)) {
                $this->module->sendRedirect('attr', ['error' => $this->lang['common.error.attribute_not_found']]);
            }

            $values = $db->makeArray($db->select('*', $this->tableValues, "`option_id` = '$attr_id'", "`sort` ASC"));
        } else {
            $attr = [];
            $values = [];
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['values'])) {
            $values = $_POST['values'];
        }

        return $this->view->render('attribute_edit.tpl', [
            'attribute' => $attr,
            'values' => $values,
            'custom' => $this->module->invokeTemplateEvent('OnManagerCommerceAttributeRender'),
        ]);
    }

    public function save()
    {
        $db = ci()->db;
        $attr_id = filter_input(INPUT_POST, 'attr_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($attr_id)) {
            $query  = $db->select('*', $this->table, "`id` = '$attr_id'");
            $attr = $db->getRow($query);

            if (empty($attr)) {
                $this->module->sendRedirect('attr', ['error' => $this->lang['common.error.attribute_not_found']]);
            }
        } else {
            $attr = [];
        }

        $data = $_POST;

        $result = $this->modx->commerce->validate($data, [
            'title' => [
                'lengthBetween' => [
                    'params'  => [2, 255],
                    'message' => 'title should be between 2 and 255 symbols',
                ],
            ],
        ]);

        if (is_array($result)) {
            $this->module->sendRedirectBack(['validation_errors' => $result]);
        }

        $fields = [
            'title' => $db->escape($data['title']),
            'sort'  => intval($data['sort']),
        ];

        $exists = [];

        if (!empty($data['values']) && is_array($data['values'])) {
            $ids = [];

            foreach ($data['values'] as $value) {
                if (!empty($value['id']) && is_numeric($value['id'])) {
                    $ids[] = $value['id'];
                }
            }

            $exists = $db->getColumn('id', $db->select('id', $this->tableValues, "`id` IN ('" . implode("', '", $ids) . "'')"));
            $exists = array_flip($exists);
        }

        $db->query('START TRANSACTION;');

        try {
            if (!empty($attr['id'])) {
                $db->update($fields, $this->table, "`id` = '" . $attr['id'] . "'");
            } else {
                $attr['id'] = $db->insert($fields, $this->table);
            }

            if (!empty($data['values']) && is_array($data['values'])) {
                foreach ($data['values'] as $value) {
                    if (empty($value['title'])) {
                        continue;
                    }

                    $fields = [
                        'option_id' => $attr['id'],
                        'title'     => $db->escape($value['title']),
                        'image'     => $db->escape($value['image']),
                        'sort'      => intval($value['sort']),
                    ];

                    if (!empty($value['id']) && is_numeric($value['id']) && isset($exists[$value['id']])) {
                        $db->update($fields, $this->tableValues, "`id` = '" . $value['id'] . "'");
                    } else {
                        $db->insert($fields, $this->tableValues);
                    }
                }
            }
        } catch (\Exception $e) {
            $db->query('ROLLBACK;');
            $this->module->sendRedirectBack(['error' => $e->getMessage()]);
        }

        $db->query('COMMIT;');
        $this->modx->clearCache('full');
        $this->module->sendRedirect('attr', ['success' => $this->lang['common.attribute_saved']]);
    }

    public function delete()
    {
        $attr_id = filter_input(INPUT_GET, 'attr_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($attr_id)) {
            try {
                $db = ci()->db;
                $row = $db->getRow($db->select('*', $this->table, "`id` = '$attr_id'"));

                if (!empty($row)) {
                    $db->delete($this->tableValues, "`option_id` = '$attr_id'");
                    $db->delete($this->table, "`id` = '$attr_id'");
                    $this->module->sendRedirect('attr', ['success' => $this->lang['common.attribute_deleted']]);
                }
            } catch (\Exception $e) {
                $this->module->sendRedirect('attr', ['error' => $e->getMessage()]);
            }
        }

        $this->module->sendRedirect('attr', ['error' => $this->lang['common.error.attribute_not_found']]);
    }
}
