<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace ContaoMateriel;


/**
 * Provide methods to handle text fields with unit drop down menu.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property array   $options
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InputUnit extends \Contao\InputUnit
{
    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $arrUnits = array();

        foreach ($this->arrUnits as $arrUnit)
        {
            $arrUnits[] = sprintf('<option value="%s"%s>%s</option>',
                                   specialchars($arrUnit['value']),
                                   $this->isSelected($arrUnit),
                                   $arrUnit['label']);
        }

        if (!is_array($this->varValue))
        {
            $this->varValue = array('value'=>$this->varValue);
        }
        return sprintf('<div class="row"><div class="col s10"><input type="text" name="%s[value]" id="ctrl_%s" class="%s" value="%s"%s onfocus="Backend.getScrollOffset()"></div><div class="col s2"><select name="%s[unit]" class="" onfocus="Backend.getScrollOffset()"%s>%s</select>%s</div></div>',
                        $this->strName,
                        $this->strId,
                        (strlen($this->strClass) ? ' ' . $this->strClass : ''),
                        specialchars($this->varValue['value']),
                        $this->getAttributes(),
                        $this->strName,
                        $this->getAttribute('disabled'),
                        implode('', $arrUnits),
                        $this->wizard);
    }
}