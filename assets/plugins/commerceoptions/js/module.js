;

jQuery(function() {
    (function($) {
        $('.add-attribute-value').click(function(e) {
            e.preventDefault();

            var tpl = parseTemplate($('script#attrValueTpl').html(), {
                iteration: _co.nextValue++
            });

            var $row = $(tpl).appendTo('.attribute-values');
            $row.find('.value-image').initImageField();
        });

        $(document).on('click', '.delete-attribute-value', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });

        $('.value-image').initImageField();
    })(jQuery);
});
