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
class DropZone extends \Contao\DropZone
{
    /**
     * Generate the markup for the default uploader
     *
     * @return string
     */
    public function generateMarkup()
    {
        // Maximum file size in MB
        $intMaxSize = round($this->getMaximumUploadSize() / 1024 / 1024);

        // String of accepted file extensions
        $strAccepted = implode(',', array_map(function($a) { return '.' . $a; }, trimsplit(',', strtolower(\Config::get('uploadTypes')))));

        // Add the scripts
        $GLOBALS['TL_CSS'][] = 'assets/dropzone/' . $GLOBALS['TL_ASSETS']['DROPZONE'] . '/css/dropzone.min.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'assets/dropzone/' . $GLOBALS['TL_ASSETS']['DROPZONE'] . '/js/dropzone.min.js';

        // Generate the markup
        return '
        <input type="hidden" name="action" value="fileupload">
        <div class="fallback">
            <input type="file" name="' . $this->strName . '[]" multiple>
        </div>
        <div class="dz-container">
            <div class="dz-default dz-message">
                <span>' . $GLOBALS['TL_LANG']['tl_files']['dropzone'] . '</span>
            </div>
            <div class="dropzone-previews"></div>
        </div>
        <script>

            jQuery(document).ready(function($) {
              new Dropzone("#tl_files", {
                paramName: "' . $this->strName . '",
                maxFilesize: ' . $intMaxSize . ',
                acceptedFiles: "' . $strAccepted . '",
                previewsContainer: ".dropzone-previews",
                uploadMultiple: true
            }).on("drop", function() {
                $(".dz-message").css("padding", "12px 18px 0");
            });
        $(".card-action:last-child").css("display", "none");
        $(".messages").css("display", "none");
        });
        </script>
        <p class="tl_help tl_tip"><i class="tiny material-icons help-icon">info_outline</i>' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileupload'][1], \System::getReadableSize($this->getMaximumUploadSize()), \Config::get('gdMaxImgWidth') . 'x' . \Config::get('gdMaxImgHeight')) . '</p>';
    }
}
