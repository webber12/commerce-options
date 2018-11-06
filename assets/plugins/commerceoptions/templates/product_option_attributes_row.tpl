<tr class="option-attribute" data-iteration="<?= $attribute['sort'] ?>">
    <td>
        <?php if (!empty($row['id'])): ?>
            <input type="hidden" name="comoptions[<?= $num ?>][attributes][<?= $attribute['sort'] ?>][id]" value="<?= $row['id'] ?>">
        <?php endif; ?>

        <input type="hidden" name="comoptions[<?= $num ?>][attributes][<?= $attribute['sort'] ?>][attribute]" value="<?= $row['attribute_id'] ?>" class="attribute_id">
        <?= $attribute['title'] ?>
    <td>
        <input type="hidden" name="comoptions[<?= $num ?>][attributes][<?= $attribute['sort'] ?>][value]" value="<?= $row['value_id'] ?>" class="value_id">
    <td>
        <a href="#" class="btn btn-sm btn-danger remove-attribute">&times;</a>
</tr>
