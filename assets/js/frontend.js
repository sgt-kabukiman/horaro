/*global jQuery, moment */

jQuery(function($) {
	'use strict';

	// http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
	function qs(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");

		var
			regex   = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);

		return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
	}

	function updateRelativeTimes() {
		var now = new Date();

		$('time.h-relative').each(function() {
			var minutes = moment($(this).attr('datetime')).diff(now, 'minutes');

			if ($(this).is('.h-fuzzy')) {
				$(this).text(moment.duration(minutes, 'minutes').humanize(true));
				return;
			}

			var hours = parseInt(minutes / 60, 10);
			var texts = [];

			minutes -= hours*60;

			if (hours) {
				texts.push(hours + ' hour' + (hours === 1 ? '' : 's'));
			}

			if (minutes) {
				texts.push(minutes + ' minute' + (minutes === 1 ? '' : 's'));
			}

			$(this).text('in ' + (texts.length === 0 ? 'a few moments' : texts.join(' and ')));
		});
	}

	function findCurrentItem() {
		var now           = (new Date()).getTime();
		var scheduleStart = new Date($('#h-schedule-start').attr('datetime'));

		// schedule has not started yet
		if (scheduleStart.getTime() > now) {
			return null;
		}

		var scheduleEnd = new Date($('#h-schedule-end').attr('datetime'));

		// schedule is over
		if (scheduleEnd.getTime() < now) {
			return null;
		}

		var items = $('.h-schedule tbody');
		var item;
		var currentItem;
		var scheduled;

		for (var i = 0, len = items.length; i < len; ++i) {
			item      = $(items[i]);
			scheduled = new Date(item.find('.h-s time').attr('datetime'));

			// item has not yet been reached; this means we just ran past the current item
			// and can now just stop
			if (scheduled.getTime() > now) {
				break;
			}

			currentItem = item;
		}

		// By checking for the end time before this loop, we can be sure we found an item,
		// even it it's the last one (otherwise we'd have to check its length to make sure
		// the schedule isn't over yet).
		// This means we're done :)

		return currentItem;
	}

	var itemTitleColumn = null;

	function determineTitleColumn() {
		// try the first 3 columns only
		for (var i = 0; i < 4; ++i) {
			var cells = $('.h-schedule td.h-' + i);

			for (var j = 0; j < cells.length; ++j) {
				// we found a non-empty column!
				if ($(cells[j]).text() !== '') {
					return i;
				}
			}
		}

		// fallback to the first column
		return 0;
	}

	function getItemTitle(item) {
		// determine what column contains the item's title; some schedules leave the first column
		// empty to have the scheduled time and the estimate right next to each other
		if (itemTitleColumn === null) {
			itemTitleColumn = determineTitleColumn();
		}

		var text = item.find('.h-' + itemTitleColumn).text();
		if (text === '') {
			text = '(unnamed item)';
		}

		return text;
	}

	function getItemScheduled(item) {
		return item.find('.h-s time').attr('datetime');
	}

	function updateTicker() {
		var current = findCurrentItem();

		$('.h-schedule tr.success').removeClass('success');

		if (!current) {
			$('.h-ticker').hide();
			return;
		}

		var next        = current.next('tbody');
		var currentNode = $('.h-current');
		var nextNode    = $('.h-next');

		$('.h-ticker').show();
		$('.panel-body', currentNode).text(getItemTitle(current));

		if (next.length > 0) {
			$('.panel-body', nextNode).text(getItemTitle(next));
			$('time', nextNode).attr('datetime', getItemScheduled(next));
		}
		else {
			nextNode.parent().remove();
			currentNode.parent()
				.removeClass('col-lg-offset-2 col-md-offset-2 col-sm-offset-1 col-sm-5')
				.addClass('col-lg-offset-4 col-md-offset-4 col-sm-offset-3 col-sm-6')
			;
		}

		current.find('.h-primary').addClass('success');
	}

	function highlightRows(termString) {
		var terms = [];

		$.each(termString.split(','), function(i, term) {
			term = term.trim();

			if (term.match(/^[a-z0-9-_&=;:#% ]+$/i)) {
				terms.push(term);
			}
		});

		// unset previous highlights
		$('.h-schedule .danger').removeClass('danger');

		if (terms.length === 0) {
			return;
		}

		var rows = $('.h-schedule .h-primary');

		// do not use "\b" because we allow some special characters and those would give bad results
		// when combined with \b

		var search = new RegExp('(^|[^a-z0-9_])(' + terms.join('|') + ')($|[^a-z0-9_])', 'i');

		for (var height = rows.length, y = 0; y < height; y++) {
			var row   = $(rows[y]);
			var cells = $('td:not(.h-s):not(.h-l):not(.h-co)', row);

			for (var width = cells.length, x = 0; x < width; x++) {
				if ($(cells[x]).text().match(search)) {
					row.addClass('danger');
					break;
				}
			}
		}
	}

	$('html').addClass('js');

	var prev = null;

	// render localized times

	$('time.h-fancy').each(function() {
		$(this).text(moment($(this).attr('datetime')).format('dddd, LL'));
	});

	$('time.h-fancy-time').each(function() {
		$(this).text(moment($(this).attr('datetime')).format('HH:mm:ss'));
	});

	// setup back buttons

	$('body').on('click', '.h-back-btn', function() {
		history.back();
		return false;
	});

	// remove previous day breaks (computed by the server, based on the schedule timezone)
	$('.h-new-day').remove();

	var scheduledFormat = null;
	var language        = (window.navigator.userLanguage || window.navigator.language || 'some-where').toLowerCase();
	var is12HourClock   = ['en-us', 'en-ca', 'en-au', 'en-ph', 'fil-ph', 'en-nz'].indexOf(language) !== -1;

	if (!is12HourClock) {
		moment.defineLocale('en-24hours', {
			parentLocale: 'en',
			longDateFormat: {
            LTS: 'HH:mm:ss',
            LT: 'HH:mm',
            L: 'MM/DD/YYYY',
            LL: 'MMMM D, YYYY',
            LLL: 'MMMM D, YYYY LT',
            LLLL: 'dddd, MMMM D, YYYY LT'
			}
		});
	}

	$('.h-s time').each(function() {
		var d = new Date($(this).attr('datetime')), m = moment(d), element = $(this);

		if (scheduledFormat === null) {
			scheduledFormat = element.closest('.h-schedule').data('precision') === 'seconds' ? 'LTS' : 'LT';
		}

		element.text(m.format(scheduledFormat));

		if (prev !== null && d.getDate() !== prev) {
			element.closest('tr').before('<tr class="h-new-day info"><td colspan="99">' + m.format('dddd, LL') + '</td></tr>');
		}

		prev = d.getDate();
	});

	// highlight rows containing a search term
	highlightRows(window.location.hash.replace('#', '') || qs('highlight'));

	$(window).on('hashchange', function(e) {
		highlightRows(window.location.hash.replace('#', '') || qs('highlight'));
	});

	$('#localized-note small').toggle();

	// Add funky behaviour to the schedule
	if ($('#controls').length > 0) {
		$('.h-schedule tbody tr.h-primary').append($('#controls').html());
		$('.h-schedule thead tr').append('<th class="h-co">&nbsp;</th>');

		var template = $($('#expanded_tpl').html().trim());
		var columns  = $('.h-schedule').data('columns');

		// more/less toggling

		$('.h-schedule').on('click', '.h-co button', function(event) {
			var btn  = $(this);
			var row  = btn.closest('tr');
			var mode = btn.attr('rel');
			var tpl  = template.clone();
			var i, text, len;

			if (mode === 'less') {
				row.next('.h-secondary').remove();
			}
			else {
				tpl.find('dd.h-e-l').text(row.find('.h-l').text());

				len = 0;

				for (i = 0; i < columns; ++i) {
					text = row.find('.h-' + i).html();

					if (text.trim().length === 0) {
						tpl.find('.h-e-' + i).remove();
					}
					else {
						tpl.find('dd.h-e-' + i).html(text);
						len++;
					}
				}

				tpl.addClass('h-e-l' + len);
				row.after(tpl);
			}

			btn.parent().toggleClass('expanded');
		});

		// allow to show the full table
		$('.h-schedule').before($('#toggler').html());

		$('#h-toggle-usability').on('click', function() {
			$('html').toggleClass('js');
			$('.h-secondary').remove();
			$('.h-schedule .expanded').removeClass('expanded');
			$('.h-schedule .h-co').toggleClass('hidden');
		});
	}

	// show ticker
	$('.h-ticker').append($('#ticker').html());

	// update ticker
	window.setInterval(updateTicker, 5000);
	updateTicker();

	$('.h-jumper').on('click', function() {
		var item = findCurrentItem();

		if (!item) {
			return false;
		}

		$('html, body').animate({ scrollTop: item.offset().top - 100 }, 'slow');

		return false;
	});

	window.setInterval(updateRelativeTimes, 5000);
	updateRelativeTimes();

	// calendar navigation

	$('.h-calendar-nav select').on('change', function() {
		window.location = '/-/calendar/' + $(this).val();
	});
});
