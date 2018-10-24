//<?php
/**
 * Commerce Options
 *
 * Commerce Options
 *
 * @category    plugin
 * @author      mnoskov
 * @version     0.1.0
 * @internal    @events OnInitializeCommerce,OnBeforeCartItemAdding,OnManagerRegisterCommerceController,OnManagerMenuPrerender,OnDocFormRender,OnManagerBeforeDefaultCurrencyChange
 * @internal    @installset base
*/

$e = &$modx->Event;

switch ($e->name) {
    case 'OnInitializeCommerce': {
        ci()->set('optionsProcessor', function($ci) use ($params) {
            require_once MODX_BASE_PATH . 'assets/plugins/commerceoptions/src/CommerceOptions.php';
            return new CommerceOptions($params);
        });

        break;
    }

    case 'OnDocFormRender': {
        //$e->output($ci()->optionsProcessor->renderForm());
        break;
    }

    case 'OnBeforeCartItemAdding': {
        if (ci()->optionsProcessor->beforeItemAdding($instance, $params['item']) === false) {
            $e->setOutput(false);
        }

        break;
    }

    case 'OnManagerBeforeDefaultCurrencyChange': {
        $ci()->optionsProcessor->changeOptionsCurrency();
        break;
    }

    case 'OnManagerRegisterCommerceController': {
        require_once MODX_BASE_PATH . 'assets/plugins/commerceoptions/src/ModuleController.php';
        $module->registerController('options', new ModuleController($modx, $module));
        break;
    }

    case 'OnManagerMenuPrerender': {
        $moduleid = $modx->db->getValue($modx->db->select('id', $modx->getFullTablename('site_modules'), "name = 'Commerce'"));
        $url = 'index.php?a=112&id=' . $moduleid;

        $params['menu']['commerce_options'] = ['commerce_options', 'commerce', '<i class="fa fa-cog"></i>Опции', $url . '&route=options', 'Опции', '', 'exec_module', 'main', 0, 40, ''];

        $e->output(serialize($params['menu']));
        break;
    }
}
