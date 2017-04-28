<?php

/**
 * Contao Open Source CMS
 *
 * @author Medialta <http://www.medialta.com>
 * @package ContaoMaterial
 * @copyright Medialta
 * @license LGPL-3.0+
 */

$GLOBALS['TL_DCA']['tl_user']['palettes']['login'] = str_replace('{backend_legend}', '{frontend_legend},frontend_helper,frontend_helper_position;{backend_legend}', $GLOBALS['TL_DCA']['tl_user']['palettes']['login']);

$GLOBALS['TL_DCA']['tl_user']['fields']['frontend_helper'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['frontend_helper'],
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_user']['fields']['frontend_helper_position'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['frontend_helper_position'],
    'inputType'               => 'select',
    'options'                 => ['topleft' => $GLOBALS['TL_LANG']['tl_user']['topleft'], 'topright' => $GLOBALS['TL_LANG']['tl_user']['topright'], 'bottomleft' => $GLOBALS['TL_LANG']['tl_user']['bottomleft'], 'bottomright' => $GLOBALS['TL_LANG']['tl_user']['bottomright']],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(32) NOT NULL default ''"
];