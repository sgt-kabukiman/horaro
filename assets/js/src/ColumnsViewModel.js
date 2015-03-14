function ColumnsViewModel(columns) {
	var self = this;

	self.columns = ko.observableArray(columns);

	self.fixedColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === true;
		});
	});

	self.flexibleColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === false;
		});
	});

	self.hasNewColumn = ko.pureComputed(function() {
		return hasNewModel(self.columns());
	}, self);

	self.numOfFlexibleColumns = ko.pureComputed(function() {
		return self.flexibleColumns().length;
	});

	self.numOfFixedItems = ko.pureComputed(function() {
		return self.fixedColumns().length;
	});

	self.isFull = ko.pureComputed(function() {
		return self.numOfFlexibleColumns() >= 10;
	});

	self.isMinimal = ko.pureComputed(function() {
		for (var acc = 0, i = 0, cols = self.columns(), len = cols.length; i < len; ++i) {
			if (cols[i].fixed === false && cols[i].id() !== -1) {
				acc++;
			}
		}

		return acc <= 1;
	});

	self.add = function() {
		self.columns.push(new Column(-1, '', self.numOfFlexibleColumns() + 1, false));
		$('.h-columnist tbody:last').attr('draggable', 'true').find('a.editable:visible:first').editable('show');
	};

	self.move = function(columnID, newPos) {
		var col  = self.findColumn(columnID);
		var data = { column: columnID, position: newPos };

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

		self.syncOrderWithDom();
	};

	self.syncOrderWithDom = function() {
		// go by HTML node order to avoid problems with "concurrent" sorting operations
		var columnist = $('.h-columnist'), columns = self.columns;

		columns().forEach(function(col) {
			if (col.fixed === false) {
				col.position = columnist.find('tbody[data-colid="' + col.id() + '"]').index() + 1;
			}
		});

		columns.sort(function(a, b) {
			if (a.fixed === b.fixed) {
				return a.position === b.position ? 0 : (a.position < b.position ? -1 : 1);
			}

			return a.fixed ? -1 : 1;
		});
	};

	self.findColumn = function(colID) {
		return findModelByID(self.columns(), colID);
	};

	self.initDragAndDrop = function() {
		nativesortable($('.h-columnist')[0], {
			change: function(table, tbody) {
				var row    = $(tbody);
				var newPos = row.index() + 1;
				var colID  = row.data('colid');

				self.move(colID, newPos);
			}
		});
	};
}
