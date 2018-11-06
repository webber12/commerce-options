;

jQuery(function() {
    (function($) {
        var optRowTpl  = $('script#optRowTpl').html(),
            attrRowTpl = $('script#attrRowTpl').html();

        var parseTemplate = function(tpl, data) {
            for (var key in data) {
                tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'), data[key]);
            }

            return tpl;
        };

        $.fn.updateTitle = function() {
            var $popup = $(this),
                isTitleLocked = $popup.find('.title-locked').is(':checked');

            if (!isTitleLocked) {
                var title = [];

                $popup.find('.option-attributes .value_id').each(function() {
                    var $self    = $(this),
                        value_id = $self.val(),
                        attr_id  = $self.closest('tr').find('.attribute_id').val();

                    if (_co.attributes[attr_id].values[value_id]) {
                        title.push(_co.attributes[attr_id].values[value_id].title);
                    }
                });

                if (title.length) {
                    $popup.find('.option-title').val(title.join(', '));
                }
            }
        };

        $.fn.updateThumb = function() {
            var $field   = $(this),
                source   = $.trim($field.val());
                $preview = $field.closest('.form-cell').find('.preview'),
                thumb    = source.replace('assets/images/', '../assets/' + _co.thumbsDir + '/images/');

            if (source == '') {
                $preview.removeAttr('style');
            } else {
                if (document.images) {
                    var image = new Image();

                    (function(source, thumb, $preview) {
                        image.onload = function() {
                            if (this.width + this.height == 0) {
                                return this.onerror();
                            }

                            $preview.css('background-image', 'url("' + thumb + '")');
                        }

                        image.onerror = function() {
                            if (this.thumbChecked == undefined) {
                                this.thumbChecked = true;
                                this.src = source.replace('assets/images', '../assets/images');
                            } else {
                                $preview.css('background-image', 'url("../assets/images/noimage.jpg")');
                            }
                        }
                    })(source, thumb, $preview);

                    image.src = thumb;
                } else {
                    $preview.css('background-image', 'url("' + thumb + '")');
                }
            }
        };

        $.fn.initComoptionsPopup = function() {
            var row = this.data('row'),
                $fields = $('.option-rows').children('[data-iteration="' + row + '"]').find('input, select, textarea');

            this.find('input, select, textarea').each(function() {
                var $field = $fields.filter('[name="' + this.name + '"]');

                if ($field.length) {
                    if (this.type == 'checkbox') {
                        this.checked = $field.is(':checked');
                    } else {
                        $(this).val($field.val());
                    }
                }
            });

            (function($popup, titleLocked) {
                $popup.on('change input', '.option-title', function() {
                    if (!titleLocked.checked) {
                        titleLocked.checked = true;
                    }
                });
            })(this, this.find('input.title-locked').get(0));

            this.on('click', '.remove-attribute', function(e) {
                e.preventDefault();
                var $dropdown = $(this).closest('table').find('.new-attribute');
                $(this).closest('tr').remove();
                $dropdown.updateAttributesDropdown();
                $dropdown.closest('.comoptions-popup').updateTitle();
            });

            this.find('.new-attribute').updateAttributesDropdown().on('change', function() {
                var $dropdown = $(this),
                    $row = $dropdown.closest('tr'),
                    $rows = $row.prevAll('[data-iteration]'),
                    val = $dropdown.val();

                if (!val) {
                    return;
                }

                var attr = _co.attributes[val],
                    tpl = parseTemplate(attrRowTpl, {
                        attribute_sort: attr.sort,
                        option_iteration: row,
                        attribute_id: val,
                        attribute_title: attr.title,
                        value_id: Object.keys(attr.values)[0]
                    }),
                    $tpl = $(tpl);

                if ($rows.length) {
                    $rows.each(function() {
                        if ($(this).attr('data-iteration') > attr.sort) {
                            $row = $(this);
                        } else {
                            return false;
                        }
                    });
                }

                $tpl.insertBefore($row).initAttributeRow();
                $dropdown.updateAttributesDropdown();
                $dropdown.closest('.comoptions-popup').updateTitle();
                $tpl.find('select').focus();
            });

            var $image = this.find('.option-image');

            $image.find('.show-browser').click(function(e) {
                e.preventDefault();

                var $field = $(this).closest('.form-cell').find('input[type="text"]'),
                    wnd    = window.parent || window,
                    margin = parseInt(wnd.innerHeight * .1),
                    width  = wnd.innerWidth - margin * 2,
                    height = wnd.innerHeight - margin * 2,
                    params = 'toolbar=no,status=no,resizable=yes,dependent=yes,width=' + width + ',height=' + height + ',left=' + margin + ',top=' + (margin + (wnd._startY ? wnd._startY * .5 : 0));

                if (window['SetUrl']) {
                    window['SetUrl_disabled'] = window['SetUrl'];
                    window['SetUrl'] = null;
                }

                window.KCFinder = {
                    callBack: function(url) {
                        if (window['SetUrl_disabled']) {
                            window['SetUrl'] = window['SetUrl_disabled'];
                        }

                        window.KCFinder = null;
                        $field.val(url).updateThumb();
                    }
                };

                var wnd = window.open(_co.imagesBrowser + '?type=images', 'FileManager', params);
            });

            $image.find('input[type="text"]').on('change input', function() {
                $(this).updateThumb();
            }).updateThumb();

            this.find('.option-attribute').initAttributeRow();
        };

        $.fn.destroyComoptionsPopup = function() {
            this.find('select.value_id').each(function() {
                var val = $(this).val(),
                    name = this.name;

                $(this).replaceWith( $('<input type="hidden" class="value_id"/>').attr('name', name).val(val) );
            });

            var row     = this.data('row'),
                $row    = $('.option-rows').children('[data-iteration="' + row + '"]'),
                $fields = this.find('input, select, textarea'),
                html    = this.children('.evo-popup-body').html();

            $row.find('.window-contents').html(html);

            $row.find('input, select, textarea').each(function() {
                var $field = $fields.filter('[name="' + this.name + '"]');

                if ($field.length) {
                    if (this.type == 'checkbox') {
                        this.checked = $field.is(':checked');
                    } else {
                        $(this).val($field.val());
                    }
                }
            });

            $row.find('.option-image-preview').attr('src', this.find('.option-image > .preview').css('background-image').replace(/url\("?(.+?)"?\).*/, '$1'));
        };

        $.fn.updateAttributesDropdown = function() {
            return this.each(function() {
                var $select = $(this).empty(),
                    exists  = {};

                $select.append('<option value=""></option>')

                $(this).closest('table').find('.attribute_id').each(function() {
                    exists[$(this).val()] = true;
                });

                for (var id in _co.attributes) {
                    if (exists[id]) {
                        continue;
                    }

                    $select.append('<option value="' + id + '">' + _co.attributes[id].title + '</option>');
                }

                $select.closest('tr').toggle($select.children().length > 1);
            });
        };

        $.fn.initAttributeRow = function() {
            return this.each(function() {
                var attr_id = parseInt($(this).find('.attribute_id').val()),
                    $input  = $(this).find('.value_id'),
                    $select = $('<select class="form-control value_id"/>').attr('name', $input.attr('name')).data('input', $input);

                if (attr_id && _co.attributes[attr_id].values) {
                    for (var id in _co.attributes[attr_id].values) {
                        $select.append('<option value="' + id + '">' + _co.attributes[attr_id].values[id].title + '</option>');
                    }

                    $select.val($input.val());
                    $select.insertAfter($input);
                    $input.detach();

                    $select.change(function() {
                        $(this).closest('.comoptions-popup').updateTitle();
                    });
                }
            });
        };

        $.fn.initOptionRow = function() {
            return this.each(function() {
                var $row = $(this);

                (function($window) {
                    $row.children('.custom-cell').on('change', 'input, textarea, select', function() {
                        var $field = $window.find('[name="' + this.name + '"]');

                        if ($field.length) {
                            if (this.type == 'checkbox') {
                                $field.get(0).checked = this.checked;
                            } else {
                                $field.val($(this).val());
                            }
                        }
                    });
                })($row.find('.window-contents'));

                $row.on('click', '.edit-option', function(e) {
                    e.preventDefault();

                    var $contents = $(this).nextAll('.window-contents');

                    var popup = parent.modx.popup({
                        title: $contents.attr('data-title'),
                        icon: 'fa-pencil',
                        content: $contents.html(),
                        width: 600,
                        hide: 0,
                        hover: 0,
                        overlay: 1,
                        onclose: function(e, obj) {
                            $(obj).destroyComoptionsPopup();
                        }
                    });

                    $(popup.el).data('row', $(this).closest('tr').attr('data-iteration')).initComoptionsPopup();
                });

                $row.find('.new-attribute').updateAttributesDropdown();
            });
        };

        $('#tabComOptions').each(function() {
            $container = $(this);

            var $rows = $container.find('.option-rows');

            $rows.children('tr').initOptionRow();

            $container.on('click', '.add-option', function(e) {
                e.preventDefault();

                var tpl = parseTemplate(optRowTpl, {
                    iteration: _co.nextOptionRow++
                });

                var $row = $(tpl).appendTo($rows).initOptionRow();
                $row.find('.btn.edit-option').click();
            });

            $container.on('click', '.remove-option', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
    })(jQuery);
});
