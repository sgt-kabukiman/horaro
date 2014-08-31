function ItemsViewModel(items) {
	var self = this;

	self.items = ko.observableArray(items);

	self.hasNewItem = ko.pureComputed(function() {
		return hasNewModel(self.items());
	}, self);

	self.calculateSchedule = function(startIdx) {
		var start, i, len, items, item, scheduled;

		startIdx = startIdx || 0;
		items    = self.items();

		if (startIdx === 0) {
			start = horaro.schedule.start.getTime();
		}
		else {
			start = items[startIdx].scheduled() + (items[startIdx].length() * 1000);
		}

		scheduled = start;

		for (i = startIdx, len = items.length; i < len; ++i) {
			item = items[i];

			item.scheduled(scheduled);

			scheduled += (item.length() * 1000);
		}
	};

	self.add = function() {
		var data = {};

		horaro.schedule.columns.forEach(function(id) {
			data[id] = '';
		});

		self.items.push(new Item(-1, 30*60, data, self.items().length + 1));
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

	self.findItem = function(itemID) {
		return findModelByID(self.items(), itemID);
	};

	self.initDragAndDrop = function(reinit) {
		$('.h-scheduler').sortable({
			handle: '.h-handle',
			items: '.h-item',
			forcePlaceholderSize: true
		});

		if (!reinit) {
			$('.h-scheduler').on('sortupdate', function(event, stuff) {
				var row    = stuff.item;
				var newPos = row.index();    // 1-based
				var itemID = row.data('itemid');

				self.move(itemID, newPos);
			});
		}
	};

	ko.computed(function() {
		self.calculateSchedule();
	});
}
