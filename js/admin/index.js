$(function () {
    var vUrl = intelli.config.admin_url + '/importer/read.json';
    var options = {};
    var total = 0;
    var imported = 0;

    $('#input-get-file').on('change', function () {
        var name = $(this).val().split('.');

        if ('csv' === name[1]) {
            $('.js-options-xml').addClass('hide');
            $('.js-options-csv').removeClass('hide');
        }
        else if ('xml' === name[1]) {
            $('.js-options-csv').addClass('hide');
            $('.js-options-xml').removeClass('hide');
        }
    });

    $('.js-check').on('click', function () {
        var _this = $(this);
        var name = $('#input-get-file').val().split('.');

        options = {
            action: 'check_file',
            file: $('#input-get-file').val()
        };

        if ('csv' === name[1]) {
            options.delimiter = $('#input-delimiter').val();
            if ($('#input-as-column').is(':checked')) {
                options.as_column = 1;
            }
        }
        else if ('xml' === name[1]) {
            options.tag = $('#input-entry-tag').val();
        }

        $.get(vUrl, options, function (data) {
            if (data.total) {
                total = data.total;
                $(_this).next().html(_t('total_entries') + ': ' + data.total);
                $('.js-options').removeClass('hide');

                if (data.fields) {
                    var fields = data.fields;
                    fields = $.map(fields, function (n, i) {
                        return '<option value="' + i + '">' + n + '</option>';
                    });
                    $('.js-fields .js-import-field').html('').append(fields);
                }
            }
        });

        return false;
    });

    $('#js-input-item').on('change', function () {
        var _this = $(this);
        if (_this.val()) {
            options = {action: 'change_item', item: _this.val()};

            $.get(vUrl, options, function (data) {
                if (data.table) {
                    $('#js-input-table').val(data.table);
                }
            });
        }
    });

    $('#js-get-fields').on('click', function (e) {
        e.preventDefault();

        var _this = $('#js-input-table');
        if (_this.val()) {
            options = {action: 'get_fields', table: _this.val()};

            $.get(vUrl, options, function (data) {
                if (data.item_fields) {
                    var item_fields = data.item_fields;

                    var options = $('.js-fields .js-item-field').html('');
                    $.each(item_fields, function () {
                        options.append($("<option />").val(this).text(this));
                    });

                    $('.js-fields').removeClass('hide');
                }
            });
        }
    });

    $('.js-fields .js-add-row').on('click', function (e) {
        e.preventDefault();

        var $thisParent = $(this).closest('.row');
        var $clone = $thisParent.clone(true);
        $thisParent.after($clone);
    });

    $('.js-fields .js-delete-row').on('click', function (e) {
        e.preventDefault();

        if ($('.js-fields .row').length > 2) {
            $(this).closest('.row').remove();
        }
    });

    var ajaxCall;

    $('#js-import').on('click', function (e) {
        e.preventDefault();

        var params = {};
        var item_fields = [];
        var import_fields = [];

        $('.js-options :input').each(function () {
            if ($(this).val()) {
                params[$(this).attr('name')] = $(this).val();
            }
        });

        if ($('#input-as-column').is(':checked')) {
            params['as_column'] = 1;
        }

        $('.js-item-field').each(function (e) {
            item_fields[e] = $(this).val();
        });
        $('.js-import-field').each(function (e) {
            import_fields[e] = $(this).val();
        });

        params['item_fields'] = item_fields;
        params['import_fields'] = import_fields;
        params['start'] = 0;

        $.get(vUrl, {action: 'start_import_process', adapter: params['adapter']});

        function progress(start) {
            params['start'] = +start;
            intelli.post(vUrl, params, function (data) {
                current = +data.imported;
                imported = imported + current;

                var width = imported * 100 / total;
                $('.progress-bar').width(width + '%');

                if (data.error) {
                    clearTimeout(ajaxCall);
                    $('#js-stop-import').hide();
                    $('#js-modal-close').attr('data-dismiss', 'modal');
                }
                else if (data.done) {
                    clearTimeout(ajaxCall);
                    $('#js-stop-import').hide();
                    $('#js-modal-close').attr('data-dismiss', 'modal');

                    $('.modal-body').append($('<p class="alert alert-success" />').text(_t('import_completed') + ': ' + imported));

                    intelli.post(vUrl, {action: 'finish_import_process', adapter: params['adapter']}, function () {
                    });
                }
                else {
                    ajaxCall = setTimeout(function () {
                        progress(data.start);
                    }, 1000);
                }
            });
        }

        ajaxCall = setTimeout(function () {
            progress(+params.start);
        }, 1000);

        $('.js-process-import').modal({keyboard: false, backdrop: 'static'});

        //$('.js-process-import').modal('toggle');
        $('#js-modal-close').attr('data-dismiss', '');
    });

    $('#js-stop-import').on('click', function () {
        clearTimeout(ajaxCall);
        $('#js-stop-import').hide();
        $('#js-modal-close').attr('data-dismiss', 'modal');
        //$('#js-modal-close').attr('data-dismiss','modal');
    });
});