function ItemsViewModel(items) {
	var self = this;

	self.items = ko.observableArray(items);

	// helper

	function findItem(itemID) {
		return findModelByID(self.items(), itemID);
	}

	// computed properties

	self.hasNewItem = ko.pureComputed(function() {
		return hasNewModel(self.items());
	}, self);

	self.isFull = ko.pureComputed(function() {
		return self.items().length >= maxItems;
	});

	// subscribers

	self.items.subscribe(function(items) {
		var pos = 1;

		items.forEach(function(item) {
			item.position(pos);
			pos++;
		});
	});

	// behaviours

	self.calculateSchedule = function(startIdx) {
		var start, i, len, items, item, scheduled, prev, date, dayOfYear;

		startIdx = startIdx || 0;
		items    = self.items();

		if (startIdx === 0) {
			start = scheduleStart.getTime();
		}
		else {
			start = items[startIdx].scheduled() + ((items[startIdx].length() + items[startIdx].setup()) * 1000);
		}

		scheduled = start;
		prev      = null;

		for (i = startIdx, len = items.length; i < len; ++i) {
			item = items[i];

			item.scheduled(scheduled);
			item.dateSwitch(false);

			date       = moment.unix(scheduled / 1000).utcOffset(scheduleTZ);
			dayOfYear  = date.dayOfYear();
			scheduled += ((item.length() + item.setup()) * 1000);

			if (prev !== null && prev !== dayOfYear) {
				item.dateSwitch(date.format('dddd, ll'));
			}

			prev = dayOfYear;
		}
	};

	self.add = function() {
		var data = {}, item;

		scheduleColumns.forEach(function(id) {
			data[id] = '';
		});

		item = new Item(-1, 30*60, data, scheduleSetupTime, self.items().length + 1);
		item.sync();

		self.items.push(item);
		$('.h-scheduler tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(itemID, newPos) {
		var item   = findItem(itemID);
		var data   = { item: itemID, position: newPos };
		var oldPos = item.position();

		// illegal move
		if (newPos < 1 || newPos > self.items().length) {
			return;
		}

		// Even if we don't actually move the item, we need to re-generate a fresh tbody element
		// because the old one was detached from the DOM during the dragging.

		var insertAt = newPos - 1; // -1 because splice() uses the internal, 0-based array

		self.items.remove(item);
		self.items.splice(insertAt, 0, item);

		// Now we can stop.

		if (oldPos == newPos) {
			return;
		}

		data[csrfTokenName] = csrfToken;

		item.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/move',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			complete: function() {
				item.busy(false);
			}
		});
	};

	self.initDragAndDrop = function() {
		nativesortable($('.h-scheduler')[0], {
			change: function(table, tbody) {
				var row    = $(tbody);
				var newPos = row.index() + 1;
				var itemID = row.data('itemid');

				// This is just the detached row that KO doesn't know anything about anymore.
				// The move() will take care of re-adding the moved row at the correct spot and
				// thereby trigger a fresh tbody element by KO.

				row.remove();
				self.move(itemID, newPos);
			}
		});
	};

	ko.computed(function() {
		self.calculateSchedule();
	});
}
