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
 * @copyright   Andreas Schempp 2011, certo web & design GmbH 2011, MEN AT WORK 2011
 * @package     MultiColumnWizard
 * @license     http://opensource.org/licenses/lgpl-3.0.html
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

	// instance callbacks (use e.g. myMCWVar.addOperationCallback() to register a callback that is for ONE specific MCW only)
	operationLoadCallbacks: [],
	operationClickCallbacks: [],
	
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
		this.options.table.getElement('tbody').getElements('tr').each(function(el, index)
		{
			el.getElement('td.operations').getElements('a').each(function(operation)
			{
				var key = operation.get('rel');

				// register static load callbacks
				if (MultiColumnWizard.operationLoadCallbacks[key])
				{
					MultiColumnWizard.operationLoadCallbacks[key].each(function(callback)
					{
						callback.pass([operation, el], self)();
					});
				}
				
				// register instance load callbacks
				if (self.operationLoadCallbacks[key])
				{
					self.operationLoadCallbacks[key].each(function(callback)
					{
						callback.pass([operation, el], self)();
					});
				}
				
				// remove all click events
				operation.removeEvents('click');
				
				// register static click callbacks
				if (MultiColumnWizard.operationClickCallbacks[key])
				{
					MultiColumnWizard.operationClickCallbacks[key].each(function(callback)
					{
						operation.addEvent('click', function(e)
						{
							e.preventDefault();
							callback.pass([operation, el], self)();
						});
					});
				}
				if(key == 'copy')
				{
					operation.addEvent('click', function(e)
					{
						e.preventDefault();
						self.updateOperations.pass([operation, el], self)();
					});
				}
				
				// register instance click callbacks
				if (self.operationClickCallbacks[key])
				{
					self.operationClickCallbacks[key].each(function(callback)
					{
						operation.addEvent('click', function(e)
						{
							e.preventDefault();
							callback.pass([operation, el], self)();
							self.updateFields(index);
						});
					});
				}
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
		row.getElements('.mcwUpdateFields *[name]').each(function(el)
		{
			// rewrite elements name
			if (typeOf(el.getProperty('name')) == 'string')
			{
				var erg = el.getProperty('name').match(/^([^\[]+)\[([0-9]+)\](.*)$/i);
				if (erg)
				{
					el.setProperty('name', erg[1] + '[' + level + ']' + erg[3]);
				}
			}

			// rewrite elements id
			if (typeOf(el.getProperty('id')) == 'string')
			{
				var erg = el.getProperty('id').match(/^(.+)_row[0-9]+_(.+)$/i);
				if (erg)
				{
					el.setProperty('id', erg[1] + '_row' + level + '_' + erg[2]);
				}
			}
		});

		row.getElements('.mcwUpdateFields label').each(function(el)
		{
			// rewrite elements for
			if (typeOf(el.getProperty('for')) == 'string')
			{
				var erg = el.getProperty('for').match(/^(.+)_row[0-9]+_(.+)$/i);
				if (erg)
				{
					el.setProperty('for', erg[1] + '_row' + level + '_' + erg[2]);
				}
			}
		});
		
		return row;
	},
	
	
	/**
	 * Add a load callback for the instance
	 * @param string the key e.g. 'copy' - your button has to have the matching rel="" attribute (<a href="jsfallbackurl" rel="copy">...</a>)
	 * @param function callback
	 */
	addOperationLoadCallback: function(key, func)
	{
		if (!this.operationLoadCallbacks[key])
		{
			this.operationLoadCallbacks[key] = [];
		}
		
		this.operationLoadCallbacks[key].push(func);
	},
	
	
	/**
	 * Add a click callback for the instance
	 * @param string the key e.g. 'copy' - your button has to have the matching rel="" attribute (<a href="jsfallbackurl" rel="copy">...</a>)
	 * @param function callback
	 */
	addOperationClickCallback: function(key, func)
	{
		if (!this.operationClickCallbacks[key])
		{
			this.operationClickCallbacks[key] = [];
		}
		
		this.operationClickCallbacks[key].push(func);
	}
});

/**
 * Extend the MultiColumnWizard with some static functions
 */
Object.append(MultiColumnWizard,
{
	// static callbacks (use e.g. MultiColumnWizard.addOperationCallback() to register a callback that is for EVERY MCW on the page)
	operationLoadCallbacks: {},
	operationClickCallbacks: {},

	/**
	 * Add a load callback for all the MCW's
	 * @param string the key e.g. 'copy' - your button has to have the matching rel="" attribute (<a href="jsfallbackurl" rel="copy">...</a>)
	 * @param function callback
	 */
	addOperationLoadCallback: function(key, func)
	{
		if (!MultiColumnWizard.operationLoadCallbacks[key])
		{
			MultiColumnWizard.operationLoadCallbacks[key] = [];
		}
		
		MultiColumnWizard.operationLoadCallbacks[key].push(func);
	},
	
	
	/**
	 * Add a click callback for all the MCW's
	 * @param string the key e.g. 'copy' - your button has to have the matching rel="" attribute (<a href="jsfallbackurl" rel="copy">...</a>)
	 * @param function callback
	 */
	addOperationClickCallback: function(key, func)
	{
		if (!MultiColumnWizard.operationClickCallbacks[key])
		{
			MultiColumnWizard.operationClickCallbacks[key] = [];
		}
		
		MultiColumnWizard.operationClickCallbacks[key].push(func);
	},

	
	/**
	 * Operation "copy" - load
	 * @param Element the icon element
	 * @param Element the row
	 */
	copyLoad: function(el, row)
	{
		var rowCount = row.getSiblings().length + 1;
		
		// remove the copy possibility if we have already reached maxCount
		if (this.options.maxCount > 0 && rowCount == this.options.maxCount)
		{
			el.destroy();
		}
	},


	/**
	 * Operation "copy" - click
	 * @param Element the icon element
	 * @param Element the row
	 */
	copyClick: function(el, row)
	{
		var rowCount = row.getSiblings().length + 1;
		
		// check maxCount for an inject
		if (this.options.maxCount == 0 || (this.options.maxCount > 0 && rowCount < this.options.maxCount))
		{
			// update the row count
			++rowCount;
			
			var copy = row.clone();
			
			// calculate -1 because the attributes start with 0, right? ;-)
			copy = this.updateRowAttributes(rowCount-1, copy);
			copy.injectAfter(row);
			this.updateOperations();
		}
		
		// remove the copy possibility if we just reach maxCount now (don't need to increment rowCount here as we already did when injecting)
		if (this.options.maxCount > 0 && rowCount == this.options.maxCount)
		{
			el.destroy();
		}
	},


	/**
	 * Operation "delete" - load
	 * @param Element the icon element
	 * @param Element the row
	 */
	deleteLoad: function(el, row)
	{
		var position = el.getAllPrevious().length - 1;
		
		// remove the delete possibility if necessary
		if (this.options.minCount > 0 && position == this.options.minCount)
		{
			el.destroy();
		}
	},


	/**
	 * Operation "delete" - click
	 * @param Element the icon element
	 * @param Element the row
	 */
	deleteClick: function(el, row)
	{
		row.destroy();
	},


	/**
	 * Operation "up" - click
	 * @param Element the icon element
	 * @param Element the row
	 */
	upClick: function(el, row)
	{
		var previous = row.getPrevious();
		if (previous)
		{
			// update the attributes so the order remains as desired
			// we have to set it to a value that is not in the DOM first, otherwise the values will get lost!!
			var previousPosition = previous.getAllPrevious().length;
			
			// this is the dummy setting (guess no one will have more than 99999 entries ;-))
			previous = this.updateRowAttributes(99999, previous);
			
			// now set the correct values again
			row = this.updateRowAttributes(previousPosition, row);
			previous = this.updateRowAttributes(previousPosition+1, previous);

			row.injectBefore(previous);
		}
	},


	/**
	 * Operation "down" - click
	 * @param Element the icon element
	 * @param Element the row
	 */
	downClick: function(el, row)
	{
		var next = row.getNext();
		if (next)
		{
			// update the attributes so the order remains as desired
			// we have to set it to a value that is not in the DOM first, otherwise the values will get lost!!
			var rowPosition = row.getAllPrevious().length;
			
			// this is the dummy setting (guess no one will have more than 99999 entries ;-))
			row = this.updateRowAttributes(99999, row);
			
			// now set the correct values again
			next = this.updateRowAttributes(rowPosition, next);
			row = this.updateRowAttributes(rowPosition+1, row);

			row.injectAfter(next);
		}
	}
});


/**
 * Register default callbacks
 */
//MultiColumnWizard.addOperationLoadCallback('copy', MultiColumnWizard.attachDatepicker);
MultiColumnWizard.addOperationLoadCallback('copy', MultiColumnWizard.copyLoad);
MultiColumnWizard.addOperationClickCallback('copy', MultiColumnWizard.copyClick);
MultiColumnWizard.addOperationLoadCallback('delete', MultiColumnWizard.deleteLoad);
MultiColumnWizard.addOperationClickCallback('delete', MultiColumnWizard.deleteClick);
MultiColumnWizard.addOperationClickCallback('up', MultiColumnWizard.upClick);
MultiColumnWizard.addOperationClickCallback('down', MultiColumnWizard.downClick);
