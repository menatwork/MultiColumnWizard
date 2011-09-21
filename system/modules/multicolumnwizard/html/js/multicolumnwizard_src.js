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
 * @package    MultiColumnWizard
 * @license    GNU/LGPL
 * @filesource
 */


/**
 * Class MultiSelect
 *
 * Provide methods to handle back end tasks.
 * @copyright  Leo Feyer 2005-2011
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Backend
 */
var MultiSelect =
{
    changeNamerecursive: function(arrEl, level)
    {
        arrEl.each(function(el)
        {
            if (el.name != undefined && el.name != null && el.name != '' )
            {
                var name = el.name.substring(0, el.name.indexOf('['));
                el.name = el.name.replace(new RegExp(name+'\[[0-9]+\]', 'ig'), name+'[' + level + ']');
            }
			
            $H(el.getProperties('for', 'id', 'onclick')).each(function(value, key)
            {
                if (value != undefined && value != null && value != '')
                {
                    el.setProperty(key, value.replace(/_row[0-9]+_/ig, '_row' + level + '_'));
                }
            });
			
            if (el.getChildren().length > 0)
            {
                MultiSelect.changeNamerecursive(el.getChildren(), level);
            }
        })
    },

    /**
	 * Module wizard
	 * @param object
	 * @param string
	 * @param string
	 */
    moduleWizard: function(el, command, id)
    {
        var table = $(id);
        var tbody = table.getFirst().getNext();
        var parent = $(el).getParent('tr');
        var rows = tbody.getChildren();
        var maxCount = table.getProperty('rel').match(/\[[0-9]+\]/ig)[0].replace('[','').replace(']','').toInt();
		
        if (window.Backend)
            Backend.getScrollOffset();

        switch (command)
        {
            case 'copy':
                var tr = new Element('tr');
                var childs = parent.getChildren();

                for (var i=0; i<childs.length; i++)
                {
                    var next = childs[i].clone(true, true).injectInside(tr);
                    next.getFirst().value = childs[i].getFirst().value;
                }

                tr.injectAfter(parent);
                if (maxCount <= tbody.getChildren().length && maxCount != 0 ){
                    console.log(tbody.getChildren().length);
                    tbody.getElements('img[src=system/themes/default/images/copy.gif]').setStyle('display', 'none');
                }
                break;

            case 'up':
                parent.getPrevious() ? parent.injectBefore(parent.getPrevious()) : parent.injectInside(tbody);
                break;

            case 'down':
                parent.getNext() ? parent.injectAfter(parent.getNext()) : parent.injectBefore(tbody.getFirst());
                break;

            case 'delete':
                if(rows.length > 1){
                    parent.destroy();
                } 
                else 
                {
                    var childs = parent.getElements('input','select','textarea');
                    for (var i=0; i<childs.length; i++)
                    {
                        // perhaps we have to unselect/uncheck ?
                        childs[i].set('value','');
                    }
                }
                if (maxCount > tbody.getChildren().length ){
                    tbody.getElements('img[src=system/themes/default/images/copy.gif]').setStyle('display', 'inline');                       
                }
                break;
        }

        rows = tbody.getChildren();

        for (var i=0; i<rows.length; i++)
        {
            var childs = rows[i].getChildren();
            for (var j=0; j<childs.length; j++)
            {
                var children = childs[j].getChildren();
                MultiSelect.changeNamerecursive(children, i)
            }
        }
    }
};

