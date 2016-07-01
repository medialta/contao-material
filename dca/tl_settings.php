<?php

/**
 * Contao Open Source CMS
 *
 * @author Medialta <http://www.medialta.com>
 * @package ContaoMaterial
 * @copyright Medialta
 * @license LGPL-3.0+
 */

if (class_exists('\MetaPalettes')) {
    \MetaPalettes::appendFields('tl_settings', 'ContaoMaterial', array('cover_image'));
}


$GLOBALS['TL_DCA']['tl_settings']['fields']['cover_image'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['cover_image'],
    'inputType'               => 'fileTree',
    'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio'),
    'sql'                     => "binary(16) NULL"
);
