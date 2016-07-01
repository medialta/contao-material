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
 * Provide methods to handle file uploads in the back end.
 */
class FileUpload extends \Contao\FileUpload
{
    /**
     * Generate the markup for the default uploader
     *
     * @return string
     */
    public function generateMarkup()
    {
        $fields = '';

        $fields .= '
        <div class="file-field input-field">
            <div class="btn">
                <span>'.$GLOBALS['TL_LANG']['MSC']['chooseFile'].'</span>
                <input type="file" name="' . $this->strName . '[]" onfocus="Backend.getScrollOffset()">
            </div>
            <div class="file-path-wrapper">
              <input class="file-path validate" type="text">
          </div>
        </div>';


        return '
        <div id="upload-fields">'.$fields.'
        </div>

        <p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileupload'][1], \System::getReadableSize($this->getMaximumUploadSize()), \Config::get('gdMaxImgWidth') . 'x' . \Config::get('gdMaxImgHeight')) . '</p>';
    }
}
