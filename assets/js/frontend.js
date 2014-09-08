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

	// add the more/less control buttons
	$('.h-schedule .h-co').removeClass('hidden');

	// The actual more/less behaviour
	var template = $($('#expanded_tpl').html().trim());

	$('.h-schedule').on('click', '.h-co button', function(event) {
		var btn     = $(this);
		var row     = btn.closest('tr');
		var mode    = btn.attr('rel');
		var columns = row.find('.h-c');
		var tpl     = template.clone();

		if (mode === 'less') {
			row.next('.h-secondary').remove();
		}
		else {
			columns.each(function(idx, col) {
				tpl.find('div[rel="' + idx + '"]').text(col.innerText);
			});

			row.after(tpl);
		}

		btn.parent().toggleClass('expanded');
	});
});
