function Column(id, name, pos, fixed) {
	var self = this;

	// setup simple data properties

	self.id    = ko.observable(id);
	self.name  = ko.observable(name);
	self.fixed = !!fixed;

	// setup properties for managing app state

	self.position  = parseInt(pos, 10);
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

	// subscribers

	self.name.subscribe(function(newValue) {
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

		var data = { name: newValue };
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
	});

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
