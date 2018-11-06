<div class="tab-page commerce-options" id="tabComOptions">
    <h2 class="tab"><?= $lang['tab.tab_caption'] ?></h2>

    <p class="pull-right">
        <a href="#" class="btn btn-primary btn-sm add-option"><?= $lang['tab.add_option'] ?></a>
    </p>

    <div class="table-responsive">
        <table class="table data">
            <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <td<?= !empty($column['style']) ? ' style="' . $column['style'] . '"' : '' ?>><?= $column['title'] ?></td>
                    <?php endforeach; ?>
                    <td style="width: 1%;"></td>
                </tr>
            </thead>
        
            <tbody class="option-rows">
                <?php foreach ($options as $num => $row): ?>
                    <?= $this->render('product_options_row.tpl', [
                        'row'        => $row,
                        'num'        => $num,
                        'columns'    => $columns,
                        'fields'     => $fields,
                        'lang'       => $lang,
                        'attributes' => $attributes,
                    ]) ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <table></table>
</div>

<link rel="stylesheet" type="text/css" href="../assets/plugins/commerceoptions/css/product.css">
<script src="../assets/plugins/commerceoptions/js/jquery.autocomplete.min.js"></script>
<script src="../assets/plugins/commerceoptions/js/product.js"></script>
<script>
    var _co = {
        nextOptionRow: <?= count($options) ?>,
        attributes: <?= json_encode($attributes, JSON_UNESCAPED_UNICODE) ?>,
        imagesBrowser: '<?= $browseUrl ?>',
        thumbsDir: '<?= $thumbsDir ?>'
    };

    tpSettings.addTabPage(jQuery('#tabComOptions').get(0));
</script>

<script type="text/template" id="optRowTpl">
    <?= $this->render('product_options_row.tpl', [
        'row'        => $blankData,
        'num'        => '{%iteration%}',
        'columns'    => $columns,
        'fields'     => $fields,
        'lang'       => $lang,
        'attributes' => $attributes,
    ]) ?>
</script>

<script type="text/template" id="attrRowTpl">
    <?= $this->render('product_option_attributes_row.tpl', [
        'i'         => '{%iteration%}',
        'num'       => '{%option_iteration%}',
        'attribute' => [
            'title' => '{%attribute_title%}',
            'sort'  => '{%attribute_sort%}'
        ],
        'row'       => [
            'attribute_id' => '{%attribute_id%}',
            'value_id'     => '{%value_id%}',
        ],
    ]) ?>
</script>
