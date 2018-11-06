;

(function() {
    $.fn.updateOptionsState = function() {
        this.each(function() {
            var $container = $(this),
                $price     = $container.find('[data-commerce-price]'),
                $hidden    = $container.find('[name="meta[comoptions_set]"]'),
                $values    = $container.find('[data-comoptions-value]'),
                $btn       = $container.find('button[type="submit"]')
                $inputs    = $values.find('input'),
                $checked   = $values.find(':checked'),
                defPrice   = $price.data('defaultPrice'),
                checked    = [],
                $available = $([]),
                available  = false;

            if (!$values.length) {
                return;
            }
        
            if ($checked.length) {
                $checked.each(function() {
                    var id = parseInt($(this).closest('[data-comoptions-value]').attr('data-comoptions-value'));

                    if (id) {
                        checked.push(id);
                        
                        if (_co.rel[id]) {
                            if (available === false) {
                                available = _co.rel[id];
                            } else {
                                available = $(available).filter(_co.rel[id]).toArray();
                            }
                        }
                    }
                });

                $available = $checked;

                for (var i = 0; i < available.length; i++) {
                    $available = $available.add($('[data-comoptions-value="' + available[i] + '"] input'));
                }
                
                $inputs.not($available).attr('disabled', true);
                $available.removeAttr('disabled');
                
                var hash = checked.sort().join('-');
                
                if (_co.prices[hash] && _co.prices[hash].price !== true) {
                    $price.html(_co.prices[hash].price);
                    $hidden.val(_co.prices[hash].id).removeAttr('disabled');
                } else {
                    $price.html(defPrice);
                    $hidden.val('').attr('disabled', true);
                }

                if (_co.prices[hash] || $available.length == $checked.length) {
                    $btn.removeAttr('disabled');
                } else {
                    $btn.attr('disabled', true);
                }
            } else {
                $inputs.removeAttr('disabled');
                $btn.attr('disabled', true);
                $price.html(defPrice);
                $hidden.val('').attr('disabled', true);
            }
        });

        return this;
    };

    $('[data-commerce-price]').each(function() {
        $(this).data('defaultPrice', this.innerHTML);
    });

    $('form[data-commerce-action="add"]').change(function() {
        $(this).updateOptionsState();
    }).change();
})();
