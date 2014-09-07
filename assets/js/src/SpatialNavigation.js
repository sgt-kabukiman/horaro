function SpatialNavigation(root) {
	var self  = this;
	var codes = {
		KEY_LEFT:  37,
		KEY_UP:    38,
		KEY_RIGHT: 39,
		KEY_DOWN:  40
	};

	self.root   = root;
	self.addBtn = function() { $('#h-add-model') };

	root.on('keydown', function(e) {
		var target = $(e.target);

		// do nothing on elements we don't care about
		if (!target.is('.editable') && !target.is('.h-controls button')) {
			return;
		}

		var interesting = false;

		for (var c in codes) {
			if (codes[c] === e.keyCode) {
				e.preventDefault();
				e.stopPropagation();
				interesting = true;
				break;
			}
		}

		if (!interesting) {
			return;
		}

		var row   = target.closest('tbody');
		var rows  = root.find('tbody');
		var nodes = row.find('.h-primary a:visible, .h-primary button:visible');
		var x     = nodes.index(target);
		var y     = rows.index(row);
		var maxX  = nodes.length - 1;
		var maxY  = rows.length - 1;
		var newX  = x;
		var newY  = y;

		switch (e.keyCode) {
			case codes.KEY_RIGHT: newX++; break;
			case codes.KEY_DOWN:  newY++; break;
			case codes.KEY_LEFT:  newX--; break;
			case codes.KEY_UP:    newY--; break;
		}

		// focus the add button when pressing down in the last row
		if (newY > maxY) {
			$('#h-add-model').focus();
			return;
		}

		if (newX > maxX) {
			return;
		}

		if (newY !== y) {
			nodes = $(rows[newY]).find('.h-primary a:visible, .h-primary button:visible');
		}

		$(nodes[newX]).focus();
	});

	$('body').on('keydown', '#h-add-model', function(e) {
		if (e.keyCode !== codes.KEY_UP) {
			return false;
		}

		e.preventDefault();
		e.stopPropagation();

		var row = root.find('tbody:last');

		if (row.length > 0) {
			row.find('a:visible:first').focus();
		}
	});
}
