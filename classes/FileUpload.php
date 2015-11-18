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
 * Provide methods to handle file uploads in the back end.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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

        for ($i=0; $i<\Config::get('uploadFields'); $i++)
        {
            $fields .= '
            <div class="file-field input-field">
              <div class="btn">
                <span>'.$GLOBALS['TL_LANG']['tl_files']['fileupload'][0].'</span>
                <input type="file" name="' . $this->strName . '[]" onfocus="Backend.getScrollOffset()">
              </div>
              <div class="file-path-wrapper">
                <input class="file-path validate" type="text">
              </div>
            </div>';
        }

        return '
  <div id="upload-fields">'.$fields.'
  </div>
  <script>
    window.addEvent("domready", function() {
      if ("multiple" in document.createElement("input")) {
        var div = $("upload-fields");
        var input = div.getElement("input");
        div.empty();
        input.set("multiple", true);
        input.inject(div);
      }
    });
  </script>
  <p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileupload'][1], \System::getReadableSize($this->getMaximumUploadSize()), \Config::get('gdMaxImgWidth') . 'x' . \Config::get('gdMaxImgHeight')) . '</p>';
    }
}