$(document).ready(function() {
    adjustLayout();
    $.fn.tooltipError = function(opt) {
        return this.each(function() {
            var option = $.extend({
                title: 'Error',
                trigger: 'manual',
            }, opt);

            var $this = $(this);
            $(this).tooltip(option).tooltip('show');
            setTimeout(function() {
                $this.tooltip('hide');
            }, 3500);
        });
    };
    $.fn.assignData = function(data) {
        return this.each(function() {
            var $form = $(this);
            $.each(data, function(i,v) {
                var $input = $form.find('[name="'+i+'"]');
                if ($input.is('p')) {
                    $input.text(v);
                }
                else if ($input.is(':checkbox') || $input.is(':radio')) {
                    $input.prop('checked', function() {
                        return this.value === v;
                    });
                }
                else if ($input.data('utilize') === 'datepicker' && /(\d{4})\-(\d{2})\-(\d{2})/.test(v)) {
                    $input.val(v.split('-').reverse().join('-'));
                }
                else {
                    $input.val(v);
                }
            });
        });
    };
    $.fn.select2Custom = function(option) {
        return this.each(function() {
            var $this = $(this);
            var $form = $this.closest('form');

            var opt = $.extend({
            }, option, $this.data());

            if (opt.url) {
                opt.minimumInputLength = 1;
                opt.ajax = {
                    url: opt.url,
                    dataType: 'json',
                    delay: 250,
                    data: function (term, page) {
                        var param = {
                            q: term, // search term
                            records: 10,
                            page: page,
                            extras: {}
                        };
                        var extras = ($this.data('extras') || '').split(',');
                        $.each(extras, function(i, v) {
                            var $e = $form.find(v);
                            if ($e.length) {
                                param.extras[v] = $e.val();
                            }
                        });

                        return param;
                    },
                    processResults: function (data, params) {
                        var more = ((params.page || 1) * 10) < data.total;
                        return { results: data.items, paginaton: {more: more}};
                    },
                    cache: true
                };
            }
            $this.select2(opt);
        });
    };
    $.fn.desktopForm = function(option) {
        return this.each(function() {
            var $form = $(this);
            var opt = $.extend({
                search: '[data-form-connect="[data-form=desktop]"]',
            }, option, $form.data());
            var map = $form.prop('action');
            var $new = $form.find('[href="#new"]');
            var $delete = $form.find('[href="#delete"]');
            var $prev = $form.find('[href="#prev"]');
            var $next = $form.find('[href="#next"]');
            var $reset = $form.find(':reset');
            var $save = $form.find(':submit');
            var $search = $(opt.search).find('[data-utilize=select2]');
            var controlDisable = function(state, reset) {
                reset = reset || false;
                $form
                    .find(':input:not(:button)')
                    .val(function() {
                        return reset?'':this.value;
                    })
                    .prop('disabled', state)
                    .filter(':not([readonly]):visible:enabled:first')
                    .focus()
                    ;
            };
            var formDisable = function(option) {
                var opt = $.extend({
                    prev: false,
                    next: false,
                    valid: false,
                    reset: false,
                    disable: false,
                    new: false
                }, option);
                controlDisable(opt.disable, opt.reset);
                opt.prev ? $prev.removeClass('disabled') : $prev.addClass('disabled');
                opt.next ? $next.removeClass('disabled') : $next.addClass('disabled');
                opt.valid ? $delete.removeClass('disabled') : $delete.addClass('disabled');
                opt.new ? $new.removeClass('disabled') : $new.addClass('disabled');
                $reset.prop('disabled', opt.disable);
                $save.prop('disabled', opt.disable);
                $form.data('valid', opt.valid);
                if (option.reset) {
                    $form.find('.form-group').removeClass('has-error');
                    $form.find('.form-error').remove();
                }
            };
            var formValid = function() {
                return true===$form.data('valid');
            };
            var get = function(dir, id) {
                formDisable({disable:true});
                id = id || $form.find('[name=id]').val() || 'empty';
                $.get(map.replace('@id', id), {dir: dir}, function(data) {
                    if (data.item.id) {
                        formDisable({next:data.next,prev:data.prev,valid:true});
                        $form.assignData(data.item || {});
                    }
                    else {
                        formDisable({disable:true,new:true});
                    }
                });
            };

            $search.on('change', function() {
                var val = $search.val();
                get('current', val);
            });
            $new.on('click', function(event) {
                event.preventDefault();
                formDisable({reset: true});
            });
            $reset.on('click', function(event) {
                formDisable({disable: true, reset: true, new: true});
            });
            $form.on('submit', function(event) {
                event.preventDefault();
                var valid = formValid();
                var id = $form.find('[name=id]').val() || 'new';
                $.ajax({
                    url: map.replace('@id', id),
                    type: valid?'put':'post',
                    dataType: 'json',
                    data: $form.serialize(),
                    beforeSend: function() {
                        formDisable({disable: true});
                    },
                    error: function() {
                        toastr.error('Request error!');
                        formDisable();
                    },
                    success: function(data) {
                        if (data.success) {
                            $form.assignData(data.update);
                            toastr.success(data.message);
                            formDisable({prev:data.prev});
                        }
                        else {
                            $.each(data.error, function(i,v) {
                                var $input = $form.find('[name="'+i+'"]');
                                if ($input.is(':input')) {
                                    $input.closest('.form-group').addClass('has-error');
                                    var $error = $('<span/>').addClass('help-block form-error').text(v);
                                    $error.insertAfter($input);
                                }
                            });
                            toastr.error(data.message);
                            formDisable();
                        }
                    }
                });
            });
            $delete.on('click', function(event) {
                event.preventDefault();
                bootboxConfirm(function(ya) {
                    if (ya) {
                        var id = $form.find('[name=id]').val() || 'new';
                        $.ajax({
                            url: map.replace('@id', id),
                            type: 'delete',
                            dataType: 'json',
                            beforeSend: function() {
                                formDisable({disable: true});
                            },
                            error: function() {
                                toastr.error('Request error!');
                                formDisable();
                            },
                            success: function(data) {
                                if (data.success) {
                                    toastr.success(data.message);
                                    $reset.click();
                                    get('first');
                                }
                                else {
                                    toastr.error(data.message);
                                    formDisable();
                                }
                            }
                        });
                    }
                }, app.confirm.delete);
            });
            $next.on('click', function(event) {
                event.preventDefault();
                get('next');
            });
            $prev.on('click', function(event) {
                event.preventDefault();
                get('prev');
            });
            get('first');
        });
    };

    $(window).on('resize', adjustLayout);
    $('[data-form=desktop]').desktopForm();
    $('.notifier').each(function() {
        var data = $(this).data();
        var message = $.trim($(this).html());
        toastr[data.toastr](message);
    });
    $('[data-confirm]').on('click', function(event) {
        event.preventDefault();
        var target = $(this).prop('href');
        var key = $(this).data('confirm');
        bootboxConfirm(function(ya) {
            if (ya) {
                window.location.href = target;
            }
        }, app.confirm[key]);
    });
    $('[data-toggle=tooltip]').each(function() {
        var option = {
            container: 'body'
        };
        $(this).tooltip(option);
    });
    $('select[data-reload]').on('change', function() {
        var name = $(this).data('reload') || $(this).prop('name');
        var value = $(this).val();
        var queries = getQuery();
        queries[name] = value;
        window.location.search = $.param(queries)
    });
    $('form[data-quick-search]').on('submit', function(event) {
        event.preventDefault();
        var queries = getQuery();
        $(this).find('input,select').each(function() {
            queries[this.name] = this.value;
        });
        window.location.search = $.param(queries);
    });
    $('[data-utilize=datepicker]').each(function() {
        var option = $.extend({
            format: 'dd-mm-yyyy',
            endDate: '0d'
        }, $(this).data(), {});
        $(this).datepicker(option);
    });
    $('[data-utilize=colorpicker]').spectrum({
        preferredFormat: 'hex'
    });
    $('[data-copier]').each(function() {
        var target = $(this).data('copier').split(',');
        var mval = $(this).val();
        var $this = $(this);
        $.each(target, function(i, v) {
            var $t = $(v);
            if ($t.length) {
                var val = $t.val();
                var changed = val != mval;

                $t.data('changed', changed);
                $t.on('keyup', function() {
                    if (!$(this).data('changed')) {
                        $(this).data('changed', $(this).val()!=$this.val());
                    }
                });
            }
        });
        $(this).on('keyup', function() {
            var val = $(this).val();
            $.each(target, function(i, v) {
                var $t = $(v);
                $t.val(function() {
                    return $(this).data('changed')?this.value:val;
                });
            });
        });
    });
    $('[data-mask]').each(function() {
        var mask = $(this).data('mask');
        $(this).inputmask(mask);
    });
    $('[data-transform=autonumeric]').autoNumeric({
        aSep: '.',
        aDec: ','
    });
    $('[data-transform=rupiah]').autoNumeric({
        aSep: '.',
        aDec: ',',
        aSign: 'Rp '
    });
    $('[data-transform=prosen]').autoNumeric({
        aSep: '.',
        aDec: ',',
        aSign: '%',
        pSign: 's'
    });
    $('[data-utilize=select2]').select2Custom();
    $('.form-error').each(function() {
        $(this).closest('.form-group').addClass('has-error');
    });
});

function isNotEmpty(v) {
    return (v || v !== "");
}
function getQuery() {
    var text = window.location.search.substr(1).replace(/&/g, '","').replace(/=/g, '":"');

    return text ? $.parseJSON('{"'+text+'"}') : {};
}
function adjustLayout() {
    var navheight = $('.navbar-fixed-top').height();

    $('body').css('padding-top', (navheight+20)+'px');
}
function bootboxConfirm(cb, message) {
    bootbox.confirm({
        title: 'Confirmation',
        message: message+'?',
        buttons: {
            confirm: {
                label: '<i class="fa fa-exclamation-triangle"></i> OK',
                className: 'btn-danger'
            },
            cancel: {
                label: '<i class="fa fa-ban"></i> Cancel',
                className: 'btn-default'
            }
        },
        callback: cb
    });
}
