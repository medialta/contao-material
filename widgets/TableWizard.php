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
 * Provide methods to handle table fields.
 *
 * @property integer $rows
 * @property integer $cols
 */
class TableWizard extends \Contao\TableWizard
{

    /**
     * Return a form to choose a CSV file and import it
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function importTable(\DataContainer $dc)
    {
        if (\Input::get('key') != 'table')
        {
            return '';
        }

        $this->import('BackendUser', 'User');
        $class = $this->User->uploader;

        // See #4086 and #7046
        if (!class_exists($class) || $class == 'DropZone')
        {
            $class = 'FileUpload';
        }

        /** @var \FileUpload $objUploader */
        $objUploader = new $class();

        // Import CSS
        if (\Input::post('FORM_SUBMIT') == 'tl_table_import')
        {
            $arrUploaded = $objUploader->uploadTo('system/tmp');

            if (empty($arrUploaded))
            {
                \Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }

            $this->import('Database');
            $arrTable = array();

            foreach ($arrUploaded as $strCsvFile)
            {
                $objFile = new \File($strCsvFile, true);

                if ($objFile->extension != 'csv')
                {
                    \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
                    continue;
                }

                // Get separator
                switch (\Input::post('separator'))
                {
                    case 'semicolon':
                    $strSeparator = ';';
                    break;

                    case 'tabulator':
                    $strSeparator = "\t";
                    break;

                    default:
                    $strSeparator = ',';
                    break;
                }

                $resFile = $objFile->handle;

                while(($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
                {
                    $arrTable[] = $arrRow;
                }
            }

            $objVersions = new \Versions($dc->table, \Input::get('id'));
            $objVersions->create();

            $this->Database->prepare("UPDATE " . $dc->table . " SET tableitems=? WHERE id=?")
            ->execute(serialize($arrTable), \Input::get('id'));

            \System::setCookie('BE_PAGE_OFFSET', 0, 0);
            $this->redirect(str_replace('&key=table', '', \Environment::get('request')));
        }

        // Return form
        return '
        <div id="tl_buttons" class="card-action">
            <a href="'.ampersand(str_replace('&key=table', '', \Environment::get('request'))).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b"><i class="material-icons black-text">keyboard_backspace</i></a>
        </div>
        '.\Message::generate().'
        <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_table_import" class="tl_form" method="post" enctype="multipart/form-data">
            <div class="tl_formbody_edit card-content" style="padding-top:0">
                <input type="hidden" name="FORM_SUBMIT" value="tl_table_import">
                <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

                <div class="tl_tbox">
                    <h3><label for="separator">'.$GLOBALS['TL_LANG']['MSC']['separator'][0].'</label></h3>
                    <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset()">
                        <option value="comma">'.$GLOBALS['TL_LANG']['MSC']['comma'].'</option>
                        <option value="semicolon">'.$GLOBALS['TL_LANG']['MSC']['semicolon'].'</option>
                        <option value="tabulator">'.$GLOBALS['TL_LANG']['MSC']['tabulator'].'</option>
                    </select>'.(($GLOBALS['TL_LANG']['MSC']['separator'][1] != '') ? '
                      <p class="tl_help tl_tip"><i class="tiny material-icons help-icon">info_outline</i>'.$GLOBALS['TL_LANG']['MSC']['separator'][1].'</p>' : '').'
                    <h3>'.$GLOBALS['TL_LANG']['MSC']['source'][0].'</h3>'.$objUploader->generateMarkup().(isset($GLOBALS['TL_LANG']['MSC']['source'][1]) ? '
                      <p class="tl_help tl_tip"><i class="tiny material-icons help-icon">info_outline</i>'.$GLOBALS['TL_LANG']['MSC']['source'][1].'</p>' : '').'
                </div>

            </div>

            <div class="card-action">

                <div class="submit-container">
                    <input type="submit" name="save" id="save" class="btn orange lighten-2" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['tw_import'][0]).'">
                </div>

            </div>
        </form>';
    }
}
