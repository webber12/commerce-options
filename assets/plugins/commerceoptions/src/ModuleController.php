<?php

class ModuleController extends Commerce\Module\Controllers\Controller
{
    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->view->setPath('assets/plugins/commerceoptions/templates/');
    }

    public function index()
    {
        return $this->view->render('options_list.tpl', [
            'list'   => [],
            'custom' => $this->module->invokeTemplateEvent('OnManagerCommerceOptionsRender'),
        ]);
    }
}
