jQuery(function($) {
	var scheduleColumns, scheduleID, viewModel, items, columns, csrfToken, csrfTokenName;

	// init CSRF token information

	csrfToken     = $('meta[name="csrf_token"]').attr('content');
	csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

	// init date and time pickers

	$('#start_date').pickadate({
		formatSubmit: 'yyyy-mm-dd',
		hiddenName: true
	});

	$('#start_time').pickatime({
		interval: 15,
		formatSubmit: 'HH:i',
		format: horaroTimeFormat,
		formatLabel: horaroTimeFormat,
		hiddenName: true
	});

	// setup Select2

	$('select.h-fancy').select2();

	// render localized times

	$('time.h-fancy').each(function() {
		// do not convert into the user's timezone, but leave the given one
		// (i.e. the schedule's timezone)
		$(this).text(moment.parseZone($(this).attr('datetime')).format('llll'));
	});

	// render flash messages

	if (typeof horaro !== 'undefined' && horaro.flashes) {
		var growlOpt = {
			ele:             'body',
			type:            'info', // (null, 'info', 'error', 'success')
			offset:          {from: 'top', amount: 26}, // 'top', or 'bottom'
			align:           'center',
			width:           350,
			delay:           3000,
			allow_dismiss:   true,
			stackup_spacing: 5
		};

		for (var flashType in horaro.flashes) {
			growlOpt.type = flashType;

			horaro.flashes[flashType].forEach(function(message) {
				$.bootstrapGrowl(message, growlOpt);
			});
		}
	}

	// prepare X-Editable

	$.fn.editable.defaults.mode = 'popup';
	$.fn.editableform.buttons =
		'<button type="submit" class="btn btn-primary btn-xs editable-submit">'+
			'<i class="fa fa-check"></i>'+
		'</button>'+
		'<button type="button" class="btn btn-default btn-xs editable-cancel">'+
			'<i class="fa fa-ban"></i>'+
		'</button>';

	// setup Knockout bindings

	//= src/Utils.js

	//= src/Item.js
	//= src/ItemsViewModel.js
	//
	//= src/Column.js
	//= src/ColumnsViewModel.js

	if (typeof horaro !== 'undefined' && horaro.schedule) {
		scheduleColumns = horaro.schedule.columns;
		scheduleID      = horaro.schedule.id;

		if (horaro.ui === 'scheduler') {
			items = [];

			if (horaro.schedule.items) {
				horaro.schedule.items.forEach(function(item, idx) {
					items.push(new Item(item[0], item[1], item[2], idx + 1));
				});
			}

			viewModel = new ItemsViewModel(items);
		}
		else if (horaro.ui === 'columnist') {
			columns = [];

			if (horaro.schedule.columns) {
				horaro.schedule.columns.forEach(function(column, idx) {
					columns.push(new Column(column[0], column[1], idx + 1));
				});
			}

			viewModel = new ColumnsViewModel(columns);
		}

		if (viewModel) {
			ko.applyBindings(viewModel);
			viewModel.initDragAndDrop(false);
		}
	}
});
