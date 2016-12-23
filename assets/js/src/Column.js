function Column(id, name, pos, hidden, fixed) {
	var self = this;

	// setup simple data properties

	self.id     = ko.observable(id);
	self.name   = ko.observable(name);
	self.hidden = ko.observable(hidden);
	self.fixed  = !!fixed;

	// setup properties for managing app state

	self.position  = ko.observable(parseInt(pos, 10));
	self.suspended = false;
	self.nextFocus = false;
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

	self.bodyClass = function() {
		return 'h-column ' + (this.$context.$index() % 2 === 1 ? 'h-odd' : 'h-even');
	};

	self.deleteBtnClass = function() {
		return (self.fixed || self.id() === -1 || viewModel.isMinimal()) ? ' disabled' : '';
	};

	self.handleText = function() {
		return self.fixed ? '' : '::';
	};

	self.first = ko.pureComputed(function() {
		return self.position() <= 1;
	}, self);

	self.last = function() {
		return self.position() >= viewModel.numOfFlexibleColumns();
	};

	self.isOptionsColumn = function() {
		return self.name() === "[[options]]";
	};

	// subscribers

	function handleNameChange() {
		if (self.isOptionsColumn()) {
			self.suspended = true;
			self.hidden(true);
			self.suspended = false;
		}
	}

	function updateColumn() {
		if (self.suspended) {
			return;
		}

		var colID = self.id();
		var isNew = colID === -1;
		var url   = '/-/schedules/' + scheduleID + '/columns';

		if (self.fixed) {
			url += '/fixed';
		}

		if (!isNew) {
			url += '/' + colID + '?_method=PUT';
		}

		var data = {
			name: self.name(),
			hidden: self.hidden(),
		};

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: url,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function(result) {
				self.suspended = true;

				self.id(result.data.id);
				self.name(result.data.name);
				self.errors(false);

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
	}

	self.name.subscribe(handleNameChange);
	self.name.subscribe(updateColumn);
	self.hidden.subscribe(updateColumn);

	self.deleteColumn = function() {
		if (self.suspended) {
			return;
		}

		var colID = self.id();
		var data  = {};

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/columns/' + colID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function() {
				viewModel.columns.remove(self);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	function move(event, direction) {
		var columnist = $(event.target).closest('table');
		var newPos    = self.position() + (direction === 'up' ? -1 : 1);

		viewModel.move(self.id(), newPos);

		// find the new DOM node for the just pressed button and focus it, if possible
		// (i.e. we're not first or last)
		var row = columnist.find('tbody[data-colid="' + self.id() + '"]');
		var btn = row.find('button.move-' + direction);

		if (btn.is('.disabled')) {
			btn = row.find('button.move-' + (direction === 'up' ? 'down' : 'up'));
		}

		btn.focus();
	}

	self.moveUp = function(col, event) {
		move(event, 'up');
	};

	self.moveDown = function(col, event) {
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

	self.doDelete = function() {
		self.deleteColumn();
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
}
