function Item(id, length, columns, pos) {
	var self = this;

	// setup simple data properties

	self.id         = ko.observable(id);
	self.length     = ko.observable(length);
	self.scheduled  = ko.observable();      // will be set by calculateSchedule()
	self.dateSwitch = ko.observable(false); // will be set by calculateSchedule()

	// setup simple properties for the schedule columns

	scheduleColumns.forEach(function(colID) {
		var name  = 'col_' + colID;
		var value = '';

		if (columns.hasOwnProperty(colID)) {
			value = columns[colID];
		}

		self[name] = ko.observable(value);
	});

	// setup properties for managing app state

	self.position  = pos;
	self.suspended = false;
	self.expanded  = ko.observable(false);
	self.deleting  = ko.observable(false);
	self.busy      = ko.observable(false);
	self.errors    = ko.observable(false);

	// computed properties

	self.formattedLength = ko.pureComputed({
		owner: self,
		read: function() {
			return moment.unix(self.length()).utc().format('HH:mm:ss');
		},
		write: function(value) {
			self.length(parseLength(value));
		}
	});

	self.formattedSchedule = ko.pureComputed(function() {
		return moment.unix(self.scheduled() / 1000).zone(horaro.schedule.tz).format('LT');
	}, self);

	self.rowClass = ko.pureComputed(function() {
		if (self.busy()) {
			return 'warning';
		}

		if (self.errors()) {
			return 'danger h-has-errors';
		}

		if (self.deleting()) {
			return 'danger';
		}

		return '';
	}, self);

	// subscribers

	self.length.subscribe(function(newValue) {
		self.sync({length: newValue});
		viewModel.calculateSchedule(0);
	});

	scheduleColumns.forEach(function(colID) {
		var name = 'col_' + colID;

		self[name].subscribe(function(newValue) {
			var columns = {};
			columns[colID] = newValue;

			self.sync({columns: columns});
		});
	});

	self.sync = function(patch) {
		if (self.suspended) {
			return;
		}

		var itemID = self.id();
		var isNew  = itemID === -1;
		var method = 'POST';
		var url    = '';

		if (isNew) {
			url = '/-/schedules/' + scheduleID + '/items';

			// When creating an element, send all non-empty fields instead of just the one that
			// has been changed (i.e. the one in patch); this makes sure the length gets sent
			// along when someone edits a content column first (without the length, the request
			// would always fail, because items with length=0 are not allowed).
			patch = {
				length: self.length(),
				columns: {}
			};

			scheduleColumns.forEach(function(colID) {
				var key   = 'col_' + colID;
				var value = self[key]();

				patch.columns[colID] = value;
			});
		}
		else {
			url = '/-/schedules/' + scheduleID + '/items/' + itemID + '?_method=PATCH';
		}

		self.busy(true);

		$.ajax({
			type: method,
			url: url,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(patch),
			success: function(result) {
				self.suspended = true;

				self.id(result.data.id);
				self.length(result.data.length);
				self.errors(false);

				horaro.schedule.columns.forEach(function(id) {
					var key   = 'col_' + id;
					var value = id in result.data.columns ? result.data.columns[id] : '';

					self[key](value);
				});

				if (isNew) {
					viewModel.initDragAndDrop(true);
				}

				self.suspended = false;
			},
			error: function(result, data) {
				self.errors(result.responseJSON.errors);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	self.deleteItem = function(patch) {
		if (self.suspended) {
			return;
		}

		var itemID = self.id();

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/' + itemID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			success: function(result) {
				viewModel.items.remove(self);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	self.toggle = function() {
		self.expanded(!self.expanded());
	};

	self.confirmDelete = function() {
		self.deleting(true);
	};

	self.cancelDelete = function() {
		self.deleting(false);
	};

	self.doDelete = function() {
		self.deleteItem();
	};
}
