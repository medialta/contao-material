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
 * Provide methods to handle text fields.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property boolean $multiple
 * @property boolean $hideInput
 * @property integer $size
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
            if ($this->rgxp == 'url')
            {
                $this->varValue = \Idna::decode($this->varValue);
            }
            elseif ($this->rgxp == 'email' || $this->rgxp == 'friendly')
            {
                $this->varValue = \Idna::decodeEmail($this->varValue);
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
            $arrFields[] = sprintf('<div class="col s%s"><input type="%s" name="%s[]" id="ctrl_%s" value="%s"%s onfocus="Backend.getScrollOffset()"></div>',
                                    12/$this->size,
                                    $strType,
                                    $this->strName,
                                    $this->strId.'_'.$i,
                                    specialchars(@$this->varValue[$i]), // see #4979
                                    $this->getAttributes());
        }

        return sprintf('<div class="input-field"><div class="row" id="ctrl_%s"%s>%s</div>%s</div>',
                        $this->strId,
                        (($this->strClass != '') ? ' class="' . $this->strClass . '"' : ''),
                        implode(' ', $arrFields),
                        $this->wizard);
    }
}
