function ItemsViewModel(items) {
	var self = this;

	self.items = ko.observableArray(items);

	self.hasNewItem = ko.pureComputed(function() {
		return hasNewModel(self.items());
	}, self);

	self.isFull = ko.pureComputed(function() {
		return self.items().length >= horaro.schedule.maxItems;
	});

	self.calculateSchedule = function(startIdx) {
		var start, i, len, items, item, scheduled, prev, date, dayOfYear;

		startIdx = startIdx || 0;
		items    = self.items();

		if (startIdx === 0) {
			start = horaro.schedule.start.getTime();
		}
		else {
			start = items[startIdx].scheduled() + (items[startIdx].length() * 1000);
		}

		scheduled = start;
		prev      = null;

		for (i = startIdx, len = items.length; i < len; ++i) {
			item = items[i];

			item.scheduled(scheduled);
			item.dateSwitch(false);

			date       = moment.unix(scheduled / 1000).zone(horaro.schedule.tz);
			dayOfYear  = date.dayOfYear();
			scheduled += (item.length() * 1000);

			if (prev !== null && prev !== dayOfYear) {
				item.dateSwitch(date.format('dddd, ll'));
			}

			prev = dayOfYear;
		}
	};

	self.add = function() {
		var data = {};

		horaro.schedule.columns.forEach(function(id) {
			data[id] = '';
		});

		self.items.push(new Item(-1, 30*60, data, self.items().length + 1));
		$('.h-scheduler tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(itemID, newPos) {
		var items = self.items;
		var item  = self.findItem(itemID);
		var data  = { item: itemID, position: newPos };

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

		// go by HTML node order to avoid problems with "concurrent" sorting operations
		var scheduler = $('.h-scheduler');

		items().forEach(function(item) {
			item.position = scheduler.find('tbody[data-itemid="' + item.id() + '"]').index() + 1;
		});

		// this kicks off the computed property afterwards to re-calculate the schedule
		items.sort(function(a, b) {
			return a.position - b.position;
		});
	};

	self.findItem = function(itemID) {
		return findModelByID(self.items(), itemID);
	};

	self.initDragAndDrop = function(reinit) {
		$('.h-scheduler').sortable({
			handle: '.h-handle',
			items: '.h-item'
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
