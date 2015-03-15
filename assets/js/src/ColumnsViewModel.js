function ColumnsViewModel(columns) {
	var self = this;

	self.columns = ko.observableArray(columns);

	// helper

	function findColumn(colID) {
		return findModelByID(self.columns(), colID);
	}

	// computed properties

	self.fixedColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === true;
		});
	}, self);

	self.flexibleColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === false;
		});
	}, self);

	self.hasNewColumn = ko.pureComputed(function() {
		return hasNewModel(self.columns());
	}, self);

	self.numOfFlexibleColumns = ko.pureComputed(function() {
		return self.flexibleColumns().length;
	}, self);

	self.numOfFixedItems = ko.pureComputed(function() {
		return self.fixedColumns().length;
	}, self);

	self.isFull = ko.pureComputed(function() {
		return self.numOfFlexibleColumns() >= 10;
	}, self);

	self.isMinimal = ko.pureComputed(function() {
		for (var acc = 0, i = 0, cols = self.columns(), len = cols.length; i < len; ++i) {
			if (cols[i].fixed === false && cols[i].id() !== -1) {
				acc++;
			}
		}

		return acc <= 1;
	});

	// subscribers

	self.columns.subscribe(function(columns) {
		var pos = 1;

		columns.forEach(function(col) {
			if (col.fixed === false) {
				col.position = pos;
				pos++;
			}
		});
	});

	// behaviours

	self.add = function() {
		self.columns.push(new Column(-1, '', self.numOfFlexibleColumns() + 1, false));
		$('.h-columnist tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(columnID, newPos) {
		var col  = findColumn(columnID);
		var data = { column: columnID, position: newPos };

		// Even if we don't actually move the column, we need to re-generate a fresh tbody element
		// because the old one was detached from the DOM during the dragging.

		var insertAt = newPos + self.numOfFixedItems() - 1; // -1 because splice() uses the internal, 0-based array

		self.columns.remove(col);
		self.columns.splice(insertAt, 0, col);

		// Now we can stop.

		if (col.position == newPos) {
			return;
		}

		data[csrfTokenName] = csrfToken;

		col.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/columns/move',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			complete: function() {
				col.busy(false);
			}
		});
	};

	self.initDragAndDrop = function() {
		nativesortable($('.h-columnist')[0], {
			change: function(table, tbody) {
				var row    = $(tbody);
				var newPos = row.index() + 1;
				var colID  = row.data('colid');

				// This is just the detached row that KO doesn't know anything about anymore.
				// The move() will take care of re-adding the moved row at the correct spot and
				// thereby trigger a fresh tbody element by KO.

				row.remove();
				self.move(colID, newPos);
			}
		});
	};
}
