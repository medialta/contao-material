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
 * Class Messages
 *
 * @author Medialta <http://www.medialta.com>
 */
class Versions extends \Contao\Versions
{
    /**
     * Render the versions dropdown menu
     *
     * @return string
     */
    public function renderDropdown()
    {
        $objVersion = $this->Database->prepare("SELECT tstamp, version, username, active FROM tl_version WHERE fromTable=? AND pid=? ORDER BY version DESC")
        ->execute($this->strTable, $this->intPid);

        if ($objVersion->numRows < 2)
        {
            return '';
        }

        $versions = '';

        while ($objVersion->next())
        {
            $versions .= '
            <option value="'.$objVersion->version.'"'.($objVersion->active ? ' selected="selected"' : '').'>'.$GLOBALS['TL_LANG']['MSC']['version'].' '.$objVersion->version.' ('.\Date::parse(\Config::get('datimFormat'), $objVersion->tstamp).') '.$objVersion->username.'</option>';
        }

        return '
        <div class="tl_version_panel">

            <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_version" class="tl_form" method="post">
                <div class="tl_formbody">
                    <input type="hidden" name="FORM_SUBMIT" value="tl_version">
                    <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
                    <select name="version" class="tl_select">'.$versions.'
                    </select>
                    <input type="submit" name="showVersion" id="showVersion" class="tl_submit" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['restore']).'">
                    <a href="'.$this->addToUrl('versions=1&amp;popup=1').'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']).'" onclick="Backend.openModalIframe({\'width\':768,\'title\':\''.specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['showDifferences'])).'\',\'url\':this.href});return false">'.\Image::getHtml('diff.gif').'</a>
                </div>
            </form>

        </div>
        ';
    }
}