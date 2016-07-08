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
 * Provide methods to handle select menus.
 *
 * @property boolean $mandatory
 * @property integer $size
 * @property boolean $multiple
 * @property array   $options
 * @property boolean $chosen
 */
class SelectMenu extends \Contao\SelectMenu
{
        /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $arrOptions = array();
        $strClass = 'tl_select';

        if ($this->multiple)
        {
            $this->strName .= '[]';
            $strClass = 'tl_mselect';
        }

        // Add an empty option (XHTML) if there are none
        if (empty($this->arrOptions))
        {
            $this->arrOptions = array(array('value'=>'', 'label'=>'-'));
        }

        foreach ($this->arrOptions as $strKey=>$arrOption)
        {
            if (isset($arrOption['value']))
            {
                $arrOptions[] = sprintf('<option value="%s"%s>%s</option>',
                                         specialchars($arrOption['value']),
                                         $this->isSelected($arrOption),
                                         $arrOption['label']);
            }
            else
            {
                $arrOptgroups = array();

                foreach ($arrOption as $arrOptgroup)
                {
                    $arrOptgroups[] = sprintf('<option value="%s"%s>%s</option>',
                                               specialchars($arrOptgroup['value']),
                                               $this->isSelected($arrOptgroup),
                                               $arrOptgroup['label']);
                }

                $arrOptions[] = sprintf('<optgroup label="&nbsp;%s">%s</optgroup>', specialchars($strKey), implode('', $arrOptgroups));
            }
        }

        // Chosen
        if ($this->chosen)
        {
            $strClass .= ' tl_chosen';
        }

        return sprintf('%<div class="input-field"><select name="%s" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s</div>',
                        ($this->multiple ? '<input type="hidden" name="'. rtrim($this->strName, '[]') .'" value="">' : ''),
                        $this->strName,
                        $this->strId,
                        $strClass,
                        (($this->strClass != '') ? ' ' . $this->strClass : ''),
                        $this->getAttributes(),
                        implode('', $arrOptions),
                        $this->wizard);
    }
}
