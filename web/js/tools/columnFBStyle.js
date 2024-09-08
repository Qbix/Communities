(function (window, Q, $, undefined) {

var Communities = Q.Communities;
	
/**
 * @module Communities
 */
	
/**
 * Apply to column title facebook style
 * @class Communities columnFBStyle
 * @constructor
 * @param {Object}	[options] Override various options for this tool
 * @param {Array}	[options.icons] Array of icons element as jquery objects
 * @param {Object}	[options.filter] Filter element as jquery object
 */
Q.Tool.define("Communities/columnFBStyle", function Communities_columnFBStyle_tool() {
	var tool = this;
	tool.addedElements = [];
	var state = this.state;
	var $toolElement = $(tool.element);
	var $columnsTitle = $toolElement.closest('.Q_columns_title');

	var $titleSlot = $(".Q_title_slot", tool.element);
	if (!$titleSlot.length) {
		throw new Q.Error("Communities/columnFBStyle: title slot not found");
	}

	$columnsTitle.on("scroll", function (e) {
		$columnsTitle[0].scrollTop = 0;
	});

	var columnsTool = Q.Tool.from($toolElement.closest(".Q_tool.Q_columns_tool"), "Q/columns");
	if (columnsTool) {
		var $column = $toolElement.closest(".Q_columns_column");
		columnsTool.state.onTransitionEnd.set(function (index, div) {
			if ($column[0] !== div) {
				return;
			}

			tool.adjustTitleWidth();
		}, tool);
	}

	// detect when title element become visible
	Q.ensure('IntersectionObserver', function () {
		tool._domObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.intersectionRatio < 0.9) {
					return;
				}

				if (entry.target === $titleSlot[0]) {
					tool.adjustTitleWidth();
				}
			});
		}, {
			root: tool.element
		});

		tool._domObserver.observe($titleSlot[0]);
	})

	// detect when title element resize
	Q.onLayout(tool).set(tool.adjustTitleWidth.bind(tool), tool);

	var $iconsContainer = $(".Q_icons_slot", tool.element);
	if (!$iconsContainer.length) {
		$iconsContainer = $("<div class='Q_icons_slot'>");
		$iconsContainer.appendTo(tool.element);
		Q.each(state.icons, function (i, element) {
			element.appendTo($iconsContainer);
		});

		var $filter = state.filter[0];
		if (!($filter instanceof $)) {
			return;
		}
		var $chooser = $("<div class='Communities_chooser' />");
		$chooser.append($filter).insertAfter($iconsContainer);
		$filter.off('blur.columnFBStyle').on('blur.columnFBStyle', _closeChooser)
			.off('keydown.columnFBStyle').on('keydown.columnFBStyle', function (e) {
			if (e.keyCode === 27) {
				$filter.blur();
			}
		});

		var $chooserTrigger = state.chooserTrigger || tool.$('.Communities_chooser_trigger');
		$chooserTrigger.on('click', function () {
			$titleSlot.add($iconsContainer).css('top', -1 * $titleSlot.outerHeight(true));
			$chooser.css('top', 0);
			$filter[0].focus(); //plugin('Q/clickfocus')
		});

		if ($filter instanceof jQuery) {
			Q.ensure('IntersectionObserver', function () {
				tool._domObserver.observe($filter[0]);
			});

			if (state.applyPlaceholder) {
				$filter.plugin('Q/placeholders', {}, _addCloseHandler);
			} else {
				_addCloseHandler();
			}
		}
		
		function _addCloseHandler() {
			$("<i class='qp-communities-close'></i>")
			.appendTo($chooser)
			.click(_closeChooser);
		}
		
		function _closeChooser() {
			var h = $titleSlot.outerHeight(true);
			// allow clicks a chance to be processed
			setTimeout(function () {
				$chooser.find('input').val('').trigger('input');
				$titleSlot.add($iconsContainer).css('top', '0');
				$chooser.css('top', h);
			}, 300);
		}
	}
},
{
	icons: [],
	filter: [],
	applyPlaceholder: true
},
{
	adjustTitleWidth: function () {
		var $titleSlot = $(".Q_title_slot", this.element);
		var $iconsContainer = $(".Q_icons_slot", this.element);

		var toolWidth = $(this.element).width();
		var titleMarginLeft = parseInt($titleSlot.css('margin-left')) || 0;
		var titleMarginRight = parseInt($titleSlot.css('margin-right')) || 0;
		var iconsWidth = $iconsContainer.width();

		$titleSlot.width(toolWidth - titleMarginLeft - titleMarginRight - iconsWidth);
	},
	Q: {
		beforeRemove: function () {
			if (this._domObserver) {
				this._domObserver.disconnect();
				this._domObserver = null;
			}
		}
	}
});

})(window, Q, Q.jQuery);