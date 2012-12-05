/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright   terminal42 gmbh 2012, MEN AT WORK 2011
 * @package     MultiColumnWizard
 * @license     http://opensource.org/licenses/lgpl-3.0.html
 * @info        tab is set to 4 whitespaces
 */

var MultiColumnWizard = new Class(
	{
		Implements: [Options],
		options:
		{
			table: null,
			maxCount: 0,
			minCount: 0,
			uniqueFields: []
		},
		operations: {},

		/**
		 * Initialize the wizard
		 * @param Object options
		 */
		initialize: function(options)
		{
			this.setOptions(options);

			// make sure we really have the table as element
			this.options.table = document.id(this.options.table);

			// Do not run this in the frontend, Backend class would not be available
			if (window.Backend)
			{
				Backend.getScrollOffset();
			}

			this.updateOperations();
		},


		/**
		 * Update operations
		 */
		updateOperations: function()
		{
			var self = this;

			// execute load callback and register click event callback
			this.options.table.getElement('tbody').getChildren('tr').each(function(el, index)
			{
				el.getChildren('td.operations a').each(function(operation)
				{
					var key = operation.get('rel');
					if (!self.operations[key])
						self.operations[key] = [];
					self.operations[key].include(operation);


					// remove all click events
					operation.removeEvents('click');

					// fire events
					operation.addEvent('click', function(e)
					{
						e.preventDefault();
						window.fireEvent('mcw_button_click', [e, key, operation, el, self]);
					});
				});
			});
		},


		/**
		 * Update row attributes
		 * @param int level
		 * @param element row
		 * @return element the updated element
		 */
		updateRowAttributes: function(level, row)
		{

			row.getElements('.mcwUpdateFields *').each(function(el)
			{

				/*
				 *  We have to process the folowing steps:
				 *  - delete elements created by choosen or other Scripst and create new ones if necessary
				 *  - rewrite the attributes name, id, onlick, for
				 *  - rewrite inline SCRIPT-tags
				 *
				 */

				//remove choosen elements
				if (el.hasClass('chzn-container')){
					el.destroy();
					return;
				}

				// rewrite elements name
				if (typeOf(el.getProperty('name')) == 'string')
				{
					var erg = el.getProperty('name').match(/^([^\[]+)\[([0-9]+)\](.*)$/i);
					if (erg)
					{

						el.setProperty('name', erg[1] + '[' + level + ']' + erg[3]);
					}
				}

				// rewrite elements id or delete input fields without an id
				if (typeOf(el.getProperty('id')) == 'string')
				{
					var erg = el.getProperty('id').match(/^(.+)_row[0-9]+_(.+)$/i);
					if (erg)
					{
						el.setProperty('id', erg[1] + '_row' + level + '_' + erg[2]);

					}
				}

				// rewrite elements onclick (e.g. pagePicker)
				if (typeOf(el.getProperty('onclick')) == 'string')
				{
					var erg = el.getProperty('onclick').match(/^(.+)_row[0-9]+_(.+)$/i);
					if (erg)
					{

						el.setProperty('onclick', erg[1] + '_row' + level + '_' + erg[2]);
					}
				}

				//rewrite elements for attribute
				if (typeOf(el.getProperty('for')) == 'string')
				{
					var erg = el.getProperty('for').match(/^(.+)_row[0-9]+_(.+)$/i);
					if (erg)
					{

						el.setProperty('for', erg[1] + '_row' + level + '_' + erg[2]);
					}
				}

				// set attributes depending of the tag type
				switch (el.nodeName.toUpperCase())
				{

					case 'SELECT':
						//create new chosen (2.11 only)
						if (el.hasClass('tl_chosen')) new Chosen(el);
						break;
					case 'INPUT':
						//set input field to visible
						if (el.getStyle('display').toLowerCase() == 'none') el.setStyle('display','inline');
						// delete input field without ids (these input fields are created by JS)
						if (typeOf(el.getProperty('id')) != 'string') el.destroy();
						break;
					case 'SCRIPT':
						//rewrite inline
						//ToDO: refactor this part. For some reason replace will only find the first token of _row[0-9]+_
						var newScript = '';
						var script = el.get('html').toString();
						var length = 0;
						var start = script.search(/_row[0-9]+_/i);
						while(start > 0)
						{
							length = script.match(/(_row[0-9]+)+_/i)[0].length;
							newScript =  newScript + script.substr(0, start) + '_row' + level + '_';
							script = script.substr(start + length);
							start = script.search(/_row[0-9]+_/i);
						}

						el.set('html', newScript+script);

						break;
				}

			});

			return row;
		},


		killAllTinyMCE: function(el, row)
		{
			var parent = row.getParent('.multicolumnwizard');

			// skip if no tinymce class was found
			if(parent.getElements('.tinymce').length == 0)
			{
				return;
			}

			var mcwName = parent.get('id');
			var myRegex = new RegExp(mcwName);
			var tinyMCEEditors = new Array();
			var counter = 0;

			// get a list with tinymces
			tinyMCE.editors.each(function(item, index){
				if(item.editorId.match(myRegex) != null)
				{
					tinyMCEEditors[counter] = item.editorId;
					counter++;
				}
			});

			// clear tinymces
			tinyMCEEditors.each(function(item, index){
				try {
					var editor = tinyMCE.get(item);
					$(editor.editorId).set('text', editor.getContent());
					editor.remove();
				} catch (e) {
					console.log(e)
				}
			});

			// search for dmg tinymces
			parent.getElements('span.mceEditor').each(function(item, index){
				console.log(item.getSiblings('script'));
				item.dispose();
			});

			// search for scripttags tinymces
			parent.getElements('.tinymce').each(function(item, index){
				item.getElements('script').each(function(item, index){
					item.dispose();
				});
			});
		},


		reinitTinyMCE: function(el, row, isParent)
		{
			var parent = null;

			if(isParent != true)
			{
				parent = row.getParent('.multicolumnwizard');
			}
			else
			{
				parent = row;
			}

			// skip if no tinymce class was found
			if(parent.getElements('.tinymce').length == 0)
			{
				return;
			}

			var varTinys = parent.getElements('.tinymce textarea');

			varTinys.each(function(item, index){
				tinyMCE.execCommand('mceAddControl', false, item.get('id'));
				tinyMCE.get(item.get('id')).show();

				$(item.get('id')).erase('required');
				$(tinyMCE.get(item.get('id')).editorContainer).getElements('iframe')[0].set('title','MultiColumnWizard - TinyMCE');
			});
		},


		reinitStylect: function()
		{
			if( Stylect != null )
			{
				$$('.styled_select').each(function(item, index){
					item.dispose();
				});

				Stylect.convertSelects();
			}
		},


		/**
		 * @param Element the element which should be cleared
		 */
		clearElementValue: function(el)
		{
			if (el.get('type') == 'checkbox' || el.get('type') == 'radio')
			{
				el.checked = false;
			}
			else
			{
				el.set('value', '');
			}
		}
	});


/**
 * Register default callbacks
 */


/**
 * Operation "copy"
 * @param The original event
 * @param The operation key
 * @param Element the icon element
 * @param Element the row
 * @param MCW instance
 */
window.addEvent('mcw_button_click', function(e, key, operation, row, inst)
{
	if (key != 'copy')
		return;

	inst.killAllTinyMCE(operation, row);

	var rowCount = row.getSiblings().length + 1;

	// check maxCount for an inject
	if (inst.options.maxCount == 0 || (inst.options.maxCount > 0 && rowCount < inst.options.maxCount))
	{
		var copy = row.clone(true,true);

		//get the current level of the row
		level = row.getAllPrevious().length;

		//update the row attributes
		copy = inst.updateRowAttributes(++level, copy);
		copy.injectAfter(row);

		//exec script
		if (copy.getElements('script').length > 0)
		{
			copy.getElements('script').each(function(script){
				$exec(script.get('html'));
			});
		}

		//updtae the row attribute of the following rows
		var that = inst;
		copy.getAllNext().each(function(row){
			that.updateRowAttributes(++level, row);
		});
	}

	// update events
	inst.updateOperations();

	inst.reinitTinyMCE(operation, row, false);
	inst.reinitStylect();

	// re add the delete possibility if necessary
	if (inst.options.minCount > 0 && rowCount > inst.options.minCount)
	{
		$$(inst.operations['delete']).setStyle('display', 'inline');
	}

	rowCount++;
	// remove the copy possibility if we have already reached maxCount
	if (inst.options.maxCount > 0 && rowCount >= inst.options.maxCount)
	{
		$$(inst.operations['copy']).setStyle('display', 'none');
	}
});


/**
 * Operation "delete" - click
 * @param The original event
 * @param The operation key
 * @param Element the icon element
 * @param Element the row
 * @param MCW instance
 */
window.addEvent('mcw_button_click', function(e, key, operation, row, inst)
{
	if (key != 'delete')
		return;

	var rowCount = row.getSiblings().length + 1;

	// remove the delete possibility if necessary
	if (inst.options.minCount > 0 && rowCount <= inst.options.minCount)
	{
		$$(inst.operations['delete']).setStyle('display', 'none');
	}

	inst.killAllTinyMCE(operation, row);
	var parent = row.getParent('.multicolumnwizard');

	if (row.getSiblings().length > 0){
		//get all following rows
		var rows = row.getAllNext();
		//extract the current level
		level = row.getAllPrevious().length;

		//destroy current row
		row.destroy();

		var that = inst;
		//update index of following rows
		rows.each(function(row) {
			that.updateRowAttributes(level++, row);
		});
	}else{
		row.getElements('input,select,textarea').each(function(element){
			inst.clearElementValue(element);
		});
	}

	rowCount--;

	// make sure copy buttons are there on reaching < maxCount
	if (inst.options.maxCount > 0 && rowCount < inst.options.maxCount)
	{
		$$(inst.operations['copy']).setStyle('display', 'inline');
	}

	inst.reinitTinyMCE(operation, parent, true);
});



/**
 * Operation "up" - click
 * @param The original event
 * @param The operation key
 * @param Element the icon element
 * @param Element the row
 * @param MCW instance
 */
window.addEvent('mcw_button_click', function(e, key, operation, row, inst)
{
	if (key != 'up')
		return;

	inst.killAllTinyMCE(operation, row);

	var previous = row.getPrevious();
	if (previous)
	{
		// update the attributes so the order remains as desired
		// we have to set it to a value that is not in the DOM first, otherwise the values will get lost!!
		var previousPosition = previous.getAllPrevious().length;

		// this is the dummy setting (guess no one will have more than 99999 entries ;-))
		previous = inst.updateRowAttributes(99999, previous);

		// now set the correct values again
		row = inst.updateRowAttributes(previousPosition, row);
		previous = inst.updateRowAttributes(previousPosition+1, previous);

		row.injectBefore(previous);
	}

	inst.reinitTinyMCE(operation, row, false);
});


/**
 * Operation "down" - click
 * @param The original event
 * @param The operation key
 * @param Element the icon element
 * @param Element the row
 * @param MCW instance
 */
window.addEvent('mcw_button_click', function(e, key, operation, row, inst)
{
	if (key != 'down')
		return;

	inst.killAllTinyMCE(operation, row);

	var next = row.getNext();
	if (next)
	{
		// update the attributes so the order remains as desired
		// we have to set it to a value that is not in the DOM first, otherwise the values will get lost!!
		var rowPosition = row.getAllPrevious().length;

		// this is the dummy setting (guess no one will have more than 99999 entries ;-))
		row = inst.updateRowAttributes(99999, row);

		// now set the correct values again
		next = inst.updateRowAttributes(rowPosition, next);
		row = inst.updateRowAttributes(rowPosition+1, row);

		row.injectAfter(next);
	}

	inst.reinitTinyMCE(operation, row, false);
});