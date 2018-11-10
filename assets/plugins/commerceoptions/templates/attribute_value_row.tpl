<tr>
    <td>
        <div class="value-image form-cell">
            <div class="preview"></div>
            <input type="text" class="form-control" name="values[<?= $iteration ?>][image]" value="<?= htmlentities($row['image']) ?>">
            <button type="button" class="btn btn-seconday show-browser"><?= $lang['common.select_image'] ?></button>
        </div>
    </td>

    <td>
        <?php if (!empty($row['id'])): ?>
            <input type="hidden" name="values[<?= $iteration ?>][id]" value="<?= $row['id'] ?>">
        <?php endif; ?>

        <input type="text" class="form-control" name="values[<?= $iteration ?>][title]" value="<?= htmlentities($row['title']) ?>">
    </td>

    <td>
        <input type="text" class="form-control" name="values[<?= $iteration ?>][sort]" value="<?= htmlentities($row['sort']) ?>">
    </td>

    <td><a href="#" class="btn btn-sm btn-danger delete-attribute-value"><?= $_lang['delete'] ?></a></td>
</tr>
