// knockout.x-editable library v0.1.0
// (c) Brian Chance - https://github.com/brianchance/knockout-x-editable
// Licensed MIT
//
// This file is basically identical to
// https://github.com/brianchance/knockout-x-editable in version
// bb5e7ef3a3f34add6f2718bf05fdc502194d8c3d, but has a patch to make the whole
// thing work with computed properties. Look for the <patch> comment to find it.

(function(factory) {
    if (typeof define === "function" && define.amd) {
        // AMD anonymous module
        define(["knockout", "jquery"], factory);
    } else {
        // No module loader (plain <script> tag) - put directly in global namespace
        factory(window.ko, window.jQuery);
    }
})(function(ko, $) {
	ko.bindingHandlers.editable = {
		init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
			var $element = $(element),
				value = valueAccessor(),
				allBindings = allBindingsAccessor(),
				editableOptions = allBindings.editableOptions || {};

			editableOptions.value = ko.utils.unwrapObservable(value);

			if (!editableOptions.name) {
				$.each(bindingContext.$data, function (k, v) {
					if (v == value) {
						editableOptions.name = k;
						return false;
					}
				});
			}

			//wrap calls to knockout.validation
			if (!editableOptions.validate && value.isValid) {
				editableOptions.validate = function (testValue) {
					//have to set to new value, then call validate, then reset to original value
					//not pretty, but works
					var initalValue = value();
					value(testValue);
					var res = value.isValid() ? null : ko.utils.unwrapObservable(value.error);
					value(initalValue);
					return res;
				}
			}

			if ((editableOptions.type === 'select' || editableOptions.type === 'checklist'|| editableOptions.type === 'typeahead') && !editableOptions.source && editableOptions.options) {
				if (editableOptions.optionsCaption)
					editableOptions.prepend = editableOptions.optionsCaption;

				//taken directly from ko.bindingHandlers['options']
				function applyToObject(object, predicate, defaultValue) {
					var predicateType = typeof predicate;
					if (predicateType == "function")    // Given a function; run it against the data value
						return predicate(object);
					else if (predicateType == "string") // Given a string; treat it as a property name on the data value
						return object[predicate];
					else                                // Given no optionsText arg; use the data value itself
						return defaultValue;
				}

				editableOptions.source = function() {
					return ko.utils.arrayMap(editableOptions.options(), function (item) {
						var optionValue = applyToObject(item, editableOptions.optionsValue, item);
						var optionText = applyToObject(item, editableOptions.optionsText, optionText);

						return {
							value: ko.utils.unwrapObservable(optionValue),
							text: ko.utils.unwrapObservable(optionText)
						};
					});
				}
			}

			if (editableOptions.visible && ko.isObservable(editableOptions.visible)) {
				editableOptions.toggle = 'manual';
			}

			//create editable
			var $editable = $element.editable(editableOptions);

			//update observable on save
			if (ko.isObservable(value)) {
				$editable.on('save.ko', function (e, params) {
					value(params.newValue);
				});

				// <patch>
				// since X-Editable is so nice to us, it will re-set the value of the element to its
				// own internal representation (i.e. what the user entered, not what the computed model
				// returns), we have to override that after the popup has been closed
				$editable.on('hidden.ko', function (e, reason) {
					if (reason === 'save') {
						$editable.editable('setValue', value(), true);
					}
				});
				// <endpatch>
			}

			// register custom event handlers

			if (editableOptions.save) {
				$editable.on('save', editableOptions.save);
			}

			if (editableOptions.hidden) {
				$editable.on('hidden', editableOptions.hidden);
			}

			//setup observable to fire only when editable changes, not when options change
			//http://www.knockmeout.net/2012/06/knockoutjs-performance-gotcha-3-all-bindings.html
			ko.computed({
				read: function () {
					var val = ko.utils.unwrapObservable(valueAccessor());
					if (val === null) val = '';
					$editable.editable('setValue', val, true)
				},
				owner: this,
				disposeWhenNodeIsRemoved: element
			});

			if (editableOptions.visible && ko.isObservable(editableOptions.visible)) {
				ko.computed({
					read: function () {
						var val = ko.utils.unwrapObservable(editableOptions.visible());debugger;
						if (val)
							$editable.editable('show');
					},
					owner: this,
					disposeWhenNodeIsRemoved: element
				});

				$editable.on('hidden.ko', function (e, params) {
					editableOptions.visible(false);
				});
			}
		}
	};
});
