<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= !empty($attribute['id']) ? sprintf($lang['common.edit_attribute_caption'], $attribute['title']) : $lang['common.new_attribute_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="javascript:;" class="btn btn-success" onclick="document.getElementById('attribute_form').submit();"><?= $_lang['save'] ?></a>
    <a href="<?= $this->module->makeUrl('options') ?>" class="btn btn-secondary"><?= $_lang['cancel'] ?></a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab"><?= $lang['common.attribute_data'] ?></h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <form action="<?= $module->makeUrl('options/save') ?>" method="post" id="attribute_form">
            <div class="sectionHeader">
                <?= $lang['common.attribute_data'] ?>
            </div>

            <div class="sectionBody">
                <table class="table">
                    <tr>
                        <td width="25%"><?= $lang['module.name_field'] ?></td>
                        <td>
                            <input type="text" name="title" value="<?= htmlentities($module->getFormAttr($attribute, 'title')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['common.sort_title'] ?></td>
                        <td>
                            <input type="text" name="sort" value="<?= htmlentities($module->getFormAttr($attribute, 'sort')) ?>">
                        </td>
                </table>

                <?php if (!empty($attribute['id'])): ?>
                    <input type="hidden" name="attr_id" value="<?= $attribute['id'] ?>">
                <?php endif; ?>
            </div>

            <div class="sectionHeader">
                <?= $lang['common.attribute_values'] ?>
            </div>

            <div class="sectionBody">
                <div class="table-responsive">
                    <table class="table data attribute-values">
                        <thead>
                            <tr>
                                <td><?= $lang['common.image'] ?></td>
                                <td><?= $lang['module.name_field'] ?></td>
                                <td><?= $lang['common.sort_title'] ?></td>
                                <td style="width: 1%;"></td>
                            </tr>
                        </thead>
                    
                        <tbody>
                            <?php foreach ($values as $iteration => $value): ?>
                                <?= $this->render('attribute_value_row.tpl', [
                                    'iteration' => $iteration,
                                    'row'       => $value,
                                ]); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-xs-right">
                    <a href="#" class="btn btn-primary btn-sm add-attribute-value"><?= $lang['common.add_attribute_value'] ?></a>
                </div>
            </div>
        </form>
    </div>
<?php $this->endBlock(); ?>

<?php $this->block('footer'); ?>
    <script src="../assets/plugins/commerceoptions/js/common.js"></script>
    <script src="../assets/plugins/commerceoptions/js/module.js"></script>
    <script>
        var _co = {
            imagesBrowser: '<?= MODX_MANAGER_URL . 'media/browser/' . $modx->getConfig('which_browser') . '/browse.php' ?>',
            thumbsDir: '<?= $modx->getConfig('thumbsDir') ?>',
            nextValue: <?= $iteration + 1 ?>
        };
    </script>

    <script type="text/template" id="attrValueTpl">
        <?= $this->render('attribute_value_row.tpl', [
            'iteration' => '{%iteration%}',
            'row'       => [
                'title' => '',
                'image' => '',
                'sort'  => '',
            ],
        ]); ?>
    </script>
<?php $this->endBlock(); ?>
