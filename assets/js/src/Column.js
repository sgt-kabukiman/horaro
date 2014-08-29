function Column(id, name, pos) {
	var self = this;

	// setup simple data properties

	self.id   = ko.observable(id);
	self.name = ko.observable(name);

	// setup properties for managing app state

	self.position  = pos;
	self.suspended = false;
	self.deleting  = ko.observable(false);
	self.busy      = ko.observable(false);
	self.errors    = ko.observable(false);

	// computed properties

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

	self.name.subscribe(function(newValue) {
		if (self.suspended) {
			return;
		}

		var colID = self.id();
		var isNew = colID === -1;
		var url   = '/-/schedules/' + scheduleID + '/columns';

		if (!isNew) {
			url += '/' + colID + '?_method=PUT';
		}

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: url,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ name: self.name() }),
			success: function(result) {
				self.suspended = true;

				self.id(result.data.id);
				self.name(result.data.name);
				self.errors(false);

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
	});

	self.deleteColumn = function(patch) {
		if (self.suspended) {
			return;
		}

		var colID = self.id();

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/columns/' + colID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			success: function(result) {
				viewModel.columns.remove(self);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	self.confirmDelete = function() {
		self.deleting(true);
	};

	self.cancelDelete = function() {
		self.deleting(false);
	};

	self.doDelete = function() {
		self.deleteColumn();
	};
}
