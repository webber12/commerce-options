<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= $lang['common.menu_title'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="<?= $module->makeUrl('attributes/edit') ?>" class="btn btn-success"><?= $lang['common.add_attribute'] ?></a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            <?= $lang['common.menu_title'] ?>
        </h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="row">
            <div class="table-responsive">
                <table class="table data">
                    <thead>
                        <tr>
                            <td style="width: 1%; text-align: center;">#</td>
                            <td><?= $lang['module.name_field'] ?></td>
                            <td style="width: 1%;"></td>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td style="width: 1%; text-align: center;"><?= $row['id'] ?></td>
                                <td><?= $row['title'] ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="<?= $this->module->makeUrl('attributes/edit', 'attr_id=' . $row['id']) ?>" class="btn btn-primary btn-sm">
                                        <?= $lang['common.edit_btn'] ?>
                                    </a>

                                    <a href="<?= $this->module->makeUrl('attributes/delete', 'attr_id=' . $row['id']) ?>" class="btn btn-danger btn-sm">
                                        <?= $lang['common.delete_btn'] ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $this->endBlock(); ?>
