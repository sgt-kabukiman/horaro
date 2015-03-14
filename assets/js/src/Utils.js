function parseLength(str) {
	var parts = str.split(':');

	// 'HH:MM:SS'
	if (parts.length >= 3) {
		return parts[0] * 3600 + parts[1] * 60 + parseInt(parts[2], 10);
	}

	// 'HH:MM'
	if (parts.length === 2) {
		return parts[0] * 3600 + parts[1] * 60;
	}

	// 'MM'
	if (parts.length === 1) {
		return parts[0] * 60;
	}

	return 0;
}

function findModelByID(models, id) {
	for (var len = models.length, i = 0; i < len; ++i) {
		if (models[i].id() === id) {
			return models[i];
		}
	}

	return null;
}

function hasNewModel(models) {
	return models.filter(function(model) {
		return model.id() === -1;
	}).length > 0;
}

function mirrorColumnWidths(sourceTable, targets) {
	var sources = $('tr:first > *', sourceTable);

	for (var i = 0, len = sources.length; i < len; ++i) {
		var w = $(sources[i]).innerWidth();

		$(targets[i]).css({
			maxWidth: w,
			width: w
		});
	}
}
