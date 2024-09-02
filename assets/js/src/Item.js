function Item(id, length, columns, setup, pos) {
	var self = this;

	// setup simple data properties

	self.id         = ko.observable(id);
	self.length     = ko.observable(length);
	self.setup      = ko.observable(setup);
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

	self.position  = ko.observable(parseInt(pos, 10));
	self.suspended = false;
	self.nextFocus = false;
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
	}, self);

	self.formattedSchedule = ko.pureComputed(function() {
		return moment.unix(self.scheduled() / 1000).utcOffset(scheduleTZ).format('LT');
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

	self.bodyClass = function() {
		return 'h-item ' + (this.$context.$index() % 2 === 1 ? 'h-odd' : 'h-even');
	};

	self.first = ko.pureComputed(function() {
		return self.position() <= 1;
	}, self);

	self.last = function() {
		return self.position() >= viewModel.items().length;
	};

	// subscribers

	self.length.subscribe(function(newValue) {
		self.sync({length: newValue});
		viewModel.calculateSchedule(0);
	});

	self.setup.subscribe(function(newValue) {
		self.sync({setup: newValue});
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

		patch[csrfTokenName] = csrfToken;

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
				self.setup(result.data.setup);
				self.errors(false);

				scheduleColumns.forEach(function(id) {
					var key   = 'col_' + id;
					var value = id in result.data.columns ? result.data.columns[id] : '';

					self[key](value);
				});

				self.suspended = false;

				if (self.nextFocus) {
					$('#h-add-model').focus();
					self.nextFocus = false;
				}
			},
			error: function(result) {
				self.errors(result.responseJSON.errors);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	self.deleteItem = function() {
		if (self.suspended) {
			return;
		}

		var itemID = self.id();
		var data   = {};

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/' + itemID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function() {
				viewModel.items.remove(self);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	self.toggle = function(item, event) {
		self.expanded(!self.expanded());
		$(event.target).parent().find('button:visible').focus();
	};

	function move(event, direction) {
		var scheduler = $(event.target).closest('table');
		var newPos    = self.position() + (direction === 'up' ? -1 : 1);

		viewModel.move(self.id(), newPos);

		// find the new DOM node for the just pressed button and focus it, if possible
		// (i.e. we're not first or last)
		var row = scheduler.find('tbody[data-itemid="' + self.id() + '"]');
		var btn = row.find('button.move-' + direction);

		if (btn.is('.disabled')) {
			btn = row.find('button.move-' + (direction === 'up' ? 'down' : 'up'));
		}

		btn.focus();
	}

	self.moveUp = function(item, event) {
		move(event, 'up');
	};

	self.moveDown = function(item, event) {
		move(event, 'down');
	};

	self.confirmDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(true);
		parent.find('.btn-default').focus();
	};

	self.cancelDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(false);
		parent.find('.btn-danger').focus();
	};

	self.doDelete = function(item, event) {
		var row  = $(event.target).closest('tbody');
		var next = row.next('tbody');

		if (next.length === 0) {
			next = row.prev('tbody');

			if (next.length === 0) {
				next = $('#h-add-model');
			}
		}

		if (next.is('tbody')) {
			next = next.find('button:visible:last');
		}

		self.deleteItem();
		next.focus();
	};

	self.onEditableHidden = function(event, reason) {
		var
			me      = $(this),
			root    = me.closest('table'),
			links   = root.find('a.editable:visible'),
			selfIdx = links.index(me),
			next    = (selfIdx < (links.length - 1)) ? $(links[selfIdx+1]) : $('#h-add-model');

		// advance to the next editable
		if (reason === 'save' || reason === 'nochange') {
			if (next.is('.editable')) {
				next.editable('show');
			}
			else {
				next.focus();

				// in case this saving triggers an ajax call to create the element,
				// the add button is still disabled right now. We set a flag to let
				// the success handler of the create call do the focussing.
				self.nextFocus = true;
			}
		}
		else {
			me.focus();
		}
	};

	self.getDisplayText = function(value) {
		if (typeof value === 'string' && value.length > 0) {
			var markup = inlineMarkdown(value);

			// Turn links into glorified spans, as they won't work anyway because we have click listeners
			// set up for X-Editable.
			var m      = $('<div>' + markup + '</div>');
			var suffix = ' <sup><i class="fa fa-external-link"></i></sup>';

			m.find('a').each(function() {
				var link   = $(this);
				var target = link.attr('href');
				var text   = link.text();

				link.replaceWith($('<span>').attr('title', 'link to ' + target).addClass('h-link').text(text).append(suffix));
			});

			$(this).html(m.html());
		}
		else {
			$(this).html('');
		}
	};
}
