<?php

/**
 * Contao Open Source CMS
 *
 * @author Medialta <http://www.medialta.com>
 * @package ContaoMaterial
 * @copyright Medialta
 * @license LGPL-3.0+
 */

namespace ContaoMaterial;


/**
 * Provide methods to handle CHMOD tables.
 */
class CheckBox extends \Contao\CheckBox
{
    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $arrOptions = array();

        if (!$this->multiple && count($this->arrOptions) > 1)
        {
            $this->arrOptions = array($this->arrOptions[0]);
        }

        // The "required" attribute only makes sense for single checkboxes
        if ($this->mandatory && !$this->multiple)
        {
            $this->arrAttributes['required'] = 'required';
        }

        $state = $this->Session->get('checkbox_groups');

        // Toggle the checkbox group
        if (\Input::get('cbc'))
        {
            $state[\Input::get('cbc')] = (isset($state[\Input::get('cbc')]) && $state[\Input::get('cbc')] == 1) ? 0 : 1;
            $this->Session->set('checkbox_groups', $state);

            $this->redirect(preg_replace('/(&(amp;)?|\?)cbc=[^& ]*/i', '', \Environment::get('request')));
        }

        $blnFirst = true;
        $blnCheckAll = true;

        foreach ($this->arrOptions as $i=>$arrOption)
        {
            // Single dimension array
            if (is_numeric($i))
            {
                $arrOptions[] = $this->generateCheckbox($arrOption, $i);
                continue;
            }

            $id = 'cbc_' . $this->strId . '_' . standardize($i);

            $img = 'folPlus';
            $display = 'none';

            if (!isset($state[$id]) || !empty($state[$id]))
            {
                $img = 'folMinus';
                $display = 'block';
            }

            $arrOptions[] = '<div class="checkbox_toggler' . ($blnFirst ? '_first' : '') . '"><a href="' . $this->addToUrl('cbc=' . $id) . '" onclick="AjaxRequest.toggleCheckboxGroup(this,\'' . $id . '\');Backend.getScrollOffset();return false">' . \Helper::getHtml($img . '.gif') . '</a>' . $i . '</div><fieldset id="' . $id . '" class="tl_checkbox_container checkbox_options" style="display:' . $display . '"><input type="checkbox" id="check_all_' . $id . '" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this, \'' . $id . '\')"> <label for="check_all_' . $id . '" style="color:#a6a6a6"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label>';

            // Multidimensional array
            foreach ($arrOption as $k=>$v)
            {
                $arrOptions[] = $this->generateCheckbox($v, standardize($i).'_'.$k);
            }

            $arrOptions[] = '</fieldset>';
            $blnFirst = false;
            $blnCheckAll = false;
        }

        // Add a "no entries found" message if there are no options
        if (empty($arrOptions))
        {
            $arrOptions[]= '<p class="tl_noopt">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>';
            $blnCheckAll = false;
        }

        if ($this->multiple)
        {
            return sprintf('<fieldset id="ctrl_%s" class="tl_checkbox_container%s"><legend>%s%s%s%s</legend><input type="hidden" name="%s" value="">%s%s</fieldset>%s',
                            $this->strId,
                            (($this->strClass != '') ? ' ' . $this->strClass : ''),
                            ($this->mandatory ? '<span class="invisible">'.$GLOBALS['TL_LANG']['MSC']['mandatory'].' </span>' : ''),
                            $this->strLabel,
                            ($this->mandatory ? '<span class="mandatory">*</span>' : ''),
                            $this->xlabel,
                            $this->strName,
                            ($blnCheckAll ? '<input type="checkbox" id="check_all_' . $this->strId . '" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this,\'ctrl_' . $this->strId . '\')' . ($this->onclick ? ';' . $this->onclick : '') . '"> <label for="check_all_' . $this->strId . '" style="color:#a6a6a6"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label><br>' : ''),
                            str_replace('<br></fieldset><br>', '</fieldset>', implode('<br>', $arrOptions)),
                            $this->wizard);
        }
        else
        {
            return sprintf('<div id="ctrl_%s" class="tl_checkbox_single_container%s"><input type="hidden" name="%s" value="">%s</div>%s',
                            $this->strId,
                            (($this->strClass != '') ? ' ' . $this->strClass : ''),
                            $this->strName,
                            str_replace('<br></div><br>', '</div>', implode('<br>', $arrOptions)),
                            $this->wizard);
        }
    }
}
