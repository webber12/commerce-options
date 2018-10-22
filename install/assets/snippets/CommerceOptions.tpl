//<?php
/**
 * CommerceOptions
 * 
 * CommerceOptions renderer
 * 
 * @category    snippet
 * @author      mnoskov
 * @version     0.1.0
 * @internal    @overwrite true
*/

$params = array_merge([
    'docid'     => $modx->documentIdentifier,
    'price'     => false,
    'valueTpl'  => '@CODE:<li data-comoptions-value="[+id+]"><label><input type="checkbox" name="meta[comoptions][]" value="[+id+]">[+title+]</label>',
    'optionTpl' => '@CODE:<div class="option" data-comoptions-option="[+id+]"><strong>[+e.title+]</strong><br><ul>[+values+]</ul></div>',
    'ownerTPL'  => '@CODE:<div class="options">[+wrap+][+hidden+]</div>',
], $params);

if ($params['price'] === false) {
    $tvname = ci()->commerce->getSetting('price_field', 'price');
    $tv = $modx->getTemplateVarOutput([$tvname], $params['docid']);
    $params['price'] = $tv[$tvname];
}

return ci()->optionsProcessor->renderOptions($params);
