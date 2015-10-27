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
 * Provide methods to handle text fields.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property boolean $multiple
 * @property boolean $hideInput
 * @property integer $size
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class TextField extends \Contao\TextField
{
    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $strType = $this->hideInput ? 'password' : 'text';

        if (!$this->multiple)
        {
            // Hide the Punycode format (see #2750)
            if ($this->rgxp == 'url' || $this->rgxp == 'email' || $this->rgxp == 'friendly')
            {
                $this->varValue = \Idna::decode($this->varValue);
            }
            return sprintf('<div class="input-field"><input type="%s" name="%s" id="ctrl_%s" class="tl_text%s" value="%s"%s onfocus="Backend.getScrollOffset()">%s</div>',
                            $strType,
                            $this->strName,
                            $this->strId,
                            (($this->strClass != '') ? ' ' . $this->strClass : ''),
                            specialchars($this->varValue),
                            $this->getAttributes(),
                            $this->wizard);
        }

        // Return if field size is missing
        if (!$this->size)
        {
            return '';
        }

        if (!is_array($this->varValue))
        {
            $this->varValue = array($this->varValue);
        }

        $arrFields = array();

        for ($i=0; $i<$this->size; $i++)
        {
            $arrFields[] = sprintf('<input type="%s" name="%s[]" id="ctrl_%s" class="tl_text_%s" value="%s"%s onfocus="Backend.getScrollOffset()">',
                                    $strType,
                                    $this->strName,
                                    $this->strId.'_'.$i,
                                    $this->size,
                                    specialchars(@$this->varValue[$i]), // see #4979
                                    $this->getAttributes());
        }

        return sprintf('<div class="input-field"><div id="ctrl_%s"%s>%s</div>%s</div>',
                        $this->strId,
                        (($this->strClass != '') ? ' class="' . $this->strClass . '"' : ''),
                        implode(' ', $arrFields),
                        $this->wizard);
    }
}
