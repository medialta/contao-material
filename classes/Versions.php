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
        <a href="#" class="toggle-button btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5 js-toggle-version toggle-version" data-position="left" data-delay="50" data-tooltip="'.$GLOBALS['TL_LANG']['MSC']['showDifferences'].'">
            <i class="material-icons">subtitles</i>
        </a>
        <div class="tl_version_panel panel js-version-panel" style="display:none">

            <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_version" class="tl_form" method="post">
                <div class="tl_formbody card-action">
                    <input type="hidden" name="FORM_SUBMIT" value="tl_version">
                    <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
                    <div class="row">
                        <div class="col m4 l3">
                            <select name="version" class="tl_select">'.$versions.'
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="showVersion" id="showVersion" class="btn waves-effect grey lighten-5 black-text"><i class="material-icons left">undo</i>'.specialchars($GLOBALS['TL_LANG']['MSC']['restore']).'</button>
                    <a href="'.$this->addToUrl('versions=1&amp;popup=1').'" class="btn waves-effect grey lighten-5 black-text" onclick="Backend.openModalIframe({\'width\':768,\'title\':\''.specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['showDifferences'])).'\',\'url\':this.href});return false"><i class="material-icons left">subtitles</i>'.specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']).'</a>
                </div>
            </form>

        </div>
        ';
    }
}