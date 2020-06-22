Contao 4
========

At Contao 4 use the MCW bundle - see https://github.com/menatwork/contao-multicolumnwizard-bundle


Usage with columnFields
=======================

```php
<?php

$GLOBALS['TL_DCA']['tl_theme']['fields']['templateSelection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_theme']['templateSelection'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'columnFields' => [
            'ts_client_os'      => [
                'label'     => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_os'],
                'exclude'   => true,
                'inputType' => 'select',
                'eval'      => [
                    'style'              => 'width:250px',
                    'includeBlankOption' => true,
                ],
                'options'   => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                ],
            ],
            'ts_client_browser' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_browser'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval'      => [ 'style' => 'width:180px' ],
            ],
        ],
    ],
    'sql'       => 'blob NULL',
];

?>
```


Usage with callback
===================

```php
<?php

$GLOBALS['TL_DCA']['tl_table']['fields']['anything'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_table']['anything'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'mandatory'       => true,
        'columnsCallback' => [ 'Class', 'Method' ],
    ],
    'sql'       => 'blob NULL',
];

?>
```


Usage with Drag and Drop
========================

```php
<?php

$GLOBALS['TL_DCA']['tl_theme']['fields']['templateSelection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_theme']['templateSelection'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        // add this line for a new button
        'dragAndDrop'  => true,
        'columnFields' => [
            'ts_client_browser' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_browser'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval'      => [ 'style' => 'width:180px' ],
            ],
        ],
    ],
    'sql'       => 'blob NULL',
];

?>
```

More information
================

More information can be found in the contao wiki
http://de.contaowiki.org/MultiColumnWizard
