/*global jQuery, moment */

jQuery(function($) {
	'use strict';

	var prev = null;

	// render localized times

	$('time.h-fancy').each(function() {
		$(this).text(moment($(this).attr('datetime')).format('dddd, LL'));
	});

	// remove previous day breaks (computed by the server, based on the schedule timezone)
	$('.h-new-day').remove();

	$('.h-s time').each(function() {
		var d = new Date($(this).attr('datetime')), element = $(this);

		element.text(d.toLocaleTimeString());

		if (prev !== null && d.getDate() !== prev) {
			element.closest('tr').before('<tr class="h-new-day"><td colspan="99">' + moment(d).format('dddd, LL') + '</td></tr>');
		}

		prev = d.getDate();
	});

	$('#localized-note small').toggle();

	// Add responsiveness classes, because now (with JS available) we can show and
	// handle the more/less buttons. If we'd add the classes in the templates, it
	// would be possible that we hide stuff and users cannot read it (think of the
	// Google cache or archive.org).
	['xs', 'sm', 'md', 'lg'].forEach(function(size) {
		$('.h-schedule .'+size).addClass('hidden-'+size).removeClass(size);
	});

	// The actual more/less behaviour
	if ($('#controls').length > 0) {
		$('.h-schedule tbody tr.h-primary').append($('#controls').html());
		$('.h-schedule thead tr').append('<th class="h-co">&nbsp;</th>');
	}

	var template = $($('#expanded_tpl').html().trim());
	var columns  = $('.h-schedule').data('columns');

	$('.h-schedule').on('click', '.h-co button', function(event) {
		var btn        = $(this);
		var row        = btn.closest('tr');
		var mode       = btn.attr('rel');
		var allColumns = row.find('td');
		var tpl        = template.clone();
		var rel, i, text, len;

		if (mode === 'less') {
			row.next('.h-secondary').remove();
		}
		else {
			tpl.find('dd.h-e-l').text(row.find('.h-l').text());

			len = 0;

			for (var i = 0; i < columns; ++i) {
				text = row.find('.h-' + i).text();

				if (text.trim().length === 0) {
					tpl.find('.h-e-' + i).remove();
				}
				else {
					tpl.find('dd.h-e-' + i).text(text);
					len++;
				}
			}

			tpl.addClass('h-e-l' + len);
			row.after(tpl);
		}

		btn.parent().toggleClass('expanded');
	});

	// add
});
