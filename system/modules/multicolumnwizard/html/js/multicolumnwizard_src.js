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

var MultiColumnWizard =
{
    execHOOK: Array(),

    'execute': function(el, command, id)
    {
    	stopEvent = new Event(window.event);
    	stopEvent.preventDefault();
    	
        var table = $(id);
        var tbody = table.getFirst().getNext();
        var parent = $(el).getParent('tr');
        var options = {
            'maxCount': table.getProperty('rel').match(/maxCount\[[0-9]+\]/ig)[0].replace('maxCount[','').replace(']','').toInt(),
            'minCount': table.getProperty('rel').match(/minCount\[[0-9]+\]/ig)[0].replace('minCount[','').replace(']','').toInt(),
            'uniqueFields': table.getProperty('rel').match(/unique\[[a-z0-9,]*\]/ig)[0].replace('unique[','').replace(']','').split(',')
        };

        // Do not run this in the frontend, Backend class would not be available
        if (window.Backend)
        {
            Backend.getScrollOffset();
        }
        
        
        // Execute the command
        MultiColumnWizard[command](tbody,parent,options);


		// set name attribute to a dummy to avoid duplicate names
		tbody.getElements('input[type=radio], input[type=checkbox]').each(function(el,i){
        	if(typeof el.get('name') == 'string')
        		el.set('name', el.get('name')+'DUMMYNAME'+i);
		});
		
		// rewrite attributes 
		tbody.getChildren().each(function(el,i){
			MultiColumnWizard.updateFields(el.getChildren(), i)
		});
		
		// kill dummy names
		tbody.getElements('input[type=radio], input[type=checkbox]').each(function(el,i){
        	if(typeof el.get('name') == 'string')
        		el.set('name', el.get('name').replace('DUMMYNAME'+i,''));
		});


		// HOOK for other extensions like Autocompleter or Chosen        
        for(var i=0; i<MultiColumnWizard.execHOOK.length; i++) {      
            MultiColumnWizard.execHOOK[i](el, command, id);
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
            tbody.getElements('img[src=system/themes/default/images/copy.gif]').getParent().setStyle('display', 'none');
        }

        if (options.minCount < tbody.getChildren().length && options.minCount != 0 )
        {
            tbody.getElements('img[src=system/themes/default/images/delete.gif]').getParent().setStyle('display', 'inline');
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
            tbody.getElements('img[src=system/themes/default/images/copy.gif]').getParent().setStyle('display', 'inline');
        }

        if (options.minCount >= tbody.getChildren().length )
        {
            tbody.getElements('img[src=system/themes/default/images/delete.gif]').getParent().setStyle('display', 'none');
        }
    },

	/**
	 * Rewrite ID,NAME,FOR attributes
	 * for the fields
	 */
    updateFields: function(arrEl, level)
    {
        arrEl.each(function(el)
        {
			// also update the childs of this element
            if (el.getChildren().length > 0)
            {
                MultiColumnWizard.updateFields(el.getChildren(), level);
            }
            
        	// rewrite elements name
        	if(typeof el.get('name') == 'string')
        	{
        		var erg = el.get('name').match(/^([^\[]+)\[([0-9]+)\](.*)$/i);
        		if(erg) el.set('name', erg[1]+'['+level+']'+erg[3]);
        	}
        	// rewrite elements id
        	if(typeof el.get('id') == 'string')
        	{
        		var erg = el.get('id').match(/^(.+)_row[0-9]+_(.+)$/i);
        		if(erg) el.set('id', erg[1]+'_row'+level+'_'+erg[2]);
        	}
        	// rewrite elements for
        	if(typeof el.get('for') == 'string')
        	{
        		var erg = el.get('for').match(/^(.+)_row[0-9]+_(.+)$/i);
        		if(erg) el.set('for', erg[1]+'_row'+level+'_'+erg[2]);
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
    },
    
    
    attachDatepicker: function(el, command, id)
    {
    	// only if a new element is created
    	if(command != 'copy') return;
    	
		// get datepicker-fields from table-options
		var datepickerFields = $(id).getProperty('rel').match(/datepicker\[[a-z0-9,]*\]/ig)[0].replace('datepicker[','').replace(']','').split(',');
		var rows = $(id).getElement('tbody').getChildren();
		
		// reattach
        if (datepickerFields.length > 1 || datepickerFields[0] != '')
        {
            for(var i=0; i<datepickerFields.length; i++)
            {
                var elements = [];

                for (var r=0; r<rows.length; r++)
                {
                    var dateInput = id+'_row'+r+'_'+datepickerFields[i];
                    document.id(dateInput).setStyle('display', 'inline-block').getNext().destroy();

                    elements.include(dateInput);
                }

                var datepicker = id.replace('ctrl_', 'datepicker_')+'_'+datepickerFields[i];
                window[datepicker].attachTo = '#'+elements.join(',#');
                window[datepicker].options.toggleElements = '#'+elements.join(',#').replace(/ctrl_/g, 'toggle_');
                window[datepicker].attach();
            }
		}
	}		
};

// Register attachTatepicker callback
MultiColumnWizard.execHOOK.push(MultiColumnWizard.attachDatepicker);
