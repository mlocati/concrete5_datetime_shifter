<?php
defined('C5_EXECUTE') or die('Access Denied.');
?>
<table class="table table-striped table-condensed">
    <thead>
        <tr>
            <th><?php echo t('Table'); ?></th>
            <th>
                <label>
                    <input type="checkbox" checked="checked" onchange="$('input.dts-table-field').prop('checked', this.checked)" />
                    <?php echo t('Field'); ?>
                </label>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($tableFields as $table => $fields) {
            $first = true;
            foreach ($fields as $field) {
                ?>
                <tr>
                    <?php
                    if ($first) {
                        $first = false;
                        ?>
                        <th rowspan="<?php echo count($fields); ?>"><code><?php echo h($table)?></code></th>
                        <?php
                    }
                    ?>
                    <td>
                        <label style="font-weight: normal">
                            <input type="checkbox" class="dts-table-field" checked="checked" data-tablename="<?php echo h($table)?>" data-fieldname="<?php echo h($field)?>" />
                            <code><?php echo h($field); ?></code>
                        </label>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </tbody>
</table>

<div id="dts-options-dialog" class="ccm-ui" style="display: none" title="<?php echo t('Options'); ?>">
    <form class="form-horizontal">
        <div class="form-group">
            <label for="dts-options-operation" class="col-sm-5 control-label"><?php echo t('Operation'); ?></label>
            <div class="col-sm-7">
                <select id="dts-options-operation" class="form-control">
                    <option value="+" selected="selected"><?php echo t('Add'); ?></option>
                    <option value="-"><?php echo t('Substract'); ?></option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="dts-options-hours" class="col-sm-5 control-label"><?php echo t('Hours'); ?></label>
            <div class="col-sm-7">
                <input type="number" id="dts-options-hours" class="form-control" value="0" min="0" />
            </div>
        </div>
        <div class="form-group">
            <label for="dts-options-minutes" class="col-sm-5 control-label"><?php echo t('Minutes'); ?></label>
            <div class="col-sm-7">
                <input type="number" id="dts-options-minutes" class="form-control" value="0" min="0" max="59" />
            </div>
        </div>
        <div class="form-group">
            <label for="dts-options-limit-min" class="col-sm-5 control-label"><?php echo t('For date/times after'); ?></label>
            <div class="col-sm-7">
                <input type="datetime-local" id="dts-options-limit-min" class="form-control" value="" />
            </div>
        </div>
        <div class="form-group">
            <label for="dts-options-limit-max" class="col-sm-5 control-label"><?php echo t('For date/times before'); ?></label>
            <div class="col-sm-7">
                <input type="datetime-local" id="dts-options-limit-max" class="form-control" value="<?php
                $now = new DateTime();
                echo $now->format('Y-m-d\TH:i:s');
                ?>" />
            </div>
        </div>
    </form>
</div>

<div id="dts-options-start" class="ccm-ui" style="display: none" title="<?php echo t('Process'); ?>">
    <div style="max-height: 500px; overflow: auto; overflow-x: visible">
        <table class="table table-condensed table-striped">
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="ccm-dashboard-form-actions-wrapper">
    <div class="ccm-dashboard-form-actions">
        <div class="pull-right">
            <a href="#" id="dts-apply" class="btn btn-primary pull-right ccm-input-submit"><?php echo t('Apply'); ?></a>
        </div>
    </div>
</div>
<script>$(document).ready(function() {

var $optionsDialog = $('#dts-options-dialog');
var $processDialog = $('#dts-options-start');
var processing = false;

$(window).on('beforeunload', function() {
    if (processing) {
        return <?php echo json_encode(t("Table fields are being updated. Are you sure you want to leave this page?")); ?>;
    }
});

function getSelectedFields() {
    var result = [];
    $('input.dts-table-field:checked').each(function() {
        var $me = $(this);
        result.push([$me.data('tablename'), $me.data('fieldname')]);
    });
    return result;
}
$('#dts-apply').on('click', function(e) {
    e.preventDefault();
    if (getSelectedFields().length === 0) {
        window.alert(<?php echo json_encode(t('Please select at least one table field.')); ?>);
        return;
    }
    askOptions();
});

function askOptions() {
    $optionsDialog.dialog({
        width: 500,
        modal: true,
        buttons: [
            {
                text: <?php echo json_encode(t('Start')); ?>,
                click: function() {
                    var $i, options = {};
                    $i = $('#dts-options-operation');
                    options.operation = $i.val();
                    if ('+-'.indexOf(options.operation) < 0) {
                        $i.focus();
                        return;
                    }
                    $i = $('#dts-options-hours');
                    options.hours = parseInt($i.val(), 10);
                    if (isNaN(options.hours) || options.hours < 0) {
                        $i.focus();
                        return;
                    }
                    $i = $('#dts-options-minutes');
                    options.minutes = parseInt($i.val(), 10);
                    if (isNaN(options.minutes) || options.minutes < 0 || options.minutes > 59) {
                        $i.focus();
                        return;
                    }
                    if (options.hours === 0 && options.minutes === 0) {
                        $('#dts-options-hours').focus();
                        return;
                    }
                    $i = $('#dts-options-limit-min');
                    options.limitMin = $i.val();
                    if (options.limitMin !== '') {
                        if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/.test(options.limitMin)) {
                            $i.focus();
                            return;
                        }
                    }
                    $i = $('#dts-options-limit-max');
                    options.limitMax = $i.val();
                    if (options.limitMax !== '') {
                        if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/.test(options.limitMax)) {
                            $i.focus();
                            return;
                        }
                    }
                    $optionsDialog.dialog('close');
                    showProcessDialog(options);
                }
            },
            {
                text: <?php echo json_encode(t('Cancel')); ?>,
                click: function() {
                    $optionsDialog.dialog('close');
                }
            }
        ]
    });
}

function showProcessDialog(options) {
    var fields = getSelectedFields();
    if (fields.length === 0) {
        return;
    }
    var $tbody = $processDialog.find('tbody').empty();
    $.each(fields, function (_, field) {
        $tbody.append($('<tr class="dts-field-todo" />')
            .data('tablename', field[0])
            .data('fieldname', field[1])
            .append($('<th />')
                .append($('<code />').text(field.join('.')))
            )
            .append($('<td />')
                .append($('<span style="visibility: hidden" />')
                    .text(<?php echo json_encode(t2('%d row updated', '%d rows updated', 88888888)); ?>)
                )
            )
        );
    });
    processing = true;
    $processDialog.dialog({
        width: 600,
        modal: true,
        beforeClose: function() {
            if (processing) {
                window.alert(<?php echo json_encode(t("You can't close this dialog: table fields are being updated...")); ?>);
                return false;
            }
            return !processing;
        },
        open: function() {
            processNext(options);
        }
    });
}

function processNext(options) {
    var $tr = $processDialog.find('tr.dts-field-todo:first');
    if ($tr.length === 0) {
        setTimeout(function() {
            window.alert(<?php echo json_encode(t("Operations completed.")); ?>);
        }, 100);
        processing = false;
        return;
    }
    var tablename = $tr.data('tablename'), fieldname = $tr.data('fieldname');
    $tr.removeClass('dts-field-todo').addClass('dts-field-doing').focus();
    var $td = $tr.find('td:last').text(<?php echo json_encode(t("Processing...")); ?>);
    $.ajax({
        type: 'POST',
        url: <?php echo json_encode($this->action('updateDatetimeField')); ?>,
        data: $.extend({
            ccm_token: <?php echo json_encode($token->generate('dts-update-field')); ?>,
            tablename: tablename,
            fieldname: fieldname
        }, options),
        dataType: 'json'
    })
    .always(function() {
    	$tr.removeClass('dts-field-doing').addClass('dts-field-done');
        processNext(options);
    })
    .fail(function(xhr, status, error) {
        $td.text(error).css('color', 'red');
    })
    .done(function(data, status, xhr) {
        var error = null;
        if (!data) {
        	error = <?php echo json_encode(t('No server response.')); ?>;
        } else if (data.error || data.errors) {
			if (data.errors) {
				error = data.errors.join ? data.errors.join('\n') : data.errors;
			} else {
				error = data.error;
			}
        } else if (!data.result) {
        	error = <?php echo json_encode(t('Invalid server response.')); ?>;
        }
        if (error !== null) {
			$td.text(error).css('color', 'red');
			return;
		}
        $('input.dts-table-field[data-tablename="' + tablename + '"][data-fieldname="' + fieldname + '"]').prop('checked', false);
        $td.text(data.result);
	});
}   

});</script>