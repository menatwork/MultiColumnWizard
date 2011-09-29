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
 * @copyright  MEN AT WORK 2011
 * @author	Andreas Isaak <isaak@men-at-work.de>
 * @author	Yanick Witschi <yanick.witschi@certo-net.ch>
 * @author	Andreas Schempp <andreas@schempp.ch>
 * @package	MultiColumnWizard
 * @license	http://opensource.org/licenses/lgpl-3.0.html
 */


var MultiColumnWizard =
{

	'execute': function(el, command, id)
	{
		var table = $(id);
		var tbody = table.getFirst().getNext();
		var parent = $(el).getParent('tr');
		var options = {
			'maxCount': table.getProperty('rel').match(/maxCount\[[0-9]+\]/ig)[0].replace('maxCount[','').replace(']','').toInt(),
        	'uniqueFields': table.getProperty('rel').match(/unique\[[a-z0-9,]*\]/ig)[0].replace('unique[','').replace(']','').split(',')
		};
		
		// Do not run this in the frontend, Backend class would not be available
		if (window.Backend)
			Backend.getScrollOffset();

		// Execute the command
		MultiColumnWizard[command](tbody,parent,options);

		var rows = tbody.getChildren();

		for (var i=0; i<rows.length; i++)
		{
			var childs = rows[i].getChildren();
			for (var j=0; j<childs.length; j++)
			{
				var children = childs[j].getChildren();
				MultiColumnWizard.updateFieldName(children, i);
			}
			
			// Store attributes in the DOM or they would be lost when getting & setting innerHTML
			rows[i].getElements('select, input, textarea').each( function(el)
			{
				if (el.get('type') == 'checkbox' || el.get('type') == 'radio')
				{
					el.checked ? el.setAttribute('checked', 'checked') : el.removeAttribute('checked');
				}
				else if (el.get('tag') == 'select' && el.selectedIndex > 0)
				{
					var options = el.getChildren();
					options.each( function(option) { option.removeAttribute('selected') } );
					options[el.selectedIndex].setAttribute('selected', 'selected');
				}
				else
				{
					el.setAttribute('value', el.value);
				}
			});
			
			// Update things like ID and "for" attribute
			rows[i].set('html', rows[i].get('html').replace(/_row[0-9]+_/ig, '_row' + i + '_'));
		}
	},
	
	'copy': function(tbody, parent, options)
	{
		var tr = new Element('tr');
		var childs = parent.getChildren();

		for (var i=0; i<childs.length; i++)
		{
			var next = childs[i].clone(true, true).injectInside(tr);
			next.getFirst().value = childs[i].getFirst().value;
		}

		tr.injectAfter(parent);
		
		if (options.maxCount <= tbody.getChildren().length && options.maxCount != 0 )
		{
			tbody.getElements('img[src=system/themes/default/images/copy.gif]').setStyle('display', 'none');
		}
		
		if (options.uniqueFields.length > 1 || options.uniqueFields[0] != '')
		{
			for(var i=0; i<options.uniqueFields.length; i++)
			{
				var el = tr.getElements('*[name*=\['+options.uniqueFields[i]+'\]]');
				
				if (el)
				{
					MultiColumnWizard.clearElementValue(el);
				}
			}
		}
	},
	
	'up': function(tbody, parent, options)
	{
		parent.getPrevious() ? parent.injectBefore(parent.getPrevious()) : parent.injectInside(tbody);
	},
	
	'down': function(tbody, parent, options)
	{
		parent.getNext() ? parent.injectAfter(parent.getNext()) : parent.injectBefore(tbody.getFirst());
	},
	
	'delete': function(tbody, parent, options)
	{
		if (tbody.getChildren().length > 1)
		{
			parent.destroy();
		} 
		else 
		{
			var childs = parent.getElements('input,select,textarea');
			for (var i=0; i<childs.length; i++)
			{
				MultiColumnWizard.clearElementValue(childs[i]);
			}
		}
		
		if (options.maxCount > tbody.getChildren().length )
		{
			tbody.getElements('img[src=system/themes/default/images/copy.gif]').setStyle('display', 'inline');   					
		}
	},
	
	updateFieldName: function(arrEl, level)
	{
		arrEl.each(function(el)
		{
			if (el.name != undefined && el.name != null && el.name != '' )
			{
				var name = el.name.substring(0, el.name.indexOf('['));
				
				// More about this hack:
				// http://stackoverflow.com/questions/2094618/changing-name-attr-of-cloned-input-element-in-jquery-doesnt-work-in-ie6-7
				// http://matts411.com/post/setting_the_name_attribute_in_ie_dom/
				if (Browser.ie || Browser.Engine.trident)
				{
					var oldName = el.name;
					var newName = el.name.replace(new RegExp(name+'\[[0-9]+\]', 'ig'), name+'[' + level + ']');
					
					if (oldName != newName)
					{
						el.mergeAttributes(document.createElement("<INPUT name='" + newName + "'/>"), false);
					}
				}
				else
				{
					el.name = el.name.replace(new RegExp(name+'\[[0-9]+\]', 'ig'), name+'[' + level + ']');
				}
			}
			
			if (el.getChildren().length > 0)
			{
				MultiColumnWizard.updateFieldName(el.getChildren(), level);
			}
		});
	},
	
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
};

