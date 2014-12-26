function ColumnsViewModel(columns) {
	var self = this;

	self.columns = ko.observableArray(columns);

	self.hasNewColumn = ko.pureComputed(function() {
		return hasNewModel(self.columns());
	}, self);

	self.numOfFlexibleColumns = ko.pureComputed(function() {
		for (var cols = self.columns(), i = 0, n = 0; i < cols.length; ++i) {
			if (!cols[i].fixed) {
				n++;
			}
		}

		return n;
	});

	self.numOfFixedItems = ko.pureComputed(function() {
		return self.columns().length - self.numOfFlexibleColumns();
	});

	self.isFull = ko.pureComputed(function() {
		return self.numOfFlexibleColumns() >= 10;
	});

	self.add = function() {
		self.columns.push(new Column(-1, '', self.numOfFlexibleColumns() + 1, false));
		$('.h-columnist tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(columnID, newPos) {
		var columns  = self.columns;
		var fixedLen = self.numOfFixedItems();
		var col      = self.findColumn(columnID);
		var data     = { column: columnID, position: newPos };

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

		// escape to floats for simple re-sorting goodness
		col.position = (newPos < col.position) ? (newPos - 0.5) : (newPos + 0.5);

		columns.sort(function(a, b) {
			if (a.fixed === b.fixed) return a.position - b.position;
			return a.fixed ? -1 : 1;
		});

		// re-number the list
		columns().forEach(function(col, idx) {
			col.position = (idx - fixedLen) + 1;
		});
	};

	self.findColumn = function(colID) {
		return findModelByID(self.columns(), colID);
	};

	self.initDragAndDrop = function(reinit) {
		$('.h-columnist').sortable({
			handle: '.h-handle',
			items: '.h-column.h-flexible'
		});

		if (!reinit) {
			$('.h-columnist').on('sortupdate', function(event, stuff) {
				var row    = stuff.item;
				var newPos = row.index('.h-flexible') + 1;
				var colID  = row.data('colid');

				self.move(colID, newPos);
			});
		}
	};
}
