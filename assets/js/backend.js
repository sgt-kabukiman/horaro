/*global jQuery, ko, horaro, horaroTimeFormat, moment */

jQuery(function($) {
	'use strict';

	var scheduleColumns, scheduleID, scheduleStart, scheduleTZ, scheduleSetupTime, viewModel, items, columns, maxItems;

	// init CSRF token information

	var csrfToken     = $('meta[name="csrf_token"]').attr('content');
	var csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

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

	// setup back buttons

	$('body').on('click', '.h-back-btn', function() {
		history.back();
		return false;
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

	function growl(msg) {
		$.bootstrapGrowl(msg, growlOpt);
	}

	if ($('#h-flashes').length > 0) {
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

		var flashes = JSON.parse($('#h-flashes').text());

		for (var flashType in flashes) {
			growlOpt.type = flashType;

			flashes[flashType].forEach(growl);
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

	ko.bindingHandlers.activate = {
		init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
			var value = valueAccessor();

			$(element).keydown(function(e) {
				if (e.keyCode === 13 /* return */ || e.keyCode === 32 /* space */) {
					e.preventDefault();
					e.stopPropagation();

					value.call(bindingContext['$data'], bindingContext['$data'], e);
				}
			});
		}
	};

	//= src/Utils.js
	//= src/SpatialNavigation.js

	//= src/Item.js
	//= src/ItemsViewModel.js
	//
	//= src/Column.js
	//= src/ColumnsViewModel.js

	var ui = $('body').data('ui');

	if (ui) {
		if (ui === 'scheduler') {
			var dataNode = $('.h-scheduler');
			var itemData = JSON.parse($('#h-item-data').text());

			scheduleID        = dataNode.data('id');
			scheduleColumns   = (''+dataNode.data('columns')).split(',');
			scheduleStart     = new Date(dataNode.data('start'));
			scheduleSetupTime = parseInt(dataNode.data('setuptime'), 10);
			scheduleTZ        = dataNode.data('tz');
			maxItems          = parseInt(dataNode.data('maxitems'), 10);
			items             = [];

			if (itemData) {
				itemData.forEach(function(item, idx) {
					items.push(new Item(item[0], item[1], item[2], idx + 1));
				});
			}

			viewModel = new ItemsViewModel(items);
		}
		else if (ui === 'columnist') {
			var dataNode = $('.h-columnist');
			var colData  = JSON.parse($('#h-column-data').text());

			scheduleID = dataNode.data('id');
			columns    = [];

			if (colData) {
				colData.forEach(function(column, idx) {
					columns.push(new Column(column[0], column[1], column[2], column[3]));
				});
			}

			viewModel = new ColumnsViewModel(columns);
		}

		if (viewModel) {
			var options = {
				attribute: 'data-bind',        // default "data-sbind"
				globals: window,               // default {}
				bindings: ko.bindingHandlers,  // default ko.bindingHandlers
				noVirtualElements: false       // default true
			};
			ko.bindingProvider.instance = new ko.secureBindingsProvider(options);

			ko.applyBindings(viewModel);
			viewModel.initDragAndDrop();
			$('#h-scheduler-loading').hide();
			$('#h-scheduler-container').show();

			// init spatial navigation (i.e. allow going up/down/left/right with array keys)
			new SpatialNavigation(dataNode);

			if (ui === 'scheduler') {
				// sync the table column widths the hard way
				setInterval(function() {
					mirrorColumnWidths(dataNode, $('tr:first > *', dataNode.prev()));
				}, 500);
			}
		}
	}

	var mdParser = new Remarkable('commonmark');
	mdParser.set({ html: false, xhtmlOut: false });

	$('.remarkable').each(function(i, textarea) {
		var timeout = null;

		textarea = $(textarea);

		function update(text) {
			var container = $('.remarkable-preview');

			container
				.html(mdParser.render(text))
				.find('img')
					.addClass('img-responsive')
					.attr('src', container.data('placeholder'))
					.attr('title', '(placeholder image by Casey Muir-Taylor, CC-BY)')
			;
		}

		textarea.on('keyup paste cut mouseup', function() {
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}

			timeout = setTimeout(function() {
				update(textarea.val());
			}, 300);
		});

		update(textarea.val());
	});
});
