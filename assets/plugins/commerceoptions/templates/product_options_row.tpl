<tr data-id="<?= $row['id'] ?>" data-iteration="<?= $num ?>">
    <?php foreach ($row['cells'] as $name => $cell): ?>
        <td class="custom-cell"<?= !empty($columns[$name]['style']) ? ' style="' . $columns[$name]['style'] . '"' : '' ?>><?= $cell ?></td>
    <?php endforeach; ?>

    <td style="white-space: nowrap; text-align: right;">
        <?php if (!empty($row['id'])): ?>
            <input type="hidden" name="comoptions[<?= $num ?>][id]" value="<?= $row['id'] ?>">
        <?php endif; ?>

        <a href="#" class="btn btn-primary btn-sm edit-option"><?= $lang['common.edit_btn'] ?></a>
        <a href="#" class="btn btn-danger btn-sm remove-option"><?= $lang['common.delete_btn'] ?></a>

        <div class="window-contents" style="display: none;" data-title="<?= htmlentities($lang['tab.edit_option_caption']) ?>">
            <div class="comoptions-popup">
                <ul class="cells-wrap">
                    <li>
                        <div class="form-cell">
                            <div class="label"><?= $lang['common.option_name'] ?>:</div>

                            <label class="title-locked">
                                <input type="checkbox" name="comoptions[<?= $num ?>][title_locked]" value="1"<?= !empty($row['title_locked']) ? ' checked' : '' ?> title="<?= $lang['tab.title_locked_descr'] ?>" class="title-locked">
                                <span class="icon">
                                    <i class="fa fa-lock"></i>
                                    <i class="fa fa-unlock"></i>
                                </span>
                            </label>

                            <input type="text" class="form-control option-title" name="comoptions[<?= $num ?>][title]" value="<?= htmlentities($row['title']) ?>">
                        </div>

                    <li>
                        <div class="form-cell">
                            <label style="margin: 0;">
                                <input type="hidden" name="comoptions[<?= $num ?>][active]" value="0">
                                <input type="checkbox" name="comoptions[<?= $num ?>][active]" value="1" class="checkbox"<?= !empty($row['active']) ? ' checked' : '' ?>>
                                <?= $lang['common.active'] ?>
                            </label>
                        </div>

                    <?php foreach ($row['fields'] as $name => $field): ?>
                        <li<?= !empty($fields[$name]['width']) ? ' style="width: ' . $fields[$name]['width'] . '"' : '' ?>>
                            <div class="form-cell"<?= !empty($fields[$name]['style']) ? ' style="' . $fields[$name]['style'] . '"' : '' ?>>
                                <div class="label"><?= $fields[$name]['title'] ?>:</div>
                                <?= $field ?>
                            </div>
                    <?php endforeach; ?>
                </ul>
                
                <p><?= $lang['common.atributes'] ?>:</p>
                
                <div class="table-responsive">
                    <table class="table data option-attributes">
                        <thead>
                            <tr>
                                <td style="width: 45%;"><?= $lang['common.attribute_name'] ?></td>
                                <td style="width: 55%;"><?= $lang['common.attribute_value'] ?></td>
                                <td style="width: 1%;"></td>
                            </tr>
                        </thead>
                    
                        <tbody>
                            <?php foreach ($row['values'] as $i => $value): ?>
                                <?php if (isset($attributes[$value['attribute_id']]) && isset($attributes[$value['attribute_id']]['values'][$value['value_id']])): ?>
                                    <?= $this->render('product_option_attributes_row.tpl', [
                                        'num'       => $num,
                                        'i'         => $i,
                                        'row'       => $value,
                                        'attribute' => $attributes[$value['attribute_id']],
                                        'value'     => $attributes[$value['attribute_id']]['values'][$value['value_id']],
                                    ]) ?>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <tr>
                                <td>
                                    <?= $lang['common.add_attribute'] ?>:
                                    <select class="new-attribute form-control"></select>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </td>
</tr>
