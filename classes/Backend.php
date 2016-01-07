<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace ContaoMaterial;

/**
 * Provide methods to manage back end controllers.
 *
 * @property \Ajax $objAjax
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class Backend extends \Contao\Backend
{
    /**
     * Add an image to each page in the tree
     *
     * @param array          $row
     * @param string         $label
     * @param \DataContainer $dc
     * @param string         $imageAttribute
     * @param boolean        $blnReturnImage
     * @param boolean        $blnProtected
     *
     * @return string
     */
    public static function addPageIcon($row, $label, \DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false)
    {
        if ($blnProtected)
        {
            $row['protected'] = true;
        }

        $image = \Controller::getPageStatusIcon((object) $row);
        $imageAttribute = trim($imageAttribute . ' data-icon="' . \Controller::getPageStatusIcon((object) array_merge($row, array('published'=>'1'))) . '" data-icon-disabled="' . \Controller::getPageStatusIcon((object) array_merge($row, array('published'=>''))) . '"');

        // Return the image only
        if ($blnReturnImage)
        {
            return \Helper::getIconHtml($image, '', $imageAttribute);
        }

        // Mark root pages
        if ($row['type'] == 'root' || \Input::get('do') == 'article')
        {
            $label = '<strong>' . $label . '</strong>';
        }

        // Add the breadcrumb link
        $label = '<a href="' . \Controller::addToUrl('node='.$row['id']) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">' . $label . '</a>';

        // Return the image
        return '<a href="contao/main.php?do=feRedirect&amp;page='.$row['id'].'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['view']).'"' . (($dc->table != 'tl_page') ? ' class="tl_gray"' : '') . ' target="_blank">'.\Helper::getIconHtml($image, '', $imageAttribute).'</a> '.$label;
    }
}