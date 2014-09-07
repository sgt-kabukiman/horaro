/*global jQuery, horaro, moment */

jQuery(function($) {
	'use strict';

	var prev = null;

	// render localized times

	$('time.h-fancy').each(function() {
		$(this).text(moment($(this).attr('datetime')).format('dddd, LL'));
	});

	$('.h-scheduled time').each(function() {
		var d = new Date($(this).attr('datetime')), element = $(this);

		element.text(d.toLocaleTimeString());

		if (prev !== null && d.getDate() !== prev) {
			element.closest('tr').before('<tr class="h-new-day"><td colspan="100">' + moment(d).format('dddd, LL') + '</td></tr>');
		}

		prev = d.getDate();
	});
});
