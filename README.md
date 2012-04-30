Usage with columnFields
=======================

```php
<?php

$GLOBALS['TL_DCA']['tl_theme']['fields']['templateSelection'] = array
(
    'label'         => &$GLOBALS['TL_LANG']['tl_theme']['templateSelection'],
    'exclude'       => true,
    'inputType'     => 'multiColumnWizard',
    'eval'          => array
        (
        'columnFields' => array
            (
            'ts_client_os' => array
                (
                'label'         => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_os'],
                'exclude'       => true,
                'inputType'     => 'select',
                'eval'          => array
                    (
                        'style'                     => 'width:250px',
                        'includeBlankOption'        => true
                    ),
                'options'       => array
                    (
                        'option1'       => 'Option 1',
                        'option2'       => 'Option 2',
                    )                
                ),
            'ts_client_browser' => array
                (
                'label'         => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_browser'],
                'exclude'       => true,
                'inputType'     => 'text',
                'eval'          => array('style'=>'width:180px')
                ),
            )
        )
);

?>
```


Usage with callback
===================

```php
<?php

$GLOBALS['TL_DCA']['tl_table']['fields']['anything'] = array
(
    'label'         => &$GLOBALS['TL_LANG']['tl_table']['anything'],
    'exclude'       => true,
    'inputType'     => 'multiColumnWizard',
    'eval'          => array
        (
        'mandatory'             => true,
        'columnsCallback'       => array('Class', 'Method')
        )
);

?>
```


More information
================

More information can be found in the contao wiki
http://de.contaowiki.org/MultiColumnWizard