function ColumnsViewModel(columns) {
	var self = this;

	self.columns = ko.observableArray(columns);

	self.hasNewColumn = ko.pureComputed(function() {
		return hasNewModel(self.columns());
	}, self);

	self.add = function() {
		self.columns.push(new Column(-1, 'My Column', self.columns().length + 1));
	};

	self.move = function(itemID, newPos) {
		var items = self.items;
		var item  = self.findItem(itemID);

		item.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/move',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ item: itemID, position: newPos }),
			complete: function() {
				item.busy(false);
			}
		});

		// escape to floats for simple re-sorting goodness
		item.position = (newPos < item.position) ? (newPos - 0.5) : (newPos + 0.5);

		items.sort(function(a, b) {
			return a.position - b.position;
		});

		// re-number the list
		items().forEach(function(item, idx) {
			item.position = idx + 1;
		});
	};

	self.findColumn = function(colID) {
		return findModelByID(self.columns(), colID);
	};

	self.initDragAndDrop = function(reinit) {
		$('.h-columnist').sortable({
			handle: '.h-handle',
			items: '.h-column',
			forcePlaceholderSize: true
		});

		if (!reinit) {
			$('.h-columnist').on('sortupdate', function(event, stuff) {
				var row    = stuff.item;
				var newPos = row.index();    // 1-based
				var colID  = row.data('colid');

				self.move(colID, newPos);
			});
		}
	};
}
