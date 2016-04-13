<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   Efg
 * @author    Thomas Kuhn <mail@th-kuhn.de>
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * @copyright Thomas Kuhn 2007-2014
 */


/**
 * Namespace
 */
namespace ContaoMaterial;


/**
 * Class DC_Formdata
 * modified version of DC_Table by Leo Feyer
 *
 * Provide methods to modify data stored in tables tl_formdata and tl_formdata_details.
 * @copyright  Thomas Kuhn 2007-2014
 * @author     Thomas Kuhn <mail@th-kuhn.de>
 * @package    Efg
 */
class DC_Formdata extends \DataContainer implements \listable, \editable
{

    /**
     * Name of the parent table
     * @param string
     */
    protected $ptable;

    /**
     * Names of the child tables
     * @param array
     */
    protected $ctable;

    /**
     * ID of the current record
     * @param integer
     */
    protected $id;

    /**
     * IDs of all root records
     * @param array
     */
    protected $root;

    /**
     * ID of the button container
     * @param string
     */
    protected $bid;

    /**
     * Limit (database query)
     * @param string
     */
    protected $limit;

    /**
     * First sorting field
     * @param string
     */
    protected $firstOrderBy;

    /**
     * Order by (database query)
     * @param array
     */
    protected $orderBy = array();

    /**
     * Fields of a new or duplicated record
     * @param array
     */
    protected $set = array();

    /**
     * IDs of all records that are currently displayed
     * @param array
     */
    protected $current = array();

    /**
     * Show the current table as tree
     * @param boolean
     */
    protected $treeView = false;

    /**
     * True if a new version has to be created
     * @param boolean
     */
    protected $blnCreateNewVersion = false;

    /**
     * True if one of the form fields is uploadable
     * @param boolean
     */
    protected $blnUploadable = false;

    /**
     * The current back end module
     * @param array
     */
    protected $arrModule = array();

    /**
     * Related form key, like 'fd_frm_contact'
     * @param string
     */
    protected $strFormKey;

    /**
     * Related form filter key, name of field in table tl_formdata holding form-identifier
     * @param string
     */
    protected $strFormFilterKey;

    /**
     * Related form filter value, title of related form like 'Contact Form"
     * @param string
     */
    protected $strFormFilterValue;

    /**
     * sql condition for form to filter
     * @param string
     */
    protected $sqlFormFilter;

    /**
     * Base fields in table tl_formdata
     * @param mixed
     */
    protected $arrBaseFields = null;

    /**
     * Base fields for owner restriction (member,user,..)
     * @param mixed
     */
    protected $arrOwnerFields = null;

    /**
     * Detail fields names in table tl_formdata_details
     * @param mixed
     */
    protected $arrDetailFields = null;

    /**
     * Fields available for import field mapping
     */
    protected $arrImportableFields = null;

    /**
     * Fields not available for import
     */
    protected $arrImportIgnoreFields = null;

    /**
     * Fields to ignore on export
     */
    protected $arrExportIgnoreFields = null;

    /**
     * Sql statements for detail fields
     * @param mixed
     */
    protected $arrSqlDetails;

    protected $arrMembers = null;

    protected $arrUsers = null;

    protected $arrMemberGroups = null;

    protected $arrUserGroups = null;

    // Decode UTF8 on CSV-/XLS-Export
    // This can be deactivated by configuration setting: $GLOBALS['EFG']['exportUTF8Decode'] = false
    protected $blnExportUTF8Decode = true;

    // Target charset when converting from UTF8 on CSV-/XLS-Export
    // This can be changed by configuration setting: $GLOBALS['EFG']['exportConvertToCharset'] = 'TARGET_CHARSET'
    protected $strExportConvertToCharset = 'CP1252';

    /**
     * Initialize the object
     * @param string
     * @param array
     */
    public function __construct($strTable, $arrModule=array())
    {
        parent::__construct();

        // Check the request token (see #4007)
        if (isset($_GET['act']))
        {
            if (!isset($_GET['rt']) || !\RequestToken::validate(\Input::get('rt')))
            {
                $this->Session->set('INVALID_TOKEN_URL', \Environment::get('request'));
                \Controller::redirect('contao/confirm.php');
            }
        }

        $this->intId = \Input::get('id');

        // Clear the clipboard
        if (isset($_GET['clipboard']))
        {
            $this->Session->set('CLIPBOARD', array());
            \Controller::redirect($this->getReferer());
        }

        $this->loadDataContainer('tl_form_field');
        $this->import('Formdata');

        // in Backend: Check BE User, Admin...
        if (TL_MODE == 'BE' || BE_USER_LOGGED_IN)
        {
            $this->import('BackendUser', 'User');
        }

        // in Frontend:
        if (TL_MODE == 'FE')
        {
            $this->import('FrontendUser', 'Member');
        }

        if (\Input::get('key') == 'export')
        {
            $this->strMode = 'export';
        }
        elseif (\Input::get('key') == 'exportxls')
        {
            $this->strMode = 'exportxls';
        }

        $this->blnExportUTF8Decode = true;
        $this->strExportConvertToCharset = 'CP1252';
        if (isset($GLOBALS['EFG']['exportUTF8Decode']) && $GLOBALS['EFG']['exportUTF8Decode'] == false)
        {
            $this->blnExportUTF8Decode = false;
        }
        if (isset($GLOBALS['EFG']['exportConvertToCharset']))
        {
            $this->strExportConvertToCharset = $GLOBALS['EFG']['exportConvertToCharset'];
        }

        if (isset($GLOBALS['EFG']['exportIgnoreFields']))
        {
            if (is_string($GLOBALS['EFG']['exportIgnoreFields']) && strlen($GLOBALS['EFG']['exportIgnoreFields']))
            {
                $this->arrExportIgnoreFields = trimsplit(',', $GLOBALS['EFG']['exportIgnoreFields']);
            }
        }

        // all field names of table tl_formdata
        foreach (\Database::getInstance()->listFields('tl_formdata') as $arrField)
        {
            if ($arrField['type'] != 'index')
            {
                $this->arrBaseFields[] = $arrField['name'];
            }
        }
        $this->arrBaseFields = array_unique($this->arrBaseFields);

        $this->arrOwnerFields = array('fd_member','fd_user','fd_member_group','fd_user_group');

        $this->arrMembers = $this->Formdata->arrMembers;
        $this->arrMemberGroups = $this->Formdata->arrMemberGroups;
        $this->arrUsers = $this->Formdata->arrUsers;
        $this->arrUserGroups = $this->Formdata->arrUserGroups;

        // Check whether the table is defined
        if ($strTable == '' || !isset($GLOBALS['TL_DCA'][$strTable]))
        {
            $this->log('Could not load the data container configuration for "' . $strTable . '"', __METHOD__, TL_ERROR);
            trigger_error('Could not load the data container configuration', E_USER_ERROR);
        }

        // Set IDs and redirect
        if (\Input::post('FORM_SUBMIT') == 'tl_select')
        {
            $ids = \Input::post('IDS');

            if (!is_array($ids) || empty($ids))
            {
                \Controller::reload();
            }

            $session = $this->Session->getData();
            $session['CURRENT']['IDS'] = \Input::post('IDS');
            $this->Session->setData($session);

            if (isset($_POST['edit']))
            {
                \Controller::redirect(str_replace('act=select', 'act=editAll', \Environment::get('request')));
            }
            elseif (isset($_POST['delete']))
            {
                \Controller::redirect(str_replace('act=select', 'act=deleteAll', \Environment::get('request')));
            }
            elseif (isset($_POST['override']))
            {
                \Controller::redirect(str_replace('act=select', 'act=overrideAll', \Environment::get('request')));
            }
            elseif (isset($_POST['cut']) || isset($_POST['copy']))
            {
                $arrClipboard = $this->Session->get('CLIPBOARD');

                $arrClipboard[$strTable] = array
                (
                    'id' => $ids,
                    'mode' => (isset($_POST['cut']) ? 'cutAll' : 'copyAll')
                );

                $this->Session->set('CLIPBOARD', $arrClipboard);
                \Controller::redirect($this->getReferer());
            }
        }

        $this->strTable = $strTable;
        $this->ptable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'];
        $this->ctable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'];
        $this->treeView = false;
        $this->root = null;
        $this->arrModule = $arrModule;

        // Key of a form or '' for no specific form
        $this->strFormKey = '';
        $this->strFormFilterKey = '';
        $this->strFormFilterValue = '';

        if (\Input::get('do'))
        {
            if (\Input::get('do') != 'feedback')
            {
                if (array_key_exists(\Input::get('do'), $GLOBALS['BE_MOD']['formdata']))
                {
                    $this->strFormKey = \Input::get('do');
                    $this->strFormFilterKey = 'form';
                    $this->strFormFilterValue = $this->Formdata->arrStoringForms[substr($this->strFormKey, 3)]['title'];
                    $this->sqlFormFilter = ' AND ' . $this->strFormFilterKey . '=\'' . $this->strFormFilterValue . '\' ';

                    // add sql where condition 'form'=TITLE_OF_FORM
                    if ($this->strTable == 'tl_formdata')
                    {
                        $this->procedure[] = $this->strFormFilterKey . '=?';
                        $this->values[] = $this->strFormFilterValue;
                    }
                }
            }
        }

        // Call onload_callback (e.g. to check permissions)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->$callback[0]->$callback[1]($this);
                }
                elseif (is_callable($callback))
                {
                    $callback($this);
                }

            }
        }

        // check names of detail fields
        // .. after call to onload_callback we have the form specific dca in $GLOBALS['TL_DCA'][$this->strTable]
        if (strlen($this->strFormKey))
        {
            $arrFFNames = array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields']);
        }

        // get all FormField names of forms storing formdata
        else
        {
            $objFFNames = \Database::getInstance()->prepare("SELECT DISTINCT ff.name FROM tl_form_field ff, tl_form f WHERE (ff.pid=f.id) AND ff.name != '' AND f.storeFormdata=?")
                ->execute("1");
            if ($objFFNames->numRows)
            {
                $arrFFNames = $objFFNames->fetchEach('name');
            }
        }

        if (!empty($arrFFNames))
        {
            $this->arrDetailFields = array_diff($arrFFNames, $this->arrBaseFields, array('import_source'));
        }

        // store array of sql-stmts for detail fields
        if (!empty($this->arrDetailFields))
        {
            $this->arrSqlDetails = array();
            foreach ($this->arrDetailFields as $strFName)
            {
                $this->arrSqlDetails[$strFName] = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' .$strFName. '\' AND pid=f.id) AS `' . $strFName .'`';
            }
        }

        // Store the current referer
        if (!empty($this->ctable) && !\Input::get('act') && !\Input::get('key') && !\Input::get('token') && \Environment::get('script') == 'contao/main.php' && !\Environment::get('isAjaxRequest'))
        {
            $session = $this->Session->get('referer');
            $session[TL_REFERER_ID][$this->strTable] = substr(\Environment::get('requestUri'), strlen(TL_PATH) + 1);
            $this->Session->set('referer', $session);
        }
    }


    /**
     * Return an object property
     * @param string
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey)
        {
            case 'id':
                return $this->intId;
                break;

            case 'parentTable':
                return $this->ptable;
                break;

            case 'childTable':
                return $this->ctable;
                break;

            case 'rootIds':
                return $this->root;
                break;

            case 'createNewVersion':
                return $this->blnCreateNewVersion;
                break;

            case 'strFormFilterValue':
                return $this->strFormFilterValue;
                break;

            case 'arrFieldConfig':
                return $this->arrFieldConfig;
                break;

            case 'arrBaseFields':
            case 'baseFields':
                return $this->arrBaseFields;
                break;

            case 'arrDetailFields':
            case 'detailFields':
                return $this->arrDetailFields;
                break;
        }

        return parent::__get($strKey);
    }


    /**
     * List all records of a particular table
     * @return string
     */
    public function showAll()
    {
        $return = '';
        $this->limit = '';
        $this->bid = 'tl_buttons';

        $this->reviseTable();

        // Add to clipboard
        if (\Input::get('act') == 'paste')
        {
            $arrClipboard = $this->Session->get('CLIPBOARD');

            $arrClipboard[$this->strTable] = array
            (
                'id' => \Input::get('id'),
                'childs' => \Input::get('childs'),
                'mode' => \Input::get('mode')
            );

            $this->Session->set('CLIPBOARD', $arrClipboard);
        }

        // Custom filter
        if (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']) && is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter'] as $filter)
            {
                $this->procedure[] = $filter[0];
                $this->values[] = $filter[1];
            }
        }

        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4)
        {
            $this->procedure[] = 'pid=?';
            $this->values[] = CURRENT_ID;
        }

        // Render view
        $return .= $this->panel();
        $return .= $this->listView();

        // Add another panel at the end of the page
        if (strpos($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'], 'limit') !== false && ($strLimit = $this->limitMenu(true)) != false)
        {
            $return .= '

<form action="'.ampersand(\Environment::get('request'), true).'" class="tl_form" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_filters_limit">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="tl_panel_bottom">

<div class="tl_submit_panel tl_subpanel">
<input type="image" name="btfilter" id="btfilter" src="' . TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/reload.gif" class="tl_img_submit" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '" alt="' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '">
</div>' . $strLimit . '

<div class="clear"></div>

</div>

</div>
</form>
';
        }

        // Store the current IDs
        $session = $this->Session->getData();
        $session['CURRENT']['IDS'] = $this->current;
        $this->Session->setData($session);

        return $return;
    }


    /**
     * Return all non-excluded fields of a record as HTML table
     * @return string
     */
    public function show()
    {
        if (!strlen($this->intId))
        {
            return '';
        }

        $table_alias = ($this->strTable == 'tl_formdata' ? ' f' : '');

        $sqlQuery = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias;
        $sqlWhere = " WHERE id=?";
        if ($sqlWhere != '')
        {
            $sqlQuery .= $sqlWhere;
        }

        $objRow = \Database::getInstance()->prepare($sqlQuery)
            ->limit(1)
            ->execute($this->intId);

        if ($objRow->numRows < 1)
        {
            return '';
        }

        $count = 1;
        $return = '';
        $row = $objRow->row();

        // Get all fields
        $fields = array_keys($row);
        $allowedFields = array('id', 'pid', 'sorting', 'tstamp');

        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields']))
        {
            $allowedFields = array_unique(array_merge($allowedFields, array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields'])));
        }

        // Use the field order of the DCA file
        $fields = array_intersect($allowedFields, $fields);

        // Show all allowed fields
        foreach ($fields as $i)
        {
            if (!in_array($i, $allowedFields)
                || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] == 'password'
                || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['doNotShow']
                || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['hideInput'])
            {
                continue;
            }

            // Special treatment for table tl_undo
            if ($this->strTable == 'tl_undo' && $i == 'data')
            {
                continue;
            }

            $value = deserialize($row[$i]);
            $class = (($count++ % 2) == 0) ? ' class="tl_bg"' : '';

            // Ignore empty detail fields if this is overall 'feedback'
            if (empty($this->strFormKey) && in_array($i, $this->arrDetailFields) && empty($value))
            {
                continue;
            }

            // Get the field value
            if (is_array($value))
            {
                foreach ($value as $kk=>$vv)
                {
                    if (is_array($vv))
                    {
                        $vals = array_values($vv);
                        $value[$kk] = $vals[0].' ('.$vals[1].')';
                    }
                }

                $row[$i] = implode(', ', $value);
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] == 'date')
            {
                $row[$i] = $value ? \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $value) : '-';
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] == 'time')
            {
                $row[$i] = $value ? \Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $value) : '-';
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] == 'datim'
                || in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['flag'], array(5, 6, 7, 8, 9, 10))
                || $i == 'tstamp')
            {
                $row[$i] = $value ? \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $value) : '-';
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] == 'checkbox'
                && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['multiple'])
            {
                if (in_array($i, $this->arrDetailFields))
                {
                    $row[$i] = strlen($value) ? $value : '-';
                }
                else
                {
                    $row[$i] = strlen($value) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label'][0] : '-';
                }
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] == 'textarea'
                && ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['allowHtml']
                    || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['preserveTags']))
            {
                $row[$i] = specialchars($value);
            }
            elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference']))
            {
                $row[$i] = isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]]) ? ((is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]]) : $row[$i];
            }

            if (in_array($i, $this->arrBaseFields) || in_array($i, $this->arrOwnerFields))
            {
                if ($i == 'fd_member')
                {
                    $row[$i] = $this->arrMembers[intval($value)];
                }
                elseif ($i == 'fd_user')
                {
                    $row[$i] = $this->arrUsers[intval($value)];
                }
                elseif ($i == 'fd_member_group')
                {
                    $row[$i] = $this->arrMemberGroups[intval($value)];
                }
                elseif ($i == 'fd_user_group')
                {
                    $row[$i] = $this->arrUserGroups[intval($value)];
                }
            }

            // Replace foreign keys with their values
            // .. but not if foreignKey table is formdata table
            if (strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['foreignKey']))
            {
                $chunks = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['foreignKey']);

                if (substr($chunks[0], 0, 3) == 'fd_')
                {
                    $row[$i] = $value;
                }
                else
                {
                    $objKey = \Database::getInstance()->prepare("SELECT " . $chunks[1] . " FROM " . $chunks[0] . " WHERE id=?")
                        ->limit(1)
                        ->execute($row[$i]);
                    if ($objKey->numRows)
                    {
                        $row[$i] = $objKey->$chunks[1];
                    }
                }
            }

            // Handle multiple value fields (CSV or pipe separated)
            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['multiple'])
            {
                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['csv'] : '|';
                $row[$i] = str_replace($strSep, ', ', $row[$i]);
            }


            // Check multiline value
            if (!is_bool(strpos($row[$i], "\n")))
            {
                $strVal = $row[$i];
                $strVal = preg_replace('/(<\/|<)(h\d|p|div|section|ul|ol|li|table|tbody|tr|td|th)([^>]*)(>)(\n)/si', "\\1\\2\\3\\4", $strVal);
                $strVal = nl2br($strVal, false);
                $strVal = preg_replace('/(<\/)(h\d|p|div|section|ul|ol|li|table|tbody|tr|td|th)([^>]*)(>)(\n)/si', "\\1\\2\\3\\4\n", $strVal);
                $row[$i] = $strVal;
                unset($strVal);
            }

            // Label
            if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label']))
            {
                $label = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label'];
            }
            else
            {
                $label = is_array($GLOBALS['TL_LANG']['MSC'][$i]) ? $GLOBALS['TL_LANG']['MSC'][$i][0] : $GLOBALS['TL_LANG']['MSC'][$i];
            }

            if (!strlen($label))
            {
                $label = $i;
            }

            $return .= '
  <tr>
    <td'.$class.'><span class="tl_label">'.$label.': </span></td>
    <td'.$class.'>'.$row[$i].'</td>
  </tr>';
        }

        // Return table
        return '
<div id="tl_buttons" class="card-action">' . (!\Input::get('popup') ? '
<a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">arrow_back</i></a>' : '') . '
</div>

<h2 class="sub_headline">'.sprintf($GLOBALS['TL_LANG']['MSC']['showRecord'], ($this->intId ? 'ID '.$this->intId : '')).'</h2>

<table class="tl_show">'.$return.'
</table>';
    }


    /**
     * Insert a new row into a database table
     * @param array
     */
    public function create($set=array())
    {

        if (!empty($this->strFormKey))
        {
            $set['form'] = $this->Formdata->arrStoringForms[str_replace('fd_', '', $this->strFormKey)]['title'];
            $set['date'] = time();
            $set['ip'] = \Environment::get('ip');

            if ($this->User && intval($this->User->id) > 0)
            {
                $set['fd_user'] = intval($this->User->id);
            }

        }

        // Get all default values for the new entry
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k => $v)
        {
            if (array_key_exists('default', $v))
            {
                if (!in_array($k, $this->arrBaseFields))
                {
                    continue;
                }
                $this->set[$k] = is_array($v['default']) ? serialize($v['default']) : $v['default'];
            }
        }

        // Set passed values
        if (!empty($set) && is_array($set))
        {
            $this->set = array_merge($this->set, $set);
        }

        // Empty the clipboard
        $arrClipboard = $this->Session->get('CLIPBOARD');
        $arrClipboard[$this->strTable] = array();
        $this->Session->set('CLIPBOARD', $arrClipboard);

        // Insert the record if the table is not closed and switch to edit mode
        if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'])
        {
            $this->set['tstamp'] = 0;

            $objInsertStmt = \Database::getInstance()->prepare("INSERT INTO " . $this->strTable . " %s")
                ->set($this->set)
                ->execute();

            if ($objInsertStmt->affectedRows)
            {
                $s2e = $GLOBALS['TL_DCA'][$this->strTable]['config']['switchToEdit'] ? '&s2e=1' : '';
                $insertID = $objInsertStmt->insertId;

                foreach ($this->arrDetailFields as $strDetailField)
                {
                    $strVal = '';
                    $arrDetailSet = array(
                        'pid' => $insertID,
                        'tstamp' => time(),
                        'ff_id' => $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strDetailField]['ff_id'],
                        'ff_name' => $strDetailField
                    );

                    // default value
                    if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strDetailField]['default']))
                    {
                        // default value in case of field type checkbox, select, radio
                        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strDetailField]['default']))
                        {
                            $strVal = implode(',', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strDetailField]['default']);
                        }
                        // default value in case of other fields
                        else
                        {
                            $strVal = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strDetailField]['default'];
                        }
                    }

                    $arrDetailSet['value'] = $strVal;

                    $objInsertStmt = \Database::getInstance()->prepare("INSERT INTO tl_formdata_details %s")
                        ->set($arrDetailSet)
                        ->execute();
                }

                // Save new record in the session
                $new_records = $this->Session->get('new_records');
                $new_records[$this->strTable][] = $insertID;
                $this->Session->set('new_records', $new_records);

                // Call the oncreate_callback
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_callback'] as $callback)
                    {
                        if (is_array($callback))
                        {
                            $this->import($callback[0]);
                            $this->$callback[0]->$callback[1]($this->strTable, $insertID, $this->set, $this);
                        }
                        elseif (is_callable($callback))
                        {
                            $callback($this->strTable, $insertID, $this->set, $this);
                        }
                    }
                }

                // Add a log entry
                $this->log('A new entry in table "'.$this->strTable.'" has been created (ID: '.$insertID.')', __METHOD__, TL_GENERAL);
                \Controller::redirect($this->switchToEdit($insertID).$s2e);
            }
        }

        \Controller::redirect($this->getReferer());
    }


    /**
     * Do nothing here
     */
    public function cut() {}


    /**
     * Do nothing here
     */
    public function copy() {}


    /**
     * Delete a record of the current table table and save it to tl_undo
     * @param boolean
     */
    public function delete($blnDoNotRedirect=false)
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
        {
            $this->log('Table "'.$this->strTable.'" is not deletable', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        if (!$this->intId)
        {
            \Controller::redirect($this->getReferer());
        }

        $data = array();
        $delete = array();

        // Do not save records from tl_undo itself
        if ($this->strTable == 'tl_undo')
        {
            \Database::getInstance()->prepare("DELETE FROM " . $this->strTable . " WHERE id=?")
                ->limit(1)
                ->execute($this->intId);

            \Controller::redirect($this->getReferer());
        }

        // If there is a PID field but no parent table
        if (\Database::getInstance()->fieldExists('pid', $this->strTable) && !strlen($this->ptable))
        {
            $delete[$this->strTable] = \Database::getInstance()->getChildRecords($this->intId, $this->strTable);
            array_unshift($delete[$this->strTable], $this->intId);
        }
        else
        {
            $delete[$this->strTable] = array($this->intId);
        }

        // Delete all child records if there is a child table
        if (!empty($this->ctable))
        {
            foreach ($delete[$this->strTable] as $id)
            {
                $this->deleteChilds($this->strTable, $id, $delete);
            }
        }

        $affected = 0;

        // Save each record of each table
        foreach ($delete as $table => $fields)
        {
            foreach ($fields as $k => $v)
            {
                $objSave = \Database::getInstance()->prepare("SELECT * FROM " . $table . " WHERE id=?")
                    ->limit(1)
                    ->execute($v);

                if ($objSave->numRows)
                {
                    $data[$table][$k] = $objSave->fetchAssoc();

                    // Store the active record
                    if ($table == $this->strTable && $v == $this->intId)
                    {
                        $this->objActiveRecord = $objSave;
                    }
                }

                $affected++;
            }
        }

        $this->import('BackendUser', 'User');

        $objUndoStmt = \Database::getInstance()->prepare("INSERT INTO tl_undo (pid, tstamp, fromTable, query, affectedRows, data) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute($this->User->id, time(), $this->strTable, 'DELETE FROM '.$this->strTable.' WHERE id='.$this->intId, $affected, serialize($data));

        // Delete the records
        if ($objUndoStmt->affectedRows)
        {
            // Call ondelete_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->$callback[0]->$callback[1]($this, $objUndoStmt->insertId);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($this, $objUndoStmt->insertId);
                    }

                }
            }

            // Delete the records
            foreach ($delete as $table => $fields)
            {
                foreach ($fields as $k => $v)
                {
                    \Database::getInstance()->prepare("DELETE FROM " . $table . " WHERE id=?")
                        ->limit(1)
                        ->execute($v);
                }
            }

            // Add a log entry unless we are deleting from tl_log itself
            if ($this->strTable != 'tl_log')
            {
                $this->log('DELETE FROM '.$this->strTable.' WHERE id='.$data[$this->strTable][0]['id'], __METHOD__, TL_GENERAL);
            }
        }

        if (!$blnDoNotRedirect)
        {
            \Controller::redirect($this->getReferer());
        }
    }


    /**
     * Delete all selected records
     */
    public function deleteAll()
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
        {
            $this->log('Table "'.$this->strTable.'" is not deletable', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $session = $this->Session->getData();
        $ids = $session['CURRENT']['IDS'];

        if (is_array($ids) && strlen($ids[0]))
        {
            foreach ($ids as $id)
            {
                $this->intId = $id;
                $this->delete(true);
            }
        }

        \Controller::redirect($this->getReferer());
    }


    /**
     * Recursively get all related table names and records
     * @param string
     * @param integer
     * @param array
     */
    public function deleteChilds($table, $id, &$delete)
    {
        $cctable = array();
        $ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'];

        if (!is_array($ctable))
        {
            return;
        }

        // Walk through each child table
        foreach ($ctable as $v)
        {
            $this->loadDataContainer($v);
            $cctable[$v] = $GLOBALS['TL_DCA'][$v]['config']['ctable'];

            $objDelete = \Database::getInstance()->prepare("SELECT id FROM " . $v . " WHERE pid=?")
                ->execute($id);

            if (!$GLOBALS['TL_DCA'][$v]['config']['doNotDeleteRecords'] && strlen($v) && $objDelete->numRows)
            {
                foreach ($objDelete->fetchAllAssoc() as $row)
                {
                    $delete[$v][] = $row['id'];

                    if (!empty($cctable[$v]))
                    {
                        $this->deleteChilds($v, $row['id'], $delete);
                    }
                }
            }
        }
    }


    /**
     * Restore one or more deleted records
     */
    public function undo()
    {
        $objRecords = \Database::getInstance()->prepare("SELECT * FROM " . $this->strTable . " WHERE id=?")
            ->limit(1)
            ->execute($this->intId);

        // Check whether there is a record
        if ($objRecords->numRows < 1)
        {
            \Controller::redirect($this->getReferer());
        }

        $error = false;
        $query = $objRecords->query;
        $data = deserialize($objRecords->data);

        if (!is_array($data))
        {
            \Controller::redirect($this->getReferer());
        }

        // Restore the data
        foreach ($data as $table => $fields)
        {
            foreach ($fields as $row)
            {
                $restore = array();

                foreach ($row as $k => $v)
                {
                    $restore[$k] = $v;
                }

                $objInsertStmt = \Database::getInstance()->prepare("INSERT INTO " . $table . " %s")
                    ->set($restore)
                    ->execute();

                // Do not delete record from tl_undo if there is an error
                if ($objInsertStmt->affectedRows < 1)
                {
                    $error = true;
                }
            }
        }

        // Add log entry and delete record from tl_undo if there was no error
        if (!$error)
        {
            $this->log('Undone '. $query, __METHOD__, TL_GENERAL);

            \Database::getInstance()->prepare("DELETE FROM " . $this->strTable . " WHERE id=?")
                ->limit(1)
                ->execute($this->intId);
        }

        \Controller::redirect($this->getReferer());
    }


    /**
     * Do nothing here
     */
    public function move() {}


    /**
     * Autogenerate a form to edit the current database record
     * @param integer
     * @param integer
     * @return string
     */
    public function edit($intID=null, $ajaxId=null)
    {
        $table_alias = ($this->strTable == 'tl_formdata' ? ' f' : '');

        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
        {
            $this->log('Table "' . $this->strTable . '" is not editable', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        if ($intID != '')
        {
            $this->intId = $intID;
        }

        $return = '';
        $this->values[] = $this->intId;
        $this->procedure[] = 'id=?';
        $this->blnCreateNewVersion = false;

        // Get current record
        $sqlQuery = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias;
        $sqlWhere = " WHERE id=?";
        if ($sqlWhere != '')
        {
            $sqlQuery .= $sqlWhere;
        }

        $objRow = \Database::getInstance()->prepare($sqlQuery)
            ->limit(1)
            ->execute($this->intId);

        // Redirect if there is no record with the given ID
        if ($objRow->numRows < 1)
        {
            $this->log('Could not load record "'.$this->strTable.'.id='.$this->intId.'"', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $this->objActiveRecord = $objRow;
        $this->checkForTinyMce();

        // Build an array from boxes and rows
        $this->strPalette = $this->getPalette();
        $boxes = trimsplit(';', $this->strPalette);
        $legends = array();

        if (!empty($boxes))
        {
            foreach ($boxes as $k => $v)
            {
                $eCount = 1;
                $boxes[$k] = trimsplit(',', $v);

                foreach ($boxes[$k] as $kk => $vv)
                {
                    if (preg_match('/^\[.*\]$/', $vv))
                    {
                        ++$eCount;
                        continue;
                    }

                    if (preg_match('/^\{.*\}$/', $vv))
                    {
                        $legends[$k] = substr($vv, 1, -1);
                        unset($boxes[$k][$kk]);
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['exclude'] || !is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]))
                    {
                        unset($boxes[$k][$kk]);
                    }
                }

                // Unset a box if it does not contain any fields
                if (count($boxes[$k]) < $eCount)
                {
                    unset($boxes[$k]);
                }
            }

            $class = '';
            $fs = $this->Session->get('fieldset_states');
            $blnIsFirst = true;

            // Render boxes
            foreach ($boxes as $k => $v)
            {
                $strAjax = '';
                $blnAjax = false;
                $key = '';
                $cls = '';
                $legend = '';

                if (isset($legends[$k]))
                {
                    list($key, $cls) = explode(':', $legends[$k]);
                    if (isset($fs[$this->strTable][$key]))
                    {
                        $class .= ($fs[$this->strTable][$key] ? '' : ' active');
                    }
                    else
                    {
                        $class .= ($cls == 'hide' ? '' : ' active');
                    }
                    $legend = "\n" . '<div class="collapsible-header '.$class.'" onclick="AjaxRequest.toggleFieldset(this,\'' . $key . '\',\'' . $this->strTable . '\')">' . (isset($GLOBALS['TL_LANG'][$this->strTable][$key]) ? $GLOBALS['TL_LANG'][$this->strTable][$key] : $key) . '</div><div class="collapsible-body">';
                } else {
                    $legend = "\n" . '<div class="collapsible-header active" onclick="AjaxRequest.toggleFieldset(this,\'' . $key . '\',\'' . $this->strTable . '\')"></div><div class="collapsible-body" style="display:block">';
                }

                $return .= "\n\n" . '<li' . ($key ? ' id="pal_'.$key.'"' : '') . ' class="' . ($legend ? '' : ' nolegend') . '">' . $legend;

                // Build rows of the current box
                foreach ($v as $kk => $vv)
                {
                    if ($vv == '[EOF]')
                    {
                        if ($blnAjax && \Environment::get('isAjaxRequest'))
                        {
                            return $strAjax . '<input type="hidden" name="FORM_FIELDS[]" value="'.specialchars($this->strPalette).'">';
                        }

                        $blnAjax = false;
                        $return .= "\n" . '</div>';

                        continue;
                    }

                    if (preg_match('/^\[.*\]$/', $vv))
                    {
                        $thisId = 'sub_' . substr($vv, 1, -1);
                        $blnAjax = ($ajaxId == $thisId && \Environment::get('isAjaxRequest')) ? true : false;
                        $return .= "\n" . '<div id="'.$thisId.'">';

                        continue;
                    }

                    $this->strField = $vv;
                    $this->strInputName = $vv;
                    $this->varValue = $objRow->$vv;

                    // Autofocus the first text field
                    if ($blnIsFirst && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] == 'text')
                    {
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['autofocus'] = 'autofocus';
                        $blnIsFirst = false;
                    }

                    // Call options_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback']))
                    {
                        $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback'][0];
                        $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback'][1];

                        $this->import($strClass);
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $this->$strClass->$strMethod($this);
                    }

                    // Convert CSV fields
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple']
                        && isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv']))
                    {
                        $this->varValue = trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'], $this->varValue);
                    }

                    /*
                    // Call load_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->varValue = $this->$callback[0]->$callback[1]($this->varValue, $this);
                            }
                            elseif (is_callable($callback))
                            {
                                $this->varValue = $callback($this->varValue, $this);
                            }

                        }

                        $this->objActiveRecord->{$this->strField} = $this->varValue;
                    }
                    */

                    // prepare values of special fields like radio, select and checkbox
                    $strInputType = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'];

                    // field types radio, select, multi checkbox
                    if (in_array($strInputType, array('radio', 'select', 'conditionalselect', 'countryselect'))
                        || ($strInputType == 'checkbox' && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple']) )
                    {
                        if (in_array($this->strField, $this->arrBaseFields) && in_array($this->strField, $this->arrOwnerFields))
                        {
                            if ($this->strField == 'fd_user')
                            {
                                if ($this->User && $this->User->id)
                                {
                                    $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['default'] = $this->User->id;
                                }
                            }
                        }
                        else
                        {

                            // foreignKey fields
                            if ($strInputType == 'select' && strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']))
                            {
                                // include blank Option
                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['includeBlankOption'] = true;

                                $arrKey = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                $strForeignTable = $arrKey[0];
                                $strForeignField = $arrKey[1];

                                // WHERE condition for foreignKey
                                $strForeignKeyCond = '';
                                if (strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKeyWhere']))
                                {
                                    $strForeignKeyCond = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKeyWhere'];
                                }

                                // check if foreignKey table is a formdata table
                                if (substr($strForeignTable, 0, 3) == 'fd_')
                                {
                                    $strFormKey = substr($strForeignTable, 3);
                                    $strForeignDcaKey = $strForeignTable;
                                    $strForeignTable = 'tl_formdata';

                                    // backup current dca and load dca for foreign formdata
                                    $BAK_DCA = $GLOBALS['TL_DCA'][$this->strTable];
                                    $this->loadDataContainer($strForeignDcaKey);

                                    $strForeignField = $arrKey[1];
                                    $strForeignSqlField = '(SELECT value FROM tl_formdata_details WHERE ff_name="' .$strForeignField. '" AND pid=f.id ) AS `' . $strForeignField . '`';

                                    $sqlForeignFd = "SELECT f.id," . $strForeignSqlField . " FROM tl_formdata f, tl_formdata_details fd ";
                                    $sqlForeignFd .= "WHERE (f.id=fd.pid) AND f." . $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['formFilterKey'] . "='" . $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['formFilterValue'] . "' AND fd.ff_name='" . $strForeignField . "'";

                                    if (!empty($strForeignKeyCond))
                                    {
                                        $arrForeignKeyCond = preg_split('/([\s!=><]+)/', $strForeignKeyCond, -1, PREG_SPLIT_DELIM_CAPTURE);
                                        $strForeignCondField = $arrForeignKeyCond[0];
                                        unset($arrForeignKeyCond[0]);
                                        if (in_array($strForeignCondField, $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['baseFields']))
                                        {
                                            $sqlForeignFd .= ' AND f.' . $strForeignCondField . implode('', $arrForeignKeyCond);
                                        }
                                        if (in_array($strForeignCondField, $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['detailFields']))
                                        {
                                            $sqlForeignFd .= ' AND (SELECT value FROM tl_formdata_details WHERE ff_name="' .$strForeignCondField. '" AND pid=f.id ) ' . implode('', $arrForeignKeyCond);
                                        }
                                    }

                                    $objForeignFd = \Database::getInstance()->prepare($sqlForeignFd)->execute();

                                    // reset current dca
                                    $GLOBALS['TL_DCA'][$this->strTable] = $BAK_DCA;
                                    unset($BAK_DCA);

                                    if ($objForeignFd->numRows)
                                    {
                                        $arrForeignRecords = $objForeignFd->fetchAllAssoc();
                                        if (!empty($arrForeignRecords))
                                        {
                                            foreach ($arrForeignRecords as $arrForeignRecord)
                                            {
                                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$arrForeignRecord['id']] = $arrForeignRecord[$strForeignField] .  ' [~' . $arrForeignRecord['id'] . '~]';
                                            }
                                        }
                                        unset($arrForeignRecords);
                                    }

                                    // unset dca 'foreignKey': prevents \Widget::getAttributesFromDca to read options from table instead handle as normal select
                                    unset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                    unset($objForeignFd);
                                }
                                // foreignKey table is 'normal' table
                                elseif (\Database::getInstance()->fieldExists($strForeignField, $strForeignTable))
                                {
                                    $blnAlias = \Database::getInstance()->fieldExists('alias', $strForeignTable);

                                    $sqlForeign = "SELECT id," . ($blnAlias ? "alias," : "") . $strForeignField . " FROM " . $strForeignTable . (strlen($strForeignKeyCond) ? " WHERE ".$strForeignKeyCond : '') . " ORDER BY " . $strForeignField;

                                    $objForeign = \Database::getInstance()->prepare($sqlForeign)->execute();

                                    if ($objForeign->numRows)
                                    {
                                        $arrForeignRecords = $objForeign->fetchAllAssoc();
                                        if (!empty($arrForeignRecords))
                                        {
                                            foreach ($arrForeignRecords as $arrForeignRecord)
                                            {
                                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$arrForeignRecord['id']] = $arrForeignRecord[$strForeignField] . ' [~' . (($blnAlias && strlen($arrForeignRecord['alias'])) ? $arrForeignRecord['alias'] : $arrForeignRecord['id']) . '~]';
                                            }
                                        }
                                        unset($arrForeignRecords);
                                    }

                                    // unset dca 'foreignKey': prevents \Widget::getAttributesFromDca to read options from table instead handle as normal select
                                    unset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                    unset($objForeign);
                                }
                                // sort options on label
                                asort($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);
                            }

                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $arrValues = explode($strSep, $this->varValue);
                            }
                            else
                            {
                                $arrValues = $this->varValue;
                            }

                            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['efgStoreValues'])
                            {
                                $this->varValue = $arrValues;
                            }
                            else
                            {
                                // prepare values
                                $arrNewValues = array();

                                foreach($arrValues as $kVal => $vVal)
                                {
                                    $vVal = trim($vVal);
                                    $strK = false;
                                    if (strlen($vVal) && $strK == false)
                                    {

                                        // handle grouped options
                                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] as $strOptsKey => $varOpts)
                                        {
                                            if (is_array($varOpts))
                                            {
                                                $strK = array_search($vVal, $varOpts);
                                            }
                                            else
                                            {
                                                $strK = array_search($vVal, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);
                                            }

                                            if ($strK !== false)
                                            {
                                                $arrNewValues[] = $strK;
                                                break;
                                            }
                                        }

                                        // add saved option to available options if it does not exist
                                        if ($strK === false)
                                        {
                                            $strK = preg_replace('/(.*?\[)(.*?)(\])/si', '$2', $vVal);
                                            $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$strK] = $vVal;
                                            $arrNewValues[] = $strK;
                                        }

                                    }
                                }

                                $this->varValue = $arrNewValues;
                            }
                        }
                    }

                    // field type single checkbox
                    elseif ($strInputType == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'])
                    {
                        // Modify options to handle Contao 3 new validation in Widget::isValidOption()
                        if (in_array($this->strField, $this->arrDetailFields))
                        {
                            if (!is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']))
                            {
                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = array
                                (
                                    '1' => $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0]
                                );
                            }
                            $strFirstOpt = array_pop($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);

                            $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][1] = $strFirstOpt;
                            if (!empty($this->varValue))
                            {
                                $this->varValue = '1';
                            }
                        }
                    }

                    // field type efgLookupSelect
                    elseif ($strInputType == 'efgLookupSelect')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);
                        $arrNewOptions = array();

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupSelect as SelectMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'select';

                    }

                    // field type efgLookupCheckbox
                    elseif ($strInputType == 'efgLookupCheckbox')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);
                        $arrNewOptions = array();

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupCheckbox as CheckboxMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'checkbox';

                    }

                    // field type efgLookupRadio
                    elseif ($strInputType == 'efgLookupRadio')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);
                        $arrNewOptions = array();

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupRadio as RadioMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'radio';

                    }

                    else
                    {
                        $this->varValue = $this->Formdata->prepareDatabaseValueForWidget($this->varValue, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);
                    }

                    $this->objActiveRecord->{$this->strField} = $this->varValue;

                    // Call load_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->varValue = $this->$callback[0]->$callback[1]($this->varValue, $this);
                            }
                            elseif (is_callable($callback))
                            {
                                $this->varValue = $callback($this->varValue, $this);
                            }

                        }

                        $this->objActiveRecord->{$this->strField} = $this->varValue;
                    }

                    // Build the row and pass the current palette string
                    $blnAjax ? $strAjax .= $this->row($this->strPalette) : $return .= $this->row($this->strPalette);
                }

                $class = '';
                $return .= "\n" . '</li>';
            }
        }

        $version = '';

        // Submit buttons
        $arrButtons = array();
        $arrButtons['save'] = '<button type="submit" name="save" id="save" class="btn orange lighten-2" accesskey="s">'.specialchars($GLOBALS['TL_LANG']['MSC']['save']).'</button>';

        if (!\Input::get('nb'))
        {
            $arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="btn-flat orange-text text-lighten-2" accesskey="c">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']).'</button>';
        }

        if (!\Input::get('popup') && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'])
        {
            $arrButtons['saveNcreate'] = '<button type="submit" name="saveNcreate" id="saveNcreate" class="btn-flat orange-text text-lighten-2" accesskey="n">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNcreate']).'</button>';
        }

        if (\Input::get('s2e'))
        {
            $arrButtons['saveNedit'] = '<button type="submit" name="saveNedit" id="saveNedit" class="btn-flat orange-text text-lighten-2" accesskey="e">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNedit']).'</button>';;
        }
        elseif (!\Input::get('popup') && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4 || strlen($this->ptable) || $GLOBALS['TL_DCA'][$this->strTable]['config']['switchToEdit']))
        {
            $arrButtons['saveNback'] = '<button type="submit" name="saveNback" id="saveNback" class="btn-flat orange-text text-lighten-2" accesskey="g">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNback']).'</button>';
        }

        // Call the buttons_callback (see #4691)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $arrButtons = $this->$callback[0]->$callback[1]($arrButtons, $this);
                }
                elseif (is_callable($callback))
                {
                    $arrButtons = $callback($arrButtons, $this);
                }
            }
        }


        // Add some buttons and end the form
        $return .= '


<div class="card-action">
  ' . implode(' ', $arrButtons) . '
</div>

</div>
</form>

        <script>
        $(document).ready(function() {
            Backend.focusInput("'.$this->strTable.'");
        });
        </script>';


        // TODO: find a better solution to handle toggleSubpalette ...
        $return .= $this->getSubpaletteJavascript();
        $return .= $this->getFilepickerJavascript('reloadEfgFiletree');

        // Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
        $return = $version . '
<div id="tl_buttons" class="card-action">' . (\Input::get('nb') ? '&nbsp;' : '
<a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">arrow_back</i></a>') . '
</div>

<h2 class="sub_headline">'.sprintf($GLOBALS['TL_LANG']['MSC']['editRecord'], ($this->intId ? 'ID '.$this->intId : '')).'</h2>
'.\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form" method="post" enctype="' . ($this->blnUploadable ? 'multipart/form-data' : 'application/x-www-form-urlencoded') . '"'.(!empty($this->onsubmit) ? ' onsubmit="'.implode(' ', $this->onsubmit).'"' : '').'>'.($this->noReload ? '

<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['general'].'</p>' : '').'
<ul class="collapsible dca-edit" data-collapsible="expandable">
<input type="hidden" name="FORM_SUBMIT" value="'.specialchars($this->strTable).'">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
<input type="hidden" name="FORM_FIELDS[]" value="'.specialchars($this->strPalette).'">'.$return;

        // Reload the page to prevent _POST variables from being sent twice
        if (\Input::post('FORM_SUBMIT') == $this->strTable && !$this->noReload)
        {
            $arrValues = $this->values;
            array_unshift($arrValues, time());

            // Trigger the onsubmit_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->$callback[0]->$callback[1]($this);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($this);
                    }
                }
            }

            // Set the current timestamp
            \Database::getInstance()->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE " . implode(' AND ', $this->procedure))
                ->execute($arrValues);

            // Redirect
            if (isset($_POST['saveNclose']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);
                \Controller::redirect($this->getReferer());
            }
            elseif (isset($_POST['saveNedit']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);
                $strUrl = \Backend::addToUrl($GLOBALS['TL_DCA'][$this->strTable]['list']['operations']['edit']['href']);

                $strUrl = preg_replace('/(&amp;)?s2e=[^&]*/i', '', $strUrl);
                $strUrl = preg_replace('/(&amp;)?act=[^&]*/i', '', $strUrl);

                \Controller::redirect($strUrl);
            }
            elseif (isset($_POST['saveNback']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);

                \Controller::redirect(\Environment::get('script') . '?do=' . \Input::get('do'));
            }

            elseif (isset($_POST['saveNcreate']))
            {
                \Message::reset();
                setcookie('BE_PAGE_OFFSET', 0, 0, '/');
                $strUrl = \Environment::get('script') . '?do=' . \Input::get('do');

                if (isset($_GET['table']))
                {
                    $strUrl .= '&amp;table=' . \Input::get('table');
                }

                $strUrl .= strlen($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']) ? '&amp;act=create&amp;mode=2&amp;pid=' . CURRENT_ID : '&amp;act=create';

                \Controller::redirect($strUrl . '&amp;rt=' . REQUEST_TOKEN);
            }

            \Controller::reload();
        }

        // Set the focus if there is an error
        if ($this->noReload)
        {
            $return .= '

<script>
  window.addEvent(\'domready\', function() {
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
  });
</script>';
        }

        return $return;
    }


    /**
     * Auto-generate a form to edit all records that are currently shown
     * @param integer
     * @param integer
     * @return string
     */
    public function editAll($intId=null, $ajaxId=null)
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
        {
            $this->log('Table "' . $this->strTable . '" is not editable', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $return = '';
        $this->import('BackendUser', 'User');

        // Get current IDs from session
        $session = $this->Session->getData();
        $ids = $session['CURRENT']['IDS'];

        if ($intId != '' && \Environment::get('isAjaxRequest'))
        {
            $ids = array($intId);
        }

        // Save field selection in session
        if (\Input::post('FORM_SUBMIT') == $this->strTable.'_all' && \Input::get('fields'))
        {
            $session['CURRENT'][$this->strTable] = \Input::post('all_fields');
            $this->Session->setData($session);
        }

        // Add fields
        $fields = $session['CURRENT'][$this->strTable];

        if (!empty($fields) && is_array($fields) && \Input::get('fields'))
        {
            $class = 'tl_tbox';
            $this->checkForTinyMce();

            // Walk through each record
            foreach ($ids as $id)
            {
                $this->intId = $id;
                $this->procedure = array('id=?');
                $this->values = array($this->intId);
                $this->blnCreateNewVersion = false;
                $this->strPalette = trimsplit('[;,]', $this->getPalette());

                // Add meta fields if the current user is an administrator
                if ($this->User->isAdmin)
                {
                    if (\Database::getInstance()->fieldExists('sorting', $this->strTable))
                    {
                        array_unshift($this->strPalette, 'sorting');
                    }

                    if (\Database::getInstance()->fieldExists('pid', $this->strTable))
                    {
                        array_unshift($this->strPalette, 'pid');
                    }

                    $GLOBALS['TL_DCA'][$this->strTable]['fields']['pid'] = array('label'=>&$GLOBALS['TL_LANG']['MSC']['pid'], 'inputType'=>'text', 'eval'=>array('rgxp'=>'digit'));
                    $GLOBALS['TL_DCA'][$this->strTable]['fields']['sorting'] = array('label'=>&$GLOBALS['TL_LANG']['MSC']['sorting'], 'inputType'=>'text', 'eval'=>array('rgxp'=>'digit'));
                }

                // Begin current row
                $strAjax = '';
                $blnAjax = false;
                $return .= '
<div class="'.$class.'">';

                $class = 'tl_box';
                $formFields = array();

                $arrBaseFields = array();
                $arrDetailFields = array();
                $arrSqlDetails = array();

                foreach ($fields as $strField)
                {
                    if (in_array($strField, $this->arrBaseFields))
                    {
                        $arrBaseFields[] = $strField;
                    }
                    elseif (in_array($strField, $this->arrDetailFields))
                    {
                        $arrDetailFields[] = $strField;
                        $arrSqlDetails[] = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' .$strField. '\' AND pid=f.id) AS `' . $strField .'`';
                    }
                }

                $strSqlFields = (!empty($arrBaseFields) ? implode(', ', $arrBaseFields) : '');
                $strSqlFields .= (!empty($arrSqlDetails) ? (strlen($strSqlFields) ? ', ' : '') . implode(', ', $arrSqlDetails) : '');

                // Get the field values
                $objRow = \Database::getInstance()->prepare("SELECT " . $strSqlFields . " FROM " . $this->strTable . " f WHERE id=?")
                    ->limit(1)
                    ->execute($this->intId);

                // Store the active record
                $this->objActiveRecord = $objRow;
                $blnIsFirst = true;

                foreach ($this->strPalette as $v)
                {
                    // Check whether field is excluded
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['exclude'])
                    {
                        continue;
                    }

                    if ($v == '[EOF]')
                    {
                        if ($blnAjax && \Environment::get('isAjaxRequest'))
                        {
                            return $strAjax . '<input type="hidden" name="FORM_FIELDS_'.$id.'[]" value="'.specialchars(implode(',', $formFields)).'">';
                        }

                        $blnAjax = false;
                        $return .= "\n  " . '</div>';

                        continue;
                    }

                    if (preg_match('/^\[.*\]$/', $v))
                    {
                        $thisId = 'sub_' . substr($v, 1, -1) . '_' . $id;
                        $blnAjax = ($ajaxId == $thisId && \Environment::get('isAjaxRequest')) ? true : false;
                        $return .= "\n  " . '<div id="'.$thisId.'">';

                        continue;
                    }

                    if (!in_array($v, $fields))
                    {
                        continue;
                    }

                    $this->strField = $v;
                    $this->strInputName = $v.'_'.$this->intId;
                    $formFields[] = $v.'_'.$this->intId;

                    // Set the default value and try to load the current value from DB
                    $this->varValue = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['default'] ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['default'] : '';

                    if ($objRow->$v !== false)
                    {
                        $this->varValue = $objRow->$v;
                    }

                    // Autofocus the first field
                    if ($blnIsFirst && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] == 'text')
                    {
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['autofocus'] = 'autofocus';
                        $blnIsFirst = false;
                    }

                    // Call options_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback']))
                    {
                        $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback'][0];
                        $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options_callback'][1];

                        $this->import($strClass);
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $this->$strClass->$strMethod($this);
                    }

                    // Convert CSV fields
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple']
                        && isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv']))
                    {
                        $this->varValue = trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'], $this->varValue);
                    }

                    // prepare values of special fields like radio, select and checkbox
                    $strInputType = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'];


                    // field types radio, select, multi checkbox
                    if (in_array($strInputType, array('radio', 'select', 'conditionalselect', 'countryselect'))
                        || ($strInputType == 'checkbox' && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple']) )
                    {
                        if (in_array($this->strField, $this->arrBaseFields) && in_array($this->strField, $this->arrOwnerFields))
                        {
                            if ($this->strField == 'fd_user')
                            {
                                if ($this->User && $this->User->id)
                                {
                                    $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['default'] = $this->User->id;
                                }
                            }
                        }
                        else
                        {
                            // foreignKey fields
                            if ($strInputType == 'select' && strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']))
                            {
                                // include blank Option
                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['includeBlankOption'] = true;

                                $arrKey = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                $strForeignTable = $arrKey[0];
                                $strForeignField = $arrKey[1];

                                // WHERE condition for foreignKey
                                $strForeignKeyCond = '';
                                if (strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKeyWhere']))
                                {
                                    $strForeignKeyCond = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKeyWhere'];
                                }

                                // check if foreignKey table is a formdata table
                                if (substr($strForeignTable, 0, 3) == 'fd_')
                                {
                                    $strFormKey = substr($strForeignTable, 3);
                                    $strForeignDcaKey = $strForeignTable;
                                    $strForeignTable = 'tl_formdata';

                                    // backup current dca and load dca for foreign formdata
                                    $BAK_DCA = $GLOBALS['TL_DCA'][$this->strTable];
                                    $this->loadDataContainer($strForeignDcaKey);

                                    $strForeignField = $arrKey[1];
                                    $strForeignSqlField = '(SELECT value FROM tl_formdata_details WHERE ff_name="' .$strForeignField. '" AND pid=f.id ) AS `' . $strForeignField . '`';

                                    $sqlForeignFd = "SELECT f.id," . $strForeignSqlField . " FROM tl_formdata f, tl_formdata_details fd ";
                                    $sqlForeignFd .= "WHERE (f.id=fd.pid) AND f." . $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['formFilterKey'] . "='" . $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['formFilterValue'] . "' AND fd.ff_name='" . $strForeignField . "'";

                                    if (!empty($strForeignKeyCond))
                                    {
                                        $arrForeignKeyCond = preg_split('/([\s!=><]+)/', $strForeignKeyCond, -1, PREG_SPLIT_DELIM_CAPTURE);
                                        $strForeignCondField = $arrForeignKeyCond[0];
                                        unset($arrForeignKeyCond[0]);
                                        if (in_array($strForeignCondField, $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['baseFields']))
                                        {
                                            $sqlForeignFd .= ' AND f.' . $strForeignCondField . implode('', $arrForeignKeyCond);
                                        }
                                        if (in_array($strForeignCondField, $GLOBALS['TL_DCA'][$strForeignTable]['tl_formdata']['detailFields']))
                                        {
                                            $sqlForeignFd .= ' AND (SELECT value FROM tl_formdata_details WHERE ff_name="' .$strForeignCondField. '" AND pid=f.id ) ' . implode('', $arrForeignKeyCond);
                                        }
                                    }

                                    $objForeignFd = \Database::getInstance()->prepare($sqlForeignFd)->execute();

                                    // reset current dca
                                    $GLOBALS['TL_DCA'][$this->strTable] = $BAK_DCA;
                                    unset($BAK_DCA);

                                    if ($objForeignFd->numRows)
                                    {
                                        $arrForeignRecords = $objForeignFd->fetchAllAssoc();
                                        if (!empty($arrForeignRecords))
                                        {
                                            foreach ($arrForeignRecords as $arrForeignRecord)
                                            {
                                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$arrForeignRecord['id']] = $arrForeignRecord[$strForeignField] .  ' [~' . $arrForeignRecord['id'] . '~]';
                                            }
                                        }
                                        unset($arrForeignRecords);
                                    }

                                    // unset dca 'foreignKey': prevents \Widget::getAttributesFromDca to read options from table instead handle as normal select
                                    unset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                    unset($objForeignFd);
                                }
                                // foreignKey table is 'normal' table
                                elseif (\Database::getInstance()->fieldExists($strForeignField, $strForeignTable))
                                {
                                    $blnAlias = \Database::getInstance()->fieldExists('alias', $strForeignTable);

                                    $sqlForeign = "SELECT id," . ($blnAlias ? "alias," : "") . $strForeignField . " FROM " . $strForeignTable . (strlen($strForeignKeyCond) ? " WHERE ".$strForeignKeyCond : '') . " ORDER BY " . $strForeignField;

                                    $objForeign = \Database::getInstance()->prepare($sqlForeign)->execute();

                                    if ($objForeign->numRows)
                                    {
                                        $arrForeignRecords = $objForeign->fetchAllAssoc();
                                        if (!empty($arrForeignRecords))
                                        {
                                            foreach ($arrForeignRecords as $arrForeignRecord)
                                            {
                                                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$arrForeignRecord['id']] = $arrForeignRecord[$strForeignField] . ' [~' . (($blnAlias && strlen($arrForeignRecord['alias'])) ? $arrForeignRecord['alias'] : $arrForeignRecord['id']) . '~]';
                                            }
                                        }
                                        unset($arrForeignRecords);
                                    }

                                    // unset dca 'foreignKey': prevents \Widget::getAttributesFromDca to read options from table instead handle as normal select
                                    unset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['foreignKey']);
                                    unset($objForeign);
                                }
                                // sort options on label
                                asort($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);
                            }

                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $arrValues = explode($strSep, $this->varValue);
                            }
                            else
                            {
                                $arrValues = $this->varValue;
                            }

                            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['efgStoreValues'])
                            {
                                $this->varValue = $arrValues;
                            }
                            else
                            {
                                // prepare values
                                $arrNewValues = array();

                                foreach($arrValues as $kVal => $vVal)
                                {
                                    $vVal = trim($vVal);
                                    $strK = false;
                                    if (strlen($vVal) && $strK == false)
                                    {

                                        // handle grouped options
                                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] as $strOptsKey => $varOpts)
                                        {
                                            if (is_array($varOpts))
                                            {
                                                $strK = array_search($vVal, $varOpts);
                                            }
                                            else
                                            {
                                                $strK = array_search($vVal, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);
                                            }

                                            if ($strK !== false)
                                            {
                                                $arrNewValues[] = $strK;
                                                break;
                                            }
                                        }

                                        // add saved option to available options if it does not exist
                                        if ($strK === false)
                                        {
                                            $strK = preg_replace('/(.*?\[)(.*?)(\])/si', '$2', $vVal);
                                            $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][$strK] = $vVal;
                                            $arrNewValues[] = $strK;
                                        }

                                    }
                                }

                                $this->varValue = $arrNewValues;
                            }
                        }
                    }

                    // field type single checkbox
                    elseif ($strInputType == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'])
                    {
                        // Modify options to handle Contao 3 new validation in Widget::isValidOption()
                        if (in_array($this->strField, $this->arrDetailFields))
                        {
                            $strFirstOpt = array_pop($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options']);

                            $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'][1] = $strFirstOpt;
                            if (!empty($this->varValue))
                            {
                                $this->varValue = '1';
                            }
                        }
                    }

                    // field type efgLookupSelect
                    elseif ($strInputType == 'efgLookupSelect')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            $arrNewOptions = array();
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupSelect as SelectMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'select';

                    }

                    // field type efgLookupCheckbox
                    elseif ($strInputType == 'efgLookupCheckbox')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            $arrNewOptions = array();
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupCheckbox as CheckboxMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'checkbox';

                    }

                    // field type efgLookupRadio
                    elseif ($strInputType == 'efgLookupRadio')
                    {
                        $arrFieldOptions = $this->Formdata->prepareWidgetOptions($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);

                        // prepare options array and value
                        if (is_array($arrFieldOptions))
                        {
                            // prepare options array
                            $arrNewOptions = array();
                            foreach ($arrFieldOptions as $k => $v)
                            {
                                $arrNewOptions[$v['value']] = $v['label'];
                            }
                        }

                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['options'] = $arrNewOptions;

                        // prepare varValue
                        if (!empty($this->varValue))
                        {
                            if (!is_array($this->varValue))
                            {
                                $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'] : '|';
                                $this->varValue = explode($strSep, $this->varValue);
                            }
                            foreach ($this->varValue as $k => $v)
                            {
                                $sNewVal = array_search($v, $arrNewOptions);
                                if ($sNewVal)
                                {
                                    $this->varValue[$v] = $sNewVal;
                                }
                            }
                        }

                        // render type efgLookupRadio as RadioMenu
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] = 'radio';

                    }

                    else
                    {
                        $this->varValue = $this->Formdata->prepareDatabaseValueForWidget($this->varValue, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]);
                    }

                    $this->objActiveRecord->{$this->strField} = $this->varValue;

                    // Call load_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->varValue = $this->$callback[0]->$callback[1]($this->varValue, $this);
                            }
                            elseif (is_callable($callback))
                            {
                                $this->varValue = $callback($this->varValue, $this);
                            }

                        }

                        $this->objActiveRecord->{$this->strField} = $this->varValue;
                    }

                    // Build the current row
                    $blnAjax ? $strAjax .= $this->row() : $return .= $this->row();

                }

                // Close box
                $return .= '
 <input type="hidden" name="FORM_FIELDS_'.$this->intId.'[]" value="'.specialchars(implode(',', $formFields)).'">
</div>';

                // Save record
                if (\Input::post('FORM_SUBMIT') == $this->strTable && !$this->noReload)
                {
                    // Call onsubmit_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->$callback[0]->$callback[1]($this);
                            }
                            elseif (is_callable($callback))
                            {
                                $callback($this);
                            }
                        }
                    }

                    // Set current timestamp (-> DO NOT CHANGE ORDER version - timestamp)
                    \Database::getInstance()->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE id=?")
                        ->execute(time(), $this->intId);
                }
            }

            // Submit buttons
            $arrButtons = array();
            $arrButtons['save'] = '<input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['save']).'">';
            $arrButtons['saveNclose'] = '<input type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']).'">';

            // Call the buttons_callback (see #4691)
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $arrButtons = $this->$callback[0]->$callback[1]($arrButtons, $this);
                    }
                    elseif (is_callable($callback))
                    {
                        $arrButtons = $callback($arrButtons, $this);
                    }
                }
            }


            // Add the form
            $return = '

<h2 class="sub_headline_all">'.sprintf($GLOBALS['TL_LANG']['MSC']['all_info'], $this->strTable).'</h2>

<form action="'.ampersand(\Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form" method="post" enctype="' . ($this->blnUploadable ? 'multipart/form-data' : 'application/x-www-form-urlencoded') . '">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="'.$this->strTable.'">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">'.($this->noReload ? '

<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['general'].'</p>' : '').$return.'

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  ' . implode(' ', $arrButtons) . '
</div>

</div>
</form>';

            // TODO: find a better solution to handle toggleSubpalette ...
            $return .= $this->getSubpaletteJavascript();
            $return .= $this->getFilepickerJavascript('reloadEfgFiletree');

            // Set the focus if there is an error
            if ($this->noReload)
            {
                $return .= '

<script>
  window.addEvent(\'domready\', function() {
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
  });
</script>';
            }

            // Reload the page to prevent _POST variables from being sent twice
            if (\Input::post('FORM_SUBMIT') == $this->strTable && !$this->noReload)
            {
                if (\Input::post('saveNclose'))
                {
                    \System::setCookie('BE_PAGE_OFFSET', 0, 0);
                    \Controller::redirect($this->getReferer());
                }

                \Controller::reload();
            }
        }

        // Else show a form to select the fields
        else
        {
            $options = '';
            $fields = array();

            // Add fields of the current table
            $fields = array_merge($fields, array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields']));

            // Add meta fields if the current user is an administrator
            if ($this->User->isAdmin)
            {
                if (\Database::getInstance()->fieldExists('sorting', $this->strTable) && !in_array('sorting', $fields))
                {
                    array_unshift($fields, 'sorting');
                }

                if (\Database::getInstance()->fieldExists('pid', $this->strTable) && !in_array('pid', $fields))
                {
                    array_unshift($fields, 'pid');
                }
            }

            // Show all non-excluded fields
            foreach ($fields as $field)
            {
                if ($field == 'pid' || $field == 'sorting' || (!$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['exclude'] && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['doNotShow'] && (strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType']) || is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback']))))
                {
                    $options .= '
  <input type="checkbox" name="all_fields[]" id="all_'.$field.'" class="tl_checkbox" value="'.specialchars($field).'"> <label for="all_'.$field.'" class="tl_checkbox_label">'.($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] ?: $GLOBALS['TL_LANG']['MSC'][$field][0]).'</label><br>';
                }
            }

            $blnIsError = ($_POST && empty($_POST['all_fields']));

            // Return the select menu
            $return .= '

<h2 class="sub_headline_all">'.sprintf($GLOBALS['TL_LANG']['MSC']['all_info'], $this->strTable).'</h2>

<form action="'.ampersand(\Environment::get('request'), true).'&amp;fields=1" id="'.$this->strTable.'_all" class="tl_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="'.$this->strTable.'_all">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">'.($blnIsError ? '

<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['general'].'</p>' : '').'

<div class="tl_tbox">
<fieldset class="tl_checkbox_container">
  <legend'.($blnIsError ? ' class="error"' : '').'>'.$GLOBALS['TL_LANG']['MSC']['all_fields'][0].'</legend>
  <input type="checkbox" id="check_all" class="tl_checkbox" onclick="Backend.toggleCheckboxes(this)"> <label for="check_all" style="color:#a6a6a6"><em>'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</em></label><br>'.$options.'
</fieldset>'.($blnIsError ? '
<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['all_fields'].'</p>' : (($GLOBALS['TL_CONFIG']['showHelp'] && strlen($GLOBALS['TL_LANG']['MSC']['all_fields'][1])) ? '
<p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['all_fields'][1].'</p>' : '')).'
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
<input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['continue']).'">
</div>

</div>
</form>';
        }

        // Return
        return '
<div id="tl_buttons" class="card-action">
<a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">arrow_back</i></a>
</div>'.$return;
    }


    /**
     * Save the current value
     * @param mixed
     * @throws \Exception
     */
    protected function save($varValue)
    {
        if (\Input::post('FORM_SUBMIT') != $this->strTable)
        {
            return;
        }

        $arrField = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];

        // table to write to: tl_formdata (base fields) or tl_formdata_details (detail fields)
        $strTargetTable = $this->strTable;
        $strTargetField = $this->strField;
        $blnDetailField = false;

        // if field is one of detail fields
        if (in_array($strTargetField, $this->arrDetailFields))
        {
            $strTargetTable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'][0];
            $blnDetailField = true;
        }

        // Convert date formats into timestamps
        if ($varValue != '' && in_array($arrField['eval']['rgxp'], array('date', 'time', 'datim')))
        {
            $objDate = new \Date($varValue, $GLOBALS['TL_CONFIG'][$arrField['eval']['rgxp'] . 'Format']);
            $varValue = $objDate->tstamp;
        }

        if (!in_array($this->strField, $this->arrOwnerFields) && !in_array($this->strField, $this->arrBaseFields))
        {

            // Convert checkbox, radio, select, conditionalselect to store the values instead of keys
            if (($arrField['inputType'] == 'checkbox' && $arrField['eval']['multiple'])
                || in_array($arrField['inputType'], array('radio', 'select', 'conditionalselect')))
            {

                $arrOpts = $arrField['options'];

                // OptGroups can not be saved so flatten grouped options array
                $arrNewOpts = array();

                foreach ($arrOpts as $strKey => $varOpt)
                {
                    if (is_array($varOpt) && !empty($varOpt))
                    {
                        foreach ($varOpt as $keyOpt => $valOpt)
                        {
                            $arrNewOpts[$keyOpt] = $valOpt;
                        }
                    }
                    else
                    {
                        $arrNewOpts[$strKey] = $varOpt;
                    }
                }
                $arrOpts = $arrNewOpts;
                unset($arrNewOpts);

                $arrSel = deserialize($varValue, true);
                if (is_array($arrSel) && !empty($arrSel))
                {
                    $arrSel = array_flip($arrSel);
                    // use options value or options labels
                    if ($arrField['eval']['efgStoreValues'])
                    {
                        $arrVals = array_keys(array_intersect_key($arrOpts, $arrSel));
                    }
                    else
                    {
                        $arrVals = array_values(array_intersect_key($arrOpts, $arrSel));
                    }
                }

                if (is_array($arrVals) && !$arrField['eval']['multiple'])
                {
                    $varValue = $arrVals[0];
                }
                else
                {
                    $varValue = (is_array($arrVals) && !empty($arrVals)) ? $arrVals : '';
                }
            }

            if ($arrField['inputType'] == 'checkbox' && !$arrField['eval']['multiple'])
            {
                if (is_array($arrField['options']))
                {
                    $arrVals = ($arrField['eval']['efgStoreValues'] ? array_keys($arrField['options']) : array_values($arrField['options']));
                }
                else
                {
                    $arrVals = array("1");
                }

                if (strlen($varValue))
                {
                    $varValue = $arrVals[0];
                }
                else
                {
                    $varValue = '';
                }
            }

        }

        // Convert fileTree IDs or UUIDs to file paths
        if ($arrField['inputType'] == 'fileTree')
        {
            $varValue = deserialize($varValue);

            if (is_array($varValue) && !empty($varValue))
            {
                foreach ($varValue as $key => $varFile)
                {
                    if (\Validator::isUuid($varFile) || is_numeric($varFile))
                    {
                        $objFileModel = \FilesModel::findById($varFile);

                        if ($objFileModel !== null)
                        {
                            $varValue[$key] = $objFileModel->path;
                        }
                    }
                }

                $varValue = array_values($varValue);
            }
            elseif (!empty($varValue))
            {
                if (\Validator::isUuid($varValue) || is_numeric($varValue))
                {
                    $objFileModel = \FilesModel::findById($varValue);

                    if ($objFileModel !== null)
                    {
                        $varValue = $objFileModel->path;
                    }
                }
            }

        }

        if ($arrField['inputType'] == 'cm_alternative')
        {
            if (is_array($arrField['options']) && isset($arrField['options'][$varValue]))
            {
                $varValue = $arrField['options'][$varValue];
            }
        }

        // Make sure unique fields are unique
        if (!is_array($varValue) && strlen($varValue) && $arrField['eval']['unique'])
        {
            $objUnique = \Database::getInstance()->prepare("SELECT * FROM " . $this->strTable . " WHERE " . $this->strField . "=? AND id!=?")
                ->execute($varValue, $this->intId);

            if ($objUnique->numRows)
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], (strlen($arrField['label'][0]) ? $arrField['label'][0] : $this->strField)));
            }
        }

        if (is_array($varValue))
        {
            $varValue = serialize($varValue);
        }

        // Convert arrays (see #2890)
        if ($arrField['eval']['multiple'] && isset($arrField['eval']['csv']))
        {
            $varValue = implode($arrField['eval']['csv'], deserialize($varValue, true));
        }

        // Trigger the save_callback
        if (is_array($arrField['save_callback']))
        {
            foreach ($arrField['save_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $varValue = $this->$callback[0]->$callback[1]($varValue, $this);
                }
                elseif (is_callable($callback))
                {
                    $varValue = $callback($varValue, $this);
                }
            }
        }

        // Save the value if there was no error
        if (($varValue != '' || !$arrField['eval']['doNotSaveEmpty']) && ($this->varValue !== $varValue || $arrField['eval']['alwaysSave']))
        {

            // Set the correct empty value (see #6284, #6373)
            if ($varValue === '')
            {
                $varValue = \Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strTargetField]['sql']);
            }

            $arrValues = $this->values;
            $arrProcedures = $this->procedure;

            if ($blnDetailField)
            {
                // add condition ff_name
                $arrProcedures[] = 'ff_name=?';
                $arrValues[] = $strTargetField;

                foreach($arrProcedures as $kP => $kV)
                {
                    if ($kV == 'id=?')
                    {
                        $arrProcedures[$kP] = 'pid=?';
                    }
                    elseif ($kV == 'form=?')
                    {
                        $arrProcedures[$kP] = 'ff_name=?';
                        $arrValues[$kP] = $strTargetField;
                    }
                }
            }
            array_unshift($arrValues, $varValue);

            $sqlUpd = "UPDATE " . $strTargetTable . " SET " . $strTargetField . "=? WHERE " . implode(' AND ', $arrProcedures);
            if ($blnDetailField)
            {
                // if record does not exist insert an empty record
                $objExist = \Database::getInstance()->prepare("SELECT id FROM tl_formdata_details WHERE pid=? AND ff_name=?")
                    ->execute(array($this->intId, $strTargetField));

                if ($objExist->numRows == 0)
                {
                    $arrSetInsert = array(
                        'pid' => $this->intId,
                        'tstamp' => time(),
                        'ff_id' => $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strTargetField]['ff_id'],
                        'ff_name' => $strTargetField
                    );
                    $objInsertStmt = \Database::getInstance()->prepare("INSERT INTO " . $strTargetTable . " %s")
                        ->set($arrSetInsert)
                        ->execute();
                }

                $sqlUpd = "UPDATE " . $strTargetTable . " SET value=? WHERE " . implode(' AND ', $arrProcedures);
            }

            $objUpdateStmt = \Database::getInstance()->prepare($sqlUpd)
                ->execute($arrValues);

            if ($objUpdateStmt->affectedRows)
            {
                if ($varValue != $this->varValue)
                {
                    if (!$arrField['eval']['submitOnChange'])
                    {
                        $this->blnCreateNewVersion = true;
                    }
                }

                $this->varValue = deserialize($varValue);

                if (is_object($this->objActiveRecord))
                {
                    $this->objActiveRecord->{$this->strField} = $this->varValue;
                }
            }
        }
    }


    /**
     * Return the name of the current palette
     * @return string
     */
    public function getPalette()
    {
        $palette = 'default';
        $strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes'][$palette];

        // Check whether there are selector fields
        if (!empty($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__']))
        {
            $sValues = array();
            $subpalettes = array();

            $table_alias = ($this->strTable == 'tl_formdata' ? ' f' : '');
            $sqlQuery = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias . " WHERE id=?";

            $objFields = \Database::getInstance()->prepare($sqlQuery)
                ->limit(1)
                ->execute($this->intId);

            // Get selector values from DB
            if ($objFields->numRows > 0)
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__'] as $name)
                {
                    $trigger = $objFields->$name;

                    // Overwrite the trigger
                    if (\Input::post('FORM_SUBMIT') == $this->strTable)
                    {
                        $key = (\Input::get('act') == 'editAll') ? $name.'_'.$this->intId : $name;

                        if (isset($_POST[$key]))
                        {
                            $arrField = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$name];
                            if (!isset($arrField['type']))
                            {
                                $arrField['type'] = $arrField['inputType'];
                            }
                            $trigger = $this->Formdata->preparePostValueForDatabase(\Input::post($key), $arrField);
                        }
                    }

                    if ($trigger != '')
                    {
                        if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['inputType'] == 'checkbox' || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['inputType'] == 'condition') && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['eval']['multiple'])
                        {
                            $sValues[] = $name;

                            // Look for a subpalette
                            if (strlen($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name]))
                            {
                                $subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name];
                            }
                        }
                        else
                        {
                            $sValues[] = $trigger;

                            $key = $name .'_' .array_search($trigger, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['options']);

                            // Look for a subpalette
                            if (strlen($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key]))
                            {
                                $subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key];
                            }
                        }

                    }
                }
            }

            // Build possible palette names from the selector values
            if (!count($sValues))
            {
                $names = array('default');
            }
            elseif (count($sValues) > 1)
            {
                foreach ($sValues as $k => $v)
                {
                    // Unset selectors that just trigger subpalettes (see #3738)
                    if (isset($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$v]))
                    {
                        unset($sValues[$k]);
                    }
                }

                $names = $this->combiner($sValues);
            }
            else
            {
                $names = array($sValues[0]);
            }

            // Get an existing palette
            foreach ($names as $paletteName)
            {
                if (strlen($GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName]))
                {
                    $strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName];
                    break;
                }
            }

            // Include subpalettes
            foreach ($subpalettes as $k => $v)
            {
                $strPalette = preg_replace('/\b'. preg_quote($k, '/').'\b/i', $k.',['.$k.'],'.$v.',[EOF]', $strPalette);
            }

        }

        return $strPalette;
    }


    /**
     * Delete all incomplete and unrelated records
     */
    protected function reviseTable()
    {
        $reload = false;
        $ptable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'];
        $ctable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'];

        $new_records = $this->Session->get('new_records');

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['reviseTable']) && is_array($GLOBALS['TL_HOOKS']['reviseTable']))
        {
            foreach ($GLOBALS['TL_HOOKS']['reviseTable'] as $callback)
            {
                $status = null;

                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $status = $this->$callback[0]->$callback[1]($this->strTable, $new_records[$this->strTable], $ptable, $ctable);
                }
                elseif (is_callable($callback))
                {
                    $status = $callback($this->strTable, $new_records[$this->strTable], $ptable, $ctable);
                }

                if ($status === true)
                {
                    $reload = true;
                }
            }
        }

        // Delete all new but incomplete records (tstamp=0)
        if (!empty($new_records[$this->strTable]) && is_array($new_records[$this->strTable]))
        {
            $objStmt = \Database::getInstance()->execute("DELETE FROM " . $this->strTable . " WHERE id IN(" . implode(',', array_map('intval', $new_records[$this->strTable])) . ") AND tstamp=0");

            if ($objStmt->affectedRows > 0)
            {
                $reload = true;
            }
        }

        // Delete all records of the current table that are not related to the parent table
        if ($ptable != '')
        {
            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'])
            {
                $objStmt = \Database::getInstance()->execute("DELETE FROM " . $this->strTable . " WHERE ptable='" . $ptable . "' AND NOT EXISTS (SELECT * FROM " . $ptable . " WHERE " . $this->strTable . ".pid = " . $ptable . ".id)");
            }
            else
            {
                $objStmt = \Database::getInstance()->execute("DELETE FROM " . $this->strTable . " WHERE NOT EXISTS (SELECT * FROM " . $ptable . " WHERE " . $this->strTable . ".pid = " . $ptable . ".id)");
            }

            if ($objStmt->affectedRows > 0)
            {
                $reload = true;
            }
        }

        // Delete all records of the child table that are not related to the current table
        if (!empty($ctable) && is_array($ctable))
        {
            foreach ($ctable as $v)
            {
                if ($v != '')
                {
                    // Load the DCA configuration so we can check for "dynamicPtable"
                    if (!isset($GLOBALS['loadDataContainer'][$v]))
                    {
                        $this->loadDataContainer($v);
                    }

                    if ($GLOBALS['TL_DCA'][$v]['config']['dynamicPtable'])
                    {
                        $objStmt = \Database::getInstance()->execute("DELETE FROM $v WHERE ptable='" . $this->strTable . "' AND NOT EXISTS (SELECT * FROM " . $this->strTable . " WHERE $v.pid = " . $this->strTable . ".id)");
                    }
                    else
                    {
                        $objStmt = \Database::getInstance()->execute("DELETE FROM $v WHERE NOT EXISTS (SELECT * FROM " . $this->strTable . " WHERE $v.pid = " . $this->strTable . ".id)");
                    }

                    if ($objStmt->affectedRows > 0)
                    {
                        $reload = true;
                    }
                }
            }
        }

        // Reload the page
        if ($reload)
        {
            \Controller::reload();
        }
    }


    /**
     * List all records of the current table and return them as HTML string
     * @return string
     */
    protected function listView()
    {

        $return = '';
        $table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->ptable : $this->strTable;
        $table_alias = ($table == 'tl_formdata' ? ' f' : '');
        $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
        $firstOrderBy = preg_replace('/\s+.*$/i', '', $orderBy[0]);

        if (is_array($this->orderBy) && $this->orderBy[0] != '')
        {
            $orderBy = $this->orderBy;
            $firstOrderBy = $this->firstOrderBy;
        }

        if (\Input::get('table') && $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] && \Database::getInstance()->fieldExists('pid', $this->strTable))
        {
            $this->procedure[] = 'pid=?';
            $this->values[] = \Input::get('id');
        }

        $query = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias;

        $sqlWhere = '';

        if (!empty($this->procedure))
        {
            $arrProcedure = $this->procedure;

            foreach ($arrProcedure as $kProc => $vProc)
            {
                $arrParts = preg_split('/[\s=><\!]/si', $vProc);
                $strProcField = $arrParts[0];
                if (in_array($strProcField, $this->arrDetailFields))
                {
                    $arrProcedure[$kProc] = "(SELECT value FROM tl_formdata_details WHERE ff_name='" . $strProcField . "' AND pid=f.id)=?";
                }
            }
            $sqlWhere = " WHERE " . implode(' AND ', $arrProcedure);
        }

        if ($sqlWhere != '')
        {
            $query .= $sqlWhere;
        }

        if (is_array($orderBy) && $orderBy[0] != '')
        {
            foreach ($orderBy as $o => $strVal)
            {
                $arrOrderField = explode(' ', $strVal);
                $strOrderField = $arrOrderField[0];
                unset($arrOrderField);
                if (!in_array($strOrderField, $this->arrBaseFields))
                {
                    $orderBy[$o] = "(SELECT value FROM tl_formdata_details WHERE ff_name='" . $strOrderField . "' AND pid=f.id)";
                }
            }

            $query .= " ORDER BY " . implode(', ', $orderBy);
        }

        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 1 && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] % 2) == 0)
        {
            $query .= " DESC";
        }

        $objRowStmt = \Database::getInstance()->prepare($query);

        if ($this->limit != '')
        {
            $arrLimit = explode(',', $this->limit);
            $objRowStmt->limit($arrLimit[1], $arrLimit[0]);
        }

        $objRow = $objRowStmt->execute($this->values);
        $this->bid = ($return != '') ? $this->bid : 'tl_buttons';

        // Display buttons
        if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] || !empty($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations']))
        {
            $return .= '

<div id="'.$this->bid.'" class="card-action">'.((\Input::get('act') == 'select' || $this->ptable) ? '
<a href="'.$this->getReferer(true, $this->ptable).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']) ? '
<a href="contao/main.php?'.$GLOBALS['TL_DCA'][$this->strTable]['config']['backlink'].'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : '')) . ((\Input::get('act') != 'select') ? '
'.(!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ? '<a href="'.(($this->ptable != '') ? \Backend::addToUrl('act=create' . (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] < 4) ? '&amp;mode=2' : '') . '&amp;pid=' . $this->intId) : \Backend::addToUrl('act=create')).'" class="header-new btn-floating btn-large waves-effect waves-light green tooltipped"  data-position="left" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()"><i class="material-icons">add</i></a> ' : '') . $this->generateGlobalButtons() : '') . '
</div>' . \Message::generate(true);
        }

        $return .= '<div class="card-content">';

        // Return "no records found" message
        if ($objRow->numRows < 1)
        {
            $return .= \Message::parseMessage(\Message::getCssClass('tl_info'), $GLOBALS['TL_LANG']['MSC']['noResult']);
        }

        // List records
        else
        {
            $result = $objRow->fetchAllAssoc();
            $return .= ((\Input::get('act') == 'select') ? '

<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').'

<div class="listing-container list_view">'.((\Input::get('act') == 'select') ? '

<div class="tl_select_trigger">
<label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">
</div>' : '').'

<table class="listing' . ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ? ' showColumns' : '') . ' bordered highlight responsive-table">';

            // Automatically add the "order by" field as last column if we do not have group headers
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] && !in_array($firstOrderBy, $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields']))
            {
                $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][] = $firstOrderBy;
            }

            // Rename each pid to its label and resort the result (sort by parent table)
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 3)
            {
                $firstOrderBy = 'pid';
                $showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];

                foreach ($result as $k=>$v)
                {
                    $objField = \Database::getInstance()->prepare("SELECT " . $showFields[0] . " FROM " . $this->ptable . " WHERE id=?")
                        ->limit(1)
                        ->execute($v['pid']);

                    $result[$k]['pid'] = $objField->$showFields[0];
                }

                $aux = array();

                foreach ($result as $row)
                {
                    $aux[] = $row['pid'];
                }

                array_multisort($aux, SORT_ASC, $result);
            }

            // Generate the table header if the "show columns" option is active
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'])
            {
                $return .= '
  <tr>';

                foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] as $f)
                {
                    $return .= '
    <th class="row-headline col_' . $f . (($f == $firstOrderBy) ? ' ordered_by' : '') . '">'.$GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'][0].'</th>';
                }

                $return .= '
    <th class="row-headline actions">&nbsp;</th>
  </tr>';
            }

            // Process result and add label and buttons
            $remoteCur = false;
            $groupclass = 'row-headline';
            $eoCount = -1;

            foreach ($result as $row)
            {
                $rowFormatted = array();
                $args = array();
                $this->current[] = $row['id'];
                $showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];

                // Label
                foreach ($showFields as $k => $v)
                {
                    if (in_array($v, $this->arrDetailFields)
                        && in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'], array('radio', 'efgLookupRadio', 'select', 'efgLookupSelect', 'checkbox', 'efgLookupCheckbox', 'efgImageSelect', 'fileTree')))
                    {
                        $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'] : '|';
                        $row[$v] = str_replace($strSep, ', ', $row[$v]);
                    }

                    if (in_array($v, $this->arrDetailFields) && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['multiple'])
                    {
                        $strSep = isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'] : '|';
                        $row[$v] = str_replace($strSep, ', ', $row[$v]);
                    }

                    if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
                    {
                        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['rgxp'] == 'date')
                        {
                            $args[$k] = strlen($row[$v]) ? \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $row[$v]) : '-';
                            $rowFormatted[$v] = $args[$k];
                        }
                        elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['rgxp'] == 'time')
                        {
                            $args[$k] = strlen($row[$v]) ? \Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $row[$v]) : '-';
                            $rowFormatted[$v] = $args[$k];
                        }
                        else
                        {
                            $args[$k] = strlen($row[$v]) ? \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row[$v]) : '-';
                            $rowFormatted[$v] = $args[$k];
                        }
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'] == 'checkbox'
                        && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['multiple'])
                    {
                        if (in_array($v, $this->arrDetailFields))
                        {
                            $args[$k] = strlen($row[$v]) ? $row[$v] : '-';
                            $rowFormatted[$v] = $args[$k];
                        }
                        else
                        {
                            $args[$k] = strlen($row[$v]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : '-';
                            $rowFormatted[$v] = $args[$k];
                        }
                    }
                    elseif (in_array($v, $this->arrBaseFields) && in_array($v, $this->arrOwnerFields))
                    {
                        if ($v == 'fd_member')
                        {
                            $args[$k] = $this->arrMembers[$row[$v]];
                            $rowFormatted[$v] = $args[$k];
                        }
                        elseif ($v == 'fd_user')
                        {
                            $args[$k] = $this->arrUsers[$row[$v]];
                            $rowFormatted[$v] = $args[$k];
                        }
                        elseif ($v == 'fd_member_group')
                        {
                            $args[$k] = $this->arrMemberGroups[$row[$v]];
                            $rowFormatted[$v] = $args[$k];
                        }
                        elseif ($v == 'fd_user_group')
                        {
                            $args[$k] = $this->arrUserGroups[$row[$v]];
                            $rowFormatted[$v] = $args[$k];
                        }
                    }
                    else
                    {
                        $row_v = deserialize($row[$v]);

                        if (is_array($row_v))
                        {
                            $args_k = array();

                            foreach ($row_v as $option)
                            {
                                $args_k[] = strlen($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$option]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$option] : $option;
                            }

                            $args[$k] = implode(', ', $args_k);
                            $rowFormatted[$v] = $args[$k];
                        }
                        elseif (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]))
                        {
                            $args[$k] = is_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]][0] : $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]];
                        }
                        elseif (($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'])) && isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]]))
                        {
                            $args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]];
                        }
                        else
                        {
                            // check multiline value
                            if (!is_bool(strpos($row[$v], "\n")))
                            {
                                $row[$v] = $this->Formdata->formatMultilineValue($row[$v]);
                            }
                            $args[$k] = $row[$v];
                            $rowFormatted[$v] = $args[$k];
                        }
                    }
                }

                // Shorten the label it if it is too long
                $label = vsprintf((strlen($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['format']) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['format'] : '%s'), $args);

                if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'] < strlen(strip_tags($label)))
                {
                    $label = trim(\String::substrHtml($label, $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'])) . ' …';
                }

                // Build the sorting groups
                if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] > 0)
                {
                    $current = $row[$firstOrderBy];
                    $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
                    $sortingMode = (count($orderBy) == 1 && $firstOrderBy == $orderBy[0] && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] != '' && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] == '') ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'];
                    $remoteNew = $this->formatCurrentValue($firstOrderBy, $current, $sortingMode);

                    // Add the group header
                    if (!$GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] && !$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['disableGrouping'] && ($remoteNew != $remoteCur || $remoteCur === false))
                    {
                        $eoCount = -1;
                        $group = $this->formatGroupHeader($firstOrderBy, $remoteNew, $sortingMode, $row);
                        $remoteCur = $remoteNew;

                        $return .= '
  <tr>
    <td colspan="2" class="'.$groupclass.'">'.$group.'</td>
  </tr>';
                        $groupclass = 'row-headline';
                    }
                }

                $return .= '
  <tr class="'.((++$eoCount % 2 == 0) ? 'even' : 'odd').' click2edit toggle-select">
    ';

                $colspan = 1;

                // Call the label_callback ($row, $label, $this)
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback']) || is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback']))
                {
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback']))
                    {
                        $strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback'][0];
                        $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback'][1];

                        $this->import($strClass);
                        $args = $this->$strClass->$strMethod($row, $label, $this, $args);
                    }
                    elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback']))
                    {
                        $args = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['label_callback']($row, $label, $this, $args);
                    }

                    // Handle strings and arrays (backwards compatibility)
                    if (!$GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'])
                    {
                        $label = is_array($args) ? implode(' ', $args) : $args;
                    }
                    elseif (!is_array($args))
                    {
                        $args = array($args);
                        $colspan = count($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields']);
                    }
                }

                // Show columns
                if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'])
                {
                    foreach ($args as $j => $arg)
                    {
                        $return .= '<td colspan="' . $colspan . '" class="item col_' . $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][$j] . (($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][$j] == $firstOrderBy) ? ' ordered_by' : '') . '">' . ($arg ?: '-') . '</td>';
                    }
                }
                else
                {
                    $return .= '<td class="tl_file_list">' . $label . '</td>';
                }

                // Buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
                $return .= ((\Input::get('act') == 'select') ? '
    <td class="item actions -select"><input type="checkbox" name="IDS[]" id="ids_'.$row['id'].'" class="tl_tree_checkbox" value="'.$row['id'].'"></td>' : '
    <td class="item actions">'.$this->generateButtons($row, $this->strTable, $this->root).'</td>') . '
  </tr>';
            }

            // Close the table
            $return .= $_buttons . ''.$tree.'
        </table>

        </div>';

            // Close the form
            if (\Input::get('act') == 'select')
            {
                $callbacks = '';

                // Call the buttons_callback
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
                    {
                        $this->import($callback[0]);
                        $callbacks .= $this->$callback[0]->$callback[1]($this);
                    }
                }

                $return .= '

                <div class="card-action">

                    <div class="submit-container">' . (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ? '
                        <input type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'"> ' : '') . (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ? '
                        <input type="submit" name="edit" id="edit" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'"> ' : '') . '
                    </div>

                </div>
            </div>
        </form>';
            }
        }
        $return .= '</div>';
        return $return;
    }


    /**
     * Build the sort panel and return it as string
     * @return string
     */
    protected function panel()
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] == '')
        {
            return '';
        }

        $intFilterPanel = 0;
        $arrPanels = array();

        foreach (trimsplit(';', $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout']) as $strPanel)
        {
            $panels = '';
            $arrSubPanels = trimsplit(',', $strPanel);

            foreach ($arrSubPanels as $strSubPanel)
            {
                $panel = '';

                // Regular panels
                if ($strSubPanel == 'search' || $strSubPanel == 'limit' || $strSubPanel == 'sort')
                {
                    $panel = $this->{$strSubPanel . 'Menu'}();
                }

                // Multiple filter subpanels can be defined to split the fields across panels
                elseif ($strSubPanel == 'filter')
                {
                    $panel = $this->{$strSubPanel . 'Menu'}(++$intFilterPanel);
                }

                // Call the panel_callback
                else
                {
                    $arrCallback = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panel_callback'][$strSubPanel];

                    if (is_array($arrCallback))
                    {
                        $this->import($arrCallback[0]);
                        $panel = $this->$arrCallback[0]->$arrCallback[1]($this);
                    }
                    elseif (is_callable($arrCallback))
                    {
                        $panel = $arrCallback($this);
                    }
                }

                // Add the panel if it is not empty
                if ($panel != '')
                {
                    $panels = $panel . $panels;
                }
            }

            // Add the group if it is not empty
            if ($panels != '')
            {
                $arrPanels[] = $panels;
            }
        }

        if (empty($arrPanels))
        {
            return '';
        }

        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            \Controller::reload();
        }

        $return = '';
        $intTotal = count($arrPanels);
        $intLast = $intTotal - 1;

        for ($i=0; $i<$intTotal; $i++)
        {
            $submit = '';

            if ($i == $intLast)
            {
                $submit = '

                <div class="submit-panel subpanel card-action js-subpanel" id="submit-subpanel">
                <button type="submit" class="btn waves-effect grey lighten-5 black-text" data-position="top" data-delay="50" data-tooltip="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '"><i class="material-icons left">refresh</i> ' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '</button>
                </div>';
            }

            $return .= '
            <div class="panel">' . $arrPanels[$i] . $submit . '

            </div>';
        }

        $return = '
<form action="'.ampersand(\Environment::get('request'), true).'" class="tl_form" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_filters">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
' . $return . '
</ul>
</form>
';

        return $return;
    }


    /**
     * Return a search form that allows to search results using regular expressions
     * @return string
     */
    protected function searchMenu()
    {
        $searchFields = array();
        $session = $this->Session->getData();

        // Get search fields
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
        {
            if ($v['search'])
            {
                $searchFields[] = $k;
            }
        }

        // Return if there are no search fields
        if (empty($searchFields))
        {
            return '';
        }

        $strSessionKey = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : (strlen($this->strFormKey)) ? $this->strFormKey : $this->strTable;

        // Store search value in the current session
        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            $session['search'][$strSessionKey]['value'] = '';
            $session['search'][$strSessionKey]['field'] = \Input::post('tl_field', true);

            // Make sure the regular expression is valid
            if (\Input::postRaw('tl_value') != '')
            {
                $sqlSearchField = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' . \Input::post('tl_field', true) .'\' AND pid=f.id)';
                try
                {
                    \Database::getInstance()->prepare("SELECT * ".(!empty($this->arrSqlDetails) ? ','.implode(', ', array_values($this->arrSqlDetails)) : '')." FROM " . $this->strTable . " f WHERE " . $sqlSearchField . " REGEXP ?")
                        ->limit(1)
                        ->execute(\Input::postRaw('tl_value'));

                    $session['search'][$strSessionKey]['value'] = \Input::postRaw('tl_value');
                }
                catch (\Exception $e) {}
            }

            $this->Session->setData($session);
        }

        // Set search value from session
        elseif ($session['search'][$strSessionKey]['value'] != '')
        {
            $sqlSearchField = $session['search'][$strSessionKey]['field'];
            if (in_array($sqlSearchField, $this->arrDetailFields))
            {
                $sqlSearchField = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' . $session['search'][$strSessionKey]['field'] .'\' AND pid=f.id)';
            }

            if (substr($GLOBALS['TL_CONFIG']['dbCollation'], -3) == '_ci')
            {
                $this->procedure[] = "LOWER(CAST(".$sqlSearchField." AS CHAR)) REGEXP LOWER(?)";
            }
            else
            {
                $this->procedure[] = "CAST(".$sqlSearchField." AS CHAR) REGEXP ?";
            }

            $this->values[] = $session['search'][$strSessionKey]['value'];
        }

        $options_sorter = array();

        foreach ($searchFields as $field)
        {
            $option_label = strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : $GLOBALS['TL_LANG']['MSC'][$field];
            $options_sorter[utf8_romanize($option_label).'_'.$field] = '  <option value="'.specialchars($field).'"'.(($field == $session['search'][$strSessionKey]['field']) ? ' selected="selected"' : '').'>'.$option_label.'</option>';
        }

        // Sort by option values
        $options_sorter = natcaseksort($options_sorter);
        $active = ($session['search'][$strSessionKey]['value'] != '') ? true : false;

        return '

        <div class="search-panel subpanel card-action row js-subpanel" id="search-subpanel" data-search="'.$session['search'][$this->strTable]['value'].'">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['search'] . ':</strong></div>
        <div class="col m4 l3">
        <select name="tl_field" class="tl_select' . ($active ? ' active' : '') . '">
        '.implode("\n", $options_sorter).'
        </select>
        </div>
        <div class="col"> = </div>
        <div class="col m4 l3">
        <input type="text" name="tl_value" class="tl_text' . ($active ? ' active' : '') . '" value="'.specialchars($session['search'][$strSessionKey]['value']).'">
        </div>
        </div>';
    }


    /**
     * Return a select menu that allows to sort results by a particular field
     * @return string
     */
    protected function sortMenu()
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] != 2 && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] != 4)
        {
            return '';
        }

        $sortingFields = array();

        // Get sorting fields
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k => $v)
        {
            if ($v['sorting'])
            {
                $sortingFields[] = $k;
            }
        }

        // Return if there are no sorting fields
        if (empty($sortingFields))
        {
            return '';
        }

        $this->bid = 'tl_buttons_a';
        $session = $this->Session->getData();
        $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
        $firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

        $strSessionKey = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : (strlen($this->strFormKey)) ? $this->strFormKey : $this->strTable;

        // Add PID to order fields
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 3 && \Database::getInstance()->fieldExists('pid', $this->strTable))
        {
            array_unshift($orderBy, 'pid');
        }

        // Set sorting from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            $session['sorting'][$strSessionKey] = in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][\Input::post('tl_sort')]['flag'], array(2, 4, 6, 8, 10, 12)) ? \Input::post('tl_sort').' DESC' : \Input::post('tl_sort');
            $this->Session->setData($session);
        }

        // Overwrite the "orderBy" value with the session value
        elseif (strlen($session['sorting'][$strSessionKey]))
        {
            $overwrite = preg_quote(preg_replace('/\s+.*$/', '', $session['sorting'][$strSessionKey]), '/');
            $orderBy = array_diff($orderBy, preg_grep('/^'.$overwrite.'/i', $orderBy));

            array_unshift($orderBy, $session['sorting'][$strSessionKey]);

            $this->firstOrderBy = $overwrite;
            $this->orderBy = $orderBy;
        }

        $options_sorter = array();

        // Sorting fields
        foreach ($sortingFields as $field)
        {
            $options_label = strlen(($lbl = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'])) ? $lbl : $GLOBALS['TL_LANG']['MSC'][$field];

            if (is_array($options_label))
            {
                $options_label = $options_label[0];
            }

            $options_sorter[$options_label] = '  <option value="'.specialchars($field).'"'.((!strlen($session['sorting'][$strSessionKey]) && $field == $firstOrderBy || $field == str_replace(' DESC', '', $session['sorting'][$strSessionKey])) ? ' selected="selected"' : '').'>'.$options_label.'</option>';
        }

        // Sort by option values
        uksort($options_sorter, 'strcasecmp');

        return '
        <div class="sorting-panel subpanel card-action row js-subpanel" id="sorting-subpanel" data-sort="'.($datasort ?'true':'').'">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['sortBy'] . ':</strong></div>
        <div class="col m4 l3">
        <select name="tl_sort" id="tl_sort" class="tl_select">
        '.implode("\n", $options_sorter).'
        </select>
        </div>
        </div>';
    }


    /**
     * Return a select menu to limit results
     * @param boolean
     * @return string
     */
    protected function limitMenu($blnOptional=false)
    {
        $session = $this->Session->getData();
        $filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : (strlen($this->strFormKey)) ? $this->strFormKey : $this->strTable;
        $fields = '';

        if (is_array($this->procedure))
        {
            $this->procedure = array_unique($this->procedure);
        }
        if (is_array($this->values))
        {
            $this->values = array_unique($this->values);
        }

        // Set limit from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters' || \Input::post('FORM_SUBMIT') == 'tl_filters_limit')
        {
            if (\Input::post('tl_limit') != 'tl_limit')
            {
                $session['filter'][$filter]['limit'] = \Input::post('tl_limit');
            }
            else
            {
                unset($session['filter'][$filter]['limit']);
            }

            $this->Session->setData($session);

            if (\Input::post('FORM_SUBMIT') == 'tl_filters_limit')
            {
                \Controller::reload();
            }
        }

        // Set limit from table configuration
        else
        {
            $this->limit = strlen($session['filter'][$filter]['limit']) ? (($session['filter'][$filter]['limit'] == 'all') ? null : $session['filter'][$filter]['limit']) : '0,' . $GLOBALS['TL_CONFIG']['resultsPerPage'];

            $sqlQuery = '';
            $sqlSelect = '';
            $sqlDetailFields = '';
            $sqlWhere = '';

            if (!empty($this->procedure))
            {
                $arrProcedure = $this->procedure;
                foreach ($arrProcedure as $kProc => $vProc)
                {
                    $arrParts = preg_split('/[\s=><\!]/si', $vProc);
                    $strProcField = $arrParts[0];
                    if (in_array($strProcField, $this->arrDetailFields))
                    {
                        $arrProcedure[$kProc] = "(SELECT value FROM tl_formdata_details WHERE ff_name='" . $strProcField . "' AND pid=f.id)=?";
                    }

                }
                $sqlWhere = " WHERE " . implode(' AND ', $arrProcedure);
            }
            $sqlSelect = "SELECT COUNT(*) AS count FROM " . $this->strTable . " f";
            $sqlQuery = $sqlSelect . $sqlWhere;

            $objTotal = \Database::getInstance()->prepare($sqlQuery)
                ->execute($this->values);
            $total = $objTotal->count;
            $options_total = 0;
            $blnIsMaxResultsPerPage = false;

            // Overall limit
            if ($total > $GLOBALS['TL_CONFIG']['maxResultsPerPage'] && ($this->limit === null || preg_replace('/^.*,/', '', $this->limit) == $GLOBALS['TL_CONFIG']['maxResultsPerPage']))
            {
                if ($this->limit === null)
                {
                    $this->limit = '0,' . $GLOBALS['TL_CONFIG']['maxResultsPerPage'];
                }

                $blnIsMaxResultsPerPage = true;
                $GLOBALS['TL_CONFIG']['resultsPerPage'] = $GLOBALS['TL_CONFIG']['maxResultsPerPage'];
                $session['filter'][$filter]['limit'] = $GLOBALS['TL_CONFIG']['maxResultsPerPage'];
            }

            $options = '';

            // Build options
            if ($total > 0)
            {
                $options = '';
                $options_total = ceil($total / $GLOBALS['TL_CONFIG']['resultsPerPage']);

                // Reset limit if other parameters have decreased the number of results
                if ($this->limit !== null && ($this->limit == '' || preg_replace('/,.*$/', '', $this->limit) > $total))
                {
                    $this->limit = '0,'.$GLOBALS['TL_CONFIG']['resultsPerPage'];
                }

                // Build options
                for ($i=0; $i<$options_total; $i++)
                {
                    $this_limit = ($i*$GLOBALS['TL_CONFIG']['resultsPerPage']).','.$GLOBALS['TL_CONFIG']['resultsPerPage'];
                    $upper_limit = ($i*$GLOBALS['TL_CONFIG']['resultsPerPage']+$GLOBALS['TL_CONFIG']['resultsPerPage']);

                    if ($upper_limit > $total)
                    {
                        $upper_limit = $total;
                    }

                    $options .= '
  <option value="'.$this_limit.'"' . \Widget::optionSelected($this->limit, $this_limit) . '>'.($i*$GLOBALS['TL_CONFIG']['resultsPerPage']+1).' - '.$upper_limit.'</option>';
                }

                if (!$blnIsMaxResultsPerPage)
                {
                    $options .= '
  <option value="all"' . \Widget::optionSelected($this->limit, null) . '>'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</option>';
                }
            }

            // Return if there is only one page
            if ($blnOptional && ($total < 1 || $options_total < 2))
            {
                return '';
            }

            $fields = '
            <div class="col m4 l3">
            <select name="tl_limit" class="' . (($session['filter'][$filter]['limit'] != 'all' && $this->total > \Config::get('resultsPerPage')) ? ' active' : '') . '" onchange="this.form.submit()">
            <option value="tl_limit">'.$GLOBALS['TL_LANG']['MSC']['filterRecords'].'</option>'.$options.'
            </select>
            </div> ';
        }

        return '

        <div class="limit-panel subpanel card-action row js-subpanel" id="limit-subpanel" data-limit="">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['showOnly'] . ':</strong></div> '.$fields.'
        </div>';
    }


    /**
     * Generate the filter panel and return it as HTML string
     * @return string
     */
    protected function filterMenu($intFilterPanel)
    {
        $fields = '';
        $this->bid = 'tl_buttons_a';
        $sortingFields = array();
        $session = $this->Session->getData();
        $filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : (strlen($this->strFormKey)) ? $this->strFormKey : $this->strTable;

        // Get the sorting fields
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k => $v)
        {
            if (intval($v['filter']) == $intFilterPanel)
            {
                $sortingFields[] = $k;
            }
        }

        // Return if there are no sorting fields
        if (empty($sortingFields))
        {
            return '';
        }

        // Set filter from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            foreach ($sortingFields as $field)
            {
                if (\Input::post($field, true) != 'tl_'.$field)
                {
                    $session['filter'][$filter][$field] = \Input::post($field, true);
                }
                else
                {
                    unset($session['filter'][$filter][$field]);
                }
            }

            // add filter if called by special form dependent BE nav item
            if ($this->strFormFilterKey != '' && $this->strFormFilterValue != '')
            {
                $session['filter'][$filter][$this->strFormFilterKey] = $this->strFormFilterValue;
            }

            $this->Session->setData($session);
        }

        // Set filter from table configuration
        else
        {
            foreach ($sortingFields as $field)
            {
                if (isset($session['filter'][$filter][$field]))
                {
                    $datafilter = true;
                    $strProcField = $field;

                    if (in_array($field, $this->arrDetailFields))
                    {
                        $strProcField = "(SELECT `value` FROM tl_formdata_details WHERE ff_name='" . $field . "' AND pid=f.id)";
                    }

                    // Sort by day
                    if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(5, 6)))
                    {
                        if ($session['filter'][$filter][$field] == '')
                        {
                            $this->procedure[] = $strProcField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$filter][$field]);
                            $this->procedure[] = $strProcField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->dayBegin;
                            $this->values[] = $objDate->dayEnd;
                        }
                    }

                    // Sort by month
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(7, 8)))
                    {
                        if ($session['filter'][$filter][$field] == '')
                        {
                            $this->procedure[] = $strProcField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$filter][$field]);
                            $this->procedure[] = $strProcField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->monthBegin;
                            $this->values[] = $objDate->monthEnd;
                        }
                    }

                    // Sort by year
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(9, 10)))
                    {
                        if ($session['filter'][$filter][$field] == '')
                        {
                            $this->procedure[] = $strProcField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$filter][$field]);
                            $this->procedure[] = $strProcField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->yearBegin;
                            $this->values[] = $objDate->yearEnd;
                        }
                    }

                    // Manual filter
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'])
                    {
                        // CSV lists (see #2890)
                        if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['csv']))
                        {
                            if (in_array($field, $this->arrDetailFields))
                            {
                                $this->procedure[] = \Database::getInstance()
                                    ->findInSet('?', "(SELECT REPLACE(`value`, '".$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['csv']."', ',') FROM tl_formdata_details WHERE ff_name='".$field."' AND pid=f.id)", true);
                                $this->values[] = $session['filter'][$filter][$field];
                            }
                            else
                            {
                                $this->procedure[] = \Database::getInstance()
                                    ->findInSet('?', $strProcField, true);
                                $this->values[] = $session['filter'][$filter][$field];
                            }
                        }
                        else
                        {
                            $this->procedure[] = $strProcField . ' LIKE ?';
                            $this->values[] = '%"' . $session['filter'][$filter][$field] . '"%';
                        }
                    }

                    // Other sort algorithm
                    else
                    {
                        $this->procedure[] = $strProcField . '=?';
                        $this->values[] = $session['filter'][$filter][$field];
                    }
                }
            }
        }

        // Add sorting options
        foreach ($sortingFields as $cnt => $field)
        {
            $arrValues = array();
            $arrProcedure = array();

            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4)
            {
                $arrProcedure[] = 'pid=?';
                $arrValues[] = CURRENT_ID;
            }

            // add condition if called form specific formdata
            if ($this->strFormFilterKey != '' && $this->strFormFilterValue != '')
            {
                $arrProcedure[] = $this->strFormFilterKey . '=?';
                $arrValues[] = $this->strFormFilterValue;
            }

            if (!empty($this->root) && is_array($this->root))
            {
                $arrProcedure[] = "id IN(" . implode(',', array_map('intval', $this->root)) . ")";
            }

            if (in_array($field, $this->arrBaseFields))
            {
                $sqlField = $field;
            }
            elseif (in_array($field, $this->arrDetailFields))
            {
                $sqlField = "SELECT DISTINCT(value) FROM tl_formdata_details WHERE ff_name='" . $field . "' AND pid=f.id";
            }

            $objFields = \Database::getInstance()->prepare("SELECT DISTINCT(" . $sqlField . ") AS `". $field . "` FROM " . $this->strTable . " f ". ((is_array($arrProcedure) && strlen($arrProcedure[0])) ? ' WHERE ' . implode(' AND ', $arrProcedure) : ''))
                ->execute($arrValues);

            // Begin select menu
            $fields .= '
            <div class="col m4 l3">
            <select name="'.$field.'" id="'.$field.'" class="tl_select' . (isset($session['filter'][$filter][$field]) ? ' active' : '') . '">
            <option value="tl_'.$field.'">'.
            (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']).'</option>
            <option value="tl_'.$field.'">---</option>';

            if ($objFields->numRows)
            {
                $options = $objFields->fetchEach($field);

                // Sort by day
                if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(5, 6)))
                {
                    ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] == 6) ? rsort($options) : sort($options);

                    foreach ($options as $k => $v)
                    {
                        if ($v == '')
                        {
                            $options[$v] = '-';
                        }
                        else
                        {
                            $options[$v] = \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $v);
                        }

                        unset($options[$k]);
                    }
                }

                // Sort by month
                elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(7, 8)))
                {
                    ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] == 8) ? rsort($options) : sort($options);

                    foreach ($options as $k => $v)
                    {
                        if ($v == '')
                        {
                            $options[$v] = '-';
                        }
                        else
                        {
                            $options[$v] = date('Y-m', $v);
                            $intMonth = (date('m', $v) - 1);

                            if (isset($GLOBALS['TL_LANG']['MONTHS'][$intMonth]))
                            {
                                $options[$v] = $GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . date('Y', $v);
                            }
                        }

                        unset($options[$k]);
                    }
                }

                // Sort by year
                elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(9, 10)))
                {
                    ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] == 10) ? rsort($options) : sort($options);

                    foreach ($options as $k => $v)
                    {
                        if ($v == '')
                        {
                            $options[$v] = '-';
                        }
                        else
                        {
                            $options[$v] = date('Y', $v);
                        }

                        unset($options[$k]);
                    }
                }

                // Manual filter
                if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'])
                {
                    $moptions = array();

                    foreach ($options as $option)
                    {
                        // CSV lists (see #2890)
                        if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['csv']))
                        {
                            $doptions = trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['csv'], $option);
                        }
                        else
                        {
                            $doptions = deserialize($option);
                        }

                        if (is_array($doptions))
                        {
                            $moptions = array_merge($moptions, $doptions);
                        }
                    }

                    $options = $moptions;
                }

                $options = array_unique($options);
                $options_callback = array();

                // Call the options_callback
                if ((is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback']) || is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'])) && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'])
                {
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback']))
                    {
                        $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][0];
                        $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][1];

                        $this->import($strClass);
                        $options_callback = $this->$strClass->$strMethod($this);
                    }
                    elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback']))
                    {
                        $options_callback = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback']($this);
                    }

                    // Sort options according to the keys of the callback array
                    $options = array_intersect(array_keys($options_callback), $options);
                }

                $options_sorter = array();
                $blnDate = in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(5, 6, 7, 8, 9, 10));

                // Options
                foreach ($options as $kk => $vv)
                {
                    $value = $blnDate ? $kk : $vv;

                    // Replace the ID with the foreign key
                    if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey']))
                    {
                        $key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey'], 2);

                        $objParent = \Database::getInstance()->prepare("SELECT " . $key[1] . " AS value FROM " . $key[0] . " WHERE id=?")
                            ->limit(1)
                            ->execute($vv);

                        if ($objParent->numRows)
                        {
                            $vv = $objParent->value;
                        }
                    }

                    // Replace boolean checkbox value with "yes" and "no"
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isBoolean'] || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple']))
                    {
                        $vv = ($vv != '') ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
                    }

                    // Options callback
                    elseif (!empty($options_callback) && is_array($options_callback))
                    {
                        $vv = $options_callback[$vv];
                    }

                    // Get the name of the parent record (see #2703)
                    elseif ($field == 'pid')
                    {
                        $this->loadDataContainer($this->ptable);
                        $showFields = $GLOBALS['TL_DCA'][$this->ptable]['list']['label']['fields'];

                        if (!$showFields[0])
                        {
                            $showFields[0] = 'id';
                        }

                        $objShowFields = \Database::getInstance()->prepare("SELECT " . $showFields[0] . " FROM ". $this->ptable . " WHERE id=?")
                            ->limit(1)
                            ->execute($vv);

                        if ($objShowFields->numRows)
                        {
                            $vv = $objShowFields->$showFields[0];
                        }
                    }

                    $option_label = '';

                    // Use reference array
                    if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference']))
                    {
                        $option_label = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$vv]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$vv][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$vv];
                    }

                    // Associative array
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options']))
                    {
                        $option_label = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'][$vv];
                    }

                    // No empty options allowed
                    if (!strlen($option_label))
                    {
                        $option_label = $vv ?: '-';
                    }

                    $options_sorter['  <option value="' . specialchars($value) . '"' . ((isset($session['filter'][$filter][$field]) && $value == $session['filter'][$filter][$field]) ? ' selected="selected"' : '').'>'.$option_label.'</option>'] = utf8_romanize($option_label);
                }

                // Sort by option values
                if (!$blnDate)
                {
                    natcasesort($options_sorter);

                    if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(2, 4, 12)))
                    {
                        $options_sorter = array_reverse($options_sorter, true);
                    }
                }

                $fields .= "\n" . implode("\n", array_keys($options_sorter));
            }

            // End select menu
            $fields .= '
            </select>
            </div>';

            // Force a line-break after six elements (see #3777)
            if ((($cnt + 1) % 6) == 0)
            {
                $fields .= '<br>';
            }
        }

        return '

        <div class="filter-panel subpanel card-action row js-subpanel" id="filter-subpanel" data-filter="'.(isset($datafilter) ? 'true' : '').'">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['filter'] . ':</strong></div> ' . $fields . '
        </div>';
    }


    /**
     * Return the formatted group header as string
     * @param string
     * @param mixed
     * @param integer
     * @return string
     */
    protected function formatCurrentValue($field, $value, $mode)
    {
        $remoteNew = $value; // see #3861

        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'])
        {
            $remoteNew = ($value != '') ? ucfirst($GLOBALS['TL_LANG']['MSC']['yes']) : ucfirst($GLOBALS['TL_LANG']['MSC']['no']);
        }
        elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey']))
        {
            $key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey'], 2);

            $objParent = \Database::getInstance()->prepare("SELECT " . $key[1] . " AS value FROM " . $key[0] . " WHERE id=?")
                ->limit(1)
                ->execute($value);

            if ($objParent->numRows)
            {
                $remoteNew = $objParent->value;
            }
        }
        elseif (in_array($mode, array(1, 2)))
        {
            $remoteNew = ($value != '') ? ucfirst(utf8_substr($value , 0, 1)) : '-';
        }
        elseif (in_array($mode, array(3, 4)))
        {
            if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length']))
            {
                $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length'] = 2;
            }

            $remoteNew = ($value != '') ? ucfirst(utf8_substr($value , 0, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length'])) : '-';
        }
        elseif (in_array($mode, array(5, 6)))
        {
            $remoteNew = ($value != '') ? \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $value) : '-';
        }
        elseif (in_array($mode, array(7, 8)))
        {
            $remoteNew = ($value != '') ? date('Y-m', $value) : '-';
            $intMonth = ($value != '') ? (date('m', $value) - 1) : '-';

            if (isset($GLOBALS['TL_LANG']['MONTHS'][$intMonth]))
            {
                $remoteNew = ($value != '') ? $GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . date('Y', $value) : '-';
            }
        }
        elseif (in_array($mode, array(9, 10)))
        {
            $remoteNew = ($value != '') ? date('Y', $value) : '-';
        }
        else
        {
            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'])
            {
                $remoteNew = ($value != '') ? $field : '';
            }
            elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference']))
            {
                $remoteNew = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value];
            }
            elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options']))
            {
                $remoteNew = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'][$value];
            }
            else
            {
                $remoteNew = $value;
            }

            if (is_array($remoteNew))
            {
                $remoteNew = $remoteNew[0];
            }

            if (empty($remoteNew))
            {
                $remoteNew = '-';
            }
        }

        return $remoteNew;
    }


    /**
     * Return the formatted group header as string
     * @param string
     * @param mixed
     * @param integer
     * @param array
     * @return string
     */
    protected function formatGroupHeader($field, $value, $mode, $row)
    {
        static $lookup = array();

        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options']))
        {
            $group = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'][$value];
        }
        elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback']))
        {
            if (!isset($lookup[$field]))
            {
                $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][0];
                $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][1];

                $this->import($strClass);
                $lookup[$field] = $this->$strClass->$strMethod($this);
            }

            $group = $lookup[$field][$value];
        }
        else
        {
            $group = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value];
        }

        if (empty($group))
        {
            $group = is_array($GLOBALS['TL_LANG'][$this->strTable][$value]) ? $GLOBALS['TL_LANG'][$this->strTable][$value][0] : $GLOBALS['TL_LANG'][$this->strTable][$value];
        }

        if (empty($group))
        {
            $group = $value;

            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isBoolean'] && $value != '-')
            {
                $group = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'];
            }
        }

        // Call the group callback ($group, $sortingMode, $firstOrderBy, $row, $this)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback']))
        {
            $strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'][0];
            $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'][1];

            $this->import($strClass);
            $group = $this->$strClass->$strMethod($group, $mode, $field, $row, $this);
        }
        elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback']))
        {
            $group = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback']($group, $mode, $field, $row, $this);
        }

        return $group;
    }


    /**
     * Check if we need to preload TinyMCE
     */
    protected function checkForTinyMce()
    {
        if (!isset($GLOBALS['TL_DCA'][$this->strTable]['subpalettes']))
        {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'] as $palette)
        {
            $fields = trimsplit(',', $palette);

            foreach ($fields as $field)
            {
                if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['rte']))
                {
                    continue;
                }

                $rte = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['rte'];

                if (strncmp($rte, 'tiny', 4) !== 0)
                {
                    continue;
                }

                list ($file, $type) = explode('|', $rte);
                $key = 'ctrl_' . $field;

                $GLOBALS['TL_RTE'][$file][$key] = array
                (
                    'id'   => $key,
                    'file' => $file,
                    'type' => $type
                );
            }
        }
    }


    /**
     * Format a value
     * @param mixed
     * @param mixed
     * @return mixed
     */
    public function formatValue($k, $value)
    {
        $value = deserialize($value);

        $rgxp = '';
        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['rgxp'] )
        {
            $rgxp = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['rgxp'];
        }
        else
        {
            $rgxp = $this->arrFF[$k]['rgxp'];
        }

        // Array
        if (is_array($value))
        {
            $value = implode(', ', $value);
        }

        // Date and time
        if ($value && $rgxp == 'date')
        {
            $value = \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $value);
        }
        elseif ($value && $rgxp == 'time')
        {
            $value = \Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $value);
        }
        elseif ($value && $rgxp == 'datim')
        {
            $value = \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $value);
        }
        elseif ($value && in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['inputType'],
                array('checkbox', 'efgLookupCheckbox', 'select', 'conditionalselect', 'efgLookupSelect','radio')))
        {
            $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['csv'] : '|';
            $value = str_replace($strSep, ', ', $value);
        }

        // owner fields fd_member, fd_user
        if (in_array($k, $this->arrBaseFields) && in_array($k, $this->arrOwnerFields))
        {
            if ($k == 'fd_member')
            {
                $value = $this->arrMembers[$value];
            }
            elseif ($k == 'fd_user')
            {
                $value = $this->arrUsers[$value];
            }
            elseif ($k == 'fd_member_group')
            {
                $value = $this->arrMemberGroups[$value];
            }
            elseif ($k == 'fd_user_group')
            {
                $value = $this->arrUserGroups[$value];
            }
        }

        return $value;
    }


    /**
     * Send confirmation mail
     * @param integer $intID ID of record
     * @return string
     */
    public function mail($intID=false)
    {

        $blnSend = false;

        if (strlen(\Input::get('token')) && \Input::get('token') == $this->Session->get('fd_mail_send'))
        {
            $blnSend = true;
        }

        $strFormFilter = ($this->strTable == 'tl_formdata' && strlen($this->strFormKey) ? $this->sqlFormFilter : '');
        $table_alias = ($this->strTable == 'tl_formdata' ? ' f' : '');

        if ($intID)
        {
            $this->intId = $intID;
        }

        $return = '';
        $this->values[] = $this->intId;
        $this->procedure[] = 'id=?';
        $this->blnCreateNewVersion = false;

        // Get current record
        $sqlQuery = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias;
        $sqlWhere = " WHERE id=?";
        if ($sqlWhere != '')
        {
            $sqlQuery .= $sqlWhere;
        }

        $objRow = \Database::getInstance()->prepare($sqlQuery)
            ->limit(1)
            ->execute($this->intId);

        // Redirect if there is no record with the given ID
        if ($objRow->numRows < 1)
        {
            $this->log('Could not load record "'.$this->strTable.'.id='.$this->intId.'"', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $arrSubmitted = $objRow->fetchAssoc();
        $arrFiles = array();

        // Form
        $objForm = null;
        $intFormId = 0;

        if (!empty($GLOBALS['TL_DCA'][$this->strTable]['tl_formdata']['detailFields']))
        {
            // Try to get the form
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['tl_formdata']['detailFields'] as $strField)
            {
                if ($objForm !== null)
                {
                    break;
                }

                if (!empty($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['f_id']))
                {
                    $intFormId = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['f_id'];
                    $objForm = \FormModel::findByPk($intFormId);
                }
            }
        }

        if ($objForm == null)
        {
            $objForm = \FormModel::findOneBy('title', $arrSubmitted['form']);
        }

        if ($objForm == null)
        {
            $this->log('Could not load record "tl_form.id='.$intFormId.'" / "tl_form.title='.$arrSubmitted['form'].'"', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $arrForm = $objForm->row();
        $arrFormFields = $this->Formdata->getFormfieldsAsArray($arrForm['id']);

        if (empty($arrForm['confirmationMailSubject']) || (empty($arrForm['confirmationMailText']) && empty($arrForm['confirmationMailTemplate'])))
        {
            return '<p class="tl_error">Can not send this form data record.<br>Missing "Subject", "Text of confirmation mail" or "HTML-template for confirmation mail"<br>Please check configuration of form in form generator.</p>';
        }

        $this->loadDataContainer('tl_files');

        $objMailProperties = new \stdClass();
        $objMailProperties->subject = '';
        $objMailProperties->sender = '';
        $objMailProperties->senderName = '';
        $objMailProperties->replyTo = '';
        $objMailProperties->recipients = array();
        $objMailProperties->messageText = '';
        $objMailProperties->messageHtmlTmpl = '';
        $objMailProperties->messageHtml = '';
        $objMailProperties->attachments = array();
        $objMailProperties->skipEmptyFields = false;

        $objMailProperties->skipEmptyFields = ($arrForm['confirmationMailSkipEmpty']) ? true : false;

        $blnStoreOptionsValues = ($arrForm['efgStoreValues']) ? true : false;

        // Set the sender as given in form configuration
        list($senderName, $sender) = \String::splitFriendlyEmail($arrForm['confirmationMailSender']);
        $objMailProperties->sender = $sender;
        $objMailProperties->senderName = $senderName;

        // Set the 'reply to' address, if given in form configuration
        if (!empty($arrForm['confirmationMailReplyto']))
        {
            list($replyToName, $replyTo) = \String::splitFriendlyEmail($arrForm['confirmationMailReplyto']);
            $objMailProperties->replyTo = (strlen($replyToName) ? $replyToName . ' <' . $replyTo . '>' : $replyTo);
        }

        // Set recipient(s)
        $recipientFieldName = $arrForm['confirmationMailRecipientField'];

        if (!empty($recipientFieldName) && !empty($arrSubmitted[$recipientFieldName]))
        {
            $varRecipient = $arrSubmitted[$recipientFieldName];
            // handle efg option 'save options of values' for field types radio, select, checkbox
            if (in_array($arrFormFields[$recipientFieldName]['type'], array('radio', 'select', 'checkbox')))
            {
                if (!$blnStoreOptionsValues)
                {
                    $arrRecipient = $this->Formdata->prepareDatabaseValueForWidget($varRecipient, $arrFormFields[$recipientFieldName], false);
                    if (!empty($arrRecipient))
                    {
                        $varRecipient = implode(', ', $arrRecipient);
                    }
                    unset($arrRecipient);
                }
            }
            $strSep = (isset($arrFormFields[$recipientFieldName]['eval']['csv'])) ? $arrFormFields[$recipientFieldName]['eval']['csv'] : '|';
            $varRecipient = str_replace($strSep, ',', $varRecipient);
        }

        if (strlen($varRecipient) || strlen($arrForm['confirmationMailRecipient']))
        {
            $arrRecipient = array_merge(trimsplit(',', $varRecipient), trimsplit(',', $arrForm['confirmationMailRecipient']));
        }

        if (\Input::get('recipient'))
        {
            $arrRecipient = trimsplit(',', \Input::get('recipient'));
        }

        if (is_array($arrRecipient))
        {
            $strRecipient = implode(', ', $arrRecipient);

            // handle insert tag {{user::email}} in recipient fields
            if (!is_bool(strpos($strRecipient, "{{user::email}}")) && $arrSubmitted['fd_member'] > 0)
            {
                $objUser = \Database::getInstance()->prepare("SELECT `email` FROM `tl_member` WHERE id=?")
                    ->limit(1)
                    ->execute($arrSubmitted['fd_member']);

                $arrRecipient = array_map("str_replace", array_fill(0, count($arrRecipient), "{{user::email}}"), array_fill(0, count($arrRecipient), $objUser->email), $arrRecipient);
            }
            $arrRecipient = array_filter(array_unique($arrRecipient));
        }
        $objMailProperties->recipients = $arrRecipient;

        // Check if we want custom attachments... (Thanks to Torben Schwellnus)
        if ($arrForm['addConfirmationMailAttachments'])
        {
            if($arrForm['confirmationMailAttachments'])
            {
                $arrCustomAttachments = deserialize($arrForm['confirmationMailAttachments'], true);

                if (!empty($arrCustomAttachments))
                {
                    foreach ($arrCustomAttachments as $varFile)
                    {
                        $objFileModel = \FilesModel::findById($varFile);

                        if ($objFileModel !== null)
                        {
                            $objFile = new \File($objFileModel->path, true);
                            if ($objFile->size)
                            {
                                $objMailProperties->attachments[TL_ROOT . '/' . $objFile->path] = array
                                (
                                    'file' => TL_ROOT . '/' . $objFile->path,
                                    'name' => $objFile->basename,
                                    'mime' => $objFile->mime
                                );
                            }
                        }
                    }
                }
            }
        }

        $objMailProperties->subject = \String::decodeEntities($arrForm['confirmationMailSubject']);
        $objMailProperties->messageText = \String::decodeEntities($arrForm['confirmationMailText']);
        $objMailProperties->messageHtmlTmpl = $arrForm['confirmationMailTemplate'];

        // Replace Insert tags and conditional tags
        $objMailProperties = $this->Formdata->prepareMailData($objMailProperties, $arrSubmitted, $arrFiles, $arrForm, $arrFormFields);

        $objEmail = new \Email();
        $objEmail->from = $objMailProperties->sender;

        if (!empty($objMailProperties->senderName))
        {
            $objEmail->fromName = $objMailProperties->senderName;
        }
        $objEmail->subject = $objMailProperties->subject;

        if (!empty($objMailProperties->attachments))
        {
            foreach ($objMailProperties->attachments as $strFile => $varParams)
            {
                $strContent = file_get_contents($varParams['file'], false);
                $objEmail->attachFileFromString($strContent, $varParams['name'], $varParams['mime']);
            }
        }

        if (!empty($objMailProperties->messageText))
        {
            $objEmail->text = $objMailProperties->messageText;
        }

        if (!empty($objMailProperties->messageHtml))
        {
            $objEmail->html = $objMailProperties->messageHtml;
        }

        // Send Mail
        if (strlen(\Input::get('token')) && \Input::get('token') == $this->Session->get('fd_mail_send'))
        {
            $this->Session->set('fd_mail_send', null);
            $blnSend = true;

            $blnConfirmationSent = false;
            if ($blnSend)
            {
                // Send e-mail
                if (!empty($objMailProperties->recipients))
                {

                    foreach ($objMailProperties->recipients as $recipient)
                    {
                        if(strlen($recipient))
                        {
                            $recipient = str_replace(array('[', ']'), array('<', '>'), $recipient);
                            $recipientName = '';
                            if (strpos($recipient, '<') > 0)
                            {
                                preg_match('/(.*)?<(\S*)>/si', $recipient, $parts);
                                $recipientName = trim($parts[1]);
                                $recipient = (strlen($recipientName) ? $recipientName.' <'.$parts[2].'>' : $parts[2]);
                            }
                        }

                        $objEmail->sendTo($recipient);
                        $blnConfirmationSent = true;

                        \Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_formdata']['mail_sent'], str_replace(array('<', '>'), array('[', ']'), $recipient)));
                    }
                }

                if ($blnConfirmationSent && isset($this->intId) && intval($this->intId) > 0)
                {
                    $arrUpd = array('confirmationSent' => '1', 'confirmationDate' => time());
                    $res = \Database::getInstance()->prepare("UPDATE tl_formdata %s WHERE id=?")
                        ->set($arrUpd)
                        ->execute($this->intId);
                }

            }

        }

        $strToken = md5(uniqid('', true));
        $this->Session->set('fd_mail_send', $strToken);

        $strHint = '';

        if (strlen($objRow->confirmationSent))
        {
            if (!$blnSend)
            {
                if (strlen($objRow->confirmationDate))
                {
                    $dateConfirmation = new \Date($objRow->confirmationDate);
                    $strHint .= '<div class="tl_message"><p class="tl_info">'. sprintf($GLOBALS['TL_LANG']['tl_formdata']['confirmation_sent'], $dateConfirmation->date, $dateConfirmation->time) .'</p></div>';
                }
                else
                {
                    $strHint .= '<div class="tl_message"><p class="tl_info">'. sprintf($GLOBALS['TL_LANG']['tl_formdata']['confirmation_sent'], '-n/a-', '-n/a-') .'</p></div>';
                }
            }
        }

        // Preview Mail
        $return = '
<div id="tl_buttons">
<a href="'.$this->getReferer(ENCODE_AMPERSANDS).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_formdata']['mail'][0].'</h2>' . \Message::generate() . $strHint .'

<form action="'.ampersand(\Environment::get('script'), ENCODE_AMPERSANDS).'" id="tl_formdata_send" class="tl_form" method="get">
<div class="tl_formbody_edit fd_mail_send">
<input type="hidden" name="do" value="' . \Input::get('do') . '">
<input type="hidden" name="table" value="' . \Input::get('table') . '">
<input type="hidden" name="act" value="' . \Input::get('act') . '">
<input type="hidden" name="id" value="' . \Input::get('id') . '">
<input type="hidden" name="rt" value="'.REQUEST_TOKEN.'">
<input type="hidden" name="token" value="' . $strToken . '">

<table cellpadding="0" cellspacing="0" class="prev_header" summary="">
  <tr class="row_0">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_formdata']['mail_sender'][0] . '</td>
    <td class="col_1">' . $objMailProperties->sender . '</td>
  </tr>

  <tr class="row_1">
    <td class="col_0"><label for="ctrl_formdata_recipient">' . $GLOBALS['TL_LANG']['tl_formdata']['mail_recipient'][0]. '</label></td>
    <td class="col_1"><input name="recipient" type="ctrl_recipient" class="tl_text" value="' . implode(',', $objMailProperties->recipients) . '" '.($blnSend ? 'disabled="disabled"' : '').'></td>
  </tr>

  <tr class="row_2">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_formdata']['mail_subject'][0] . '</td>
    <td class="col_1">' . $objMailProperties->subject . '</td>
  </tr>';

        if (!empty($objMailProperties->attachments))
        {
            $attachments = array();
            foreach ($objMailProperties->attachments as $strFile => $arr)
            {
                $attachments[] = str_replace(TL_ROOT.'/', '', $strFile);
            }

            $return .= '
  <tr class="row_3">
    <td class="col_0" style="vertical-align:top">' . $GLOBALS['TL_LANG']['tl_formdata']['attachments'] . '</td>
    <td class="col_1">' . implode(',<br> ', $attachments) . '</td>
  </tr>';
        }

        $return .= '
</table>

<h3>' . $GLOBALS['TL_LANG']['tl_formdata']['mail_body_plaintext'][0] . '</h3>
<div class="preview_plaintext">
' . nl2br($objMailProperties->messageText) . '
</div>';

        if (!empty($objMailProperties->messageHtml))
        {
            $return .= '
<h3>' . $GLOBALS['TL_LANG']['tl_formdata']['mail_body_html'][0] . '</h3>
<div class="preview_html">
' . preg_replace(array('/.*?<body.*?>/si','/<\/body>.*$/si'), array('', ''), $objMailProperties->messageHtml) . '
</div>';
        }

        $return .= '
</div>';

        if (!$blnSend)
        {
            $return .= '
<div class="tl_formbody_submit">

<div class="tl_submit_container">
<input type="submit" id="send" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_formdata']['mail'][0]).'">
</div>

</div>';
        }

        $return .= '
</form>';

        return $return;
    }


    public function importFile()
    {
        if (\Input::get('key') != 'import')
        {
            return '';
        }

        if (null === $this->arrImportIgnoreFields)
        {
            $this->arrImportIgnoreFields = array('id', 'pid', 'tstamp', 'form', 'ip', 'date', 'confirmationSent', 'confirmationDate', 'import_source' );
        }

        if (null === $this->arrImportableFields)
        {
            $arrFdFields = array_merge($this->arrBaseFields, $this->arrDetailFields);
            $arrFdFields = array_diff($arrFdFields, $this->arrImportIgnoreFields);
            foreach ($arrFdFields as $strFdField)
            {
                $this->arrImportableFields[$strFdField] = $GLOBALS['TL_DCA']['tl_formdata']['fields'][$strFdField]['label'][0];
            }
        }

        $arrSessionData = $this->Session->get('EFG');
        if (null == $arrSessionData)
        {
            $arrSessionData = array();
        }
        $this->Session->set('EFG', $arrSessionData);

        // Import CSV
        if ($_POST['FORM_SUBMIT'] == 'tl_formdata_import')
        {
            $this->loadDataContainer('tl_files');

            $strMode = 'preview';
            $arrSessionData['import'][$this->strFormKey]['separator'] = $_POST['separator'];
            $arrSessionData['import'][$this->strFormKey]['csv_has_header'] = ($_POST['csv_has_header'] == '1' ? '1' : '');
            $this->Session->set('EFG', $arrSessionData);

            if (intval(\Input::post('import_source')) == 0)
            {
                \Message::addError($GLOBALS['TL_LANG']['tl_formdata']['error_select_source']);
                \Controller::reload();
            }

            $objFileModel = \FilesModel::findById(\Input::post('import_source'));
            $objFile = new \File($objFileModel->path, true);

            if ($objFile->extension != 'csv')
            {
                \Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
                setcookie('BE_PAGE_OFFSET', 0, 0, '/');
                \Controller::reload();
            }

            // Get separator
            switch (\Input::post('separator'))
            {
                case 'semicolon':
                    $strSeparator = ';';
                    break;

                case 'tabulator':
                    $strSeparator = '\t';
                    break;

                case 'comma':
                default:
                    $strSeparator = ',';
                    break;
            }

            if ($_POST['FORM_MODE'] == 'import')
            {
                $strMode = 'import';

                $time = time();
                $intTotal = null;
                $intInvalid = 0;
                $intValid = 0;

                $arrImportCols = \Input::post('import_cols');
                $arrSessionData['import'][$this->strFormKey]['import_cols'] = $arrImportCols;
                $this->Session->set('EFG', $arrSessionData);

                $arrMapFields = array_flip($arrImportCols);
                if (isset($arrMapFields['__IGNORE__']))
                {
                    unset($arrMapFields['__IGNORE__']);
                }

                $blnUseCsvHeader = ($arrSessionData['import'][$this->strFormKey]['csv_has_header'] == '1' ? true : false);

                $arrEntries = array();
                $resFile = $objFile->handle;

                $timeNow = time();
                $strFormTitle = $this->Formdata->arrFormsDcaKey[substr($this->strFormKey, 3)];

                $strAliasField = (strlen($this->Formdata->arrStoringForms[substr($this->strFormKey, 3)]['efgAliasField']) ? $this->Formdata->arrStoringForms[substr($this->strFormKey, 3)]['efgAliasField'] : '');

                $objForm = \FormModel::findOneBy('title', $strFormTitle);

                if ($objForm !== null)
                {
                    $arrFormFields = $this->Formdata->getFormfieldsAsArray($objForm->id);
                }

                while(($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
                {
                    if (null === $intTotal)
                    {
                        $intTotal = 0;
                        if ($blnUseCsvHeader)
                        {
                            continue;
                        }
                    }

                    $strAlias = '';
                    if (isset($arrRow[$arrMapFields['alias']]) && strlen($arrRow[$arrMapFields['alias']]))
                    {
                        $strAlias = $arrRow[$arrMapFields['alias']];
                    }
                    elseif (isset($arrRow[$arrMapFields[$strAliasField]]) && strlen($arrRow[$arrMapFields[$strAliasField]]))
                    {
                        \Input::setPost($strAliasField, $arrRow[$arrMapFields[$strAliasField]]);
                    }

                    $arrDetailSets = array();

                    // prepare base data
                    $arrSet = array
                    (
                        'tstamp' => $timeNow,
                        'fd_member' => 0,
                        'fd_user' => intval($this->User->id),
                        'form' => $strFormTitle,
                        'ip' => \Environment::get('ip'),
                        'date' => $timeNow,
                        'published' => ($GLOBALS['TL_DCA']['tl_formdata']['fields']['published']['default'] == '1' ? '1' : '' ),
                    );

                    foreach ($arrMapFields as $strField => $intCol)
                    {
                        if (in_array($strField, $this->arrImportIgnoreFields))
                        {
                            continue;
                        }

                        if (in_array($strField, $this->arrBaseFields))
                        {
                            $arrField = $GLOBALS['TL_DCA']['tl_formdata']['fields'][$strField];

                            if (in_array($strField, $this->arrOwnerFields))
                            {
                                switch ($strField)
                                {
                                    case 'fd_user':
                                        $array = 'arrUsers';
                                        break;

                                    case 'fd_member':
                                        $array = 'arrMembers';
                                        break;

                                    case 'fd_user_group':
                                        $array = 'arrUserGroups';
                                        break;

                                    case 'fd_member_group':
                                        $array = 'arrMemberGroups';
                                        break;
                                }

                                if (is_numeric($arrRow[$intCol]) && array_key_exists($arrRow[$intCol], $this->{$array}))
                                {
                                    $varValue = $arrRow[$intCol];
                                }
                                elseif (is_string($arrRow[$intCol]))
                                {
                                    $varValue = intval(array_search($arrRow[$intCol], $this->{$array}));
                                }
                            }
                            elseif ($strField == 'published')
                            {
                                if ($arrRow[$intCol] == $arrField['label'][0] || intval($arrRow[$intCol]) == 1)
                                {
                                    $varValue = '1';
                                }
                                else
                                {
                                    $varValue = '';
                                }
                            }
                            elseif ($strField == 'alias')
                            {
                                continue;
                            }
                            else
                            {
                                $varValue = $arrRow[$intCol];
                            }
                            $arrSet[$strField] = $varValue;
                        }
                    }

                    // prepare details data
                    foreach ($arrMapFields as $strField => $intCol)
                    {
                        if (in_array($strField, $this->arrImportIgnoreFields))
                        {
                            continue;
                        }

                        if (in_array($strField, $this->arrDetailFields))
                        {
                            // $arrField = array_merge($arrFormFields[$strField], $GLOBALS['TL_DCA']['tl_formdata']['fields'][$strField]);
                            $arrField = $GLOBALS['TL_DCA']['tl_formdata']['fields'][$strField];

                            $varValue = $this->Formdata->prepareImportValueForDatabase($arrRow[$intCol], $arrField);

                            // prepare details data
                            $arrDetailSet = array(
                                // 'pid' => $intNewId,
                                'sorting' => $arrFormFields[$strField]['sorting'],
                                'tstamp' => $timeNow,
                                'ff_id' => $arrField['ff_id'],
                                'ff_name' => $strField,
                                'value' => $varValue
                            );

                            $arrDetailSets[] = $arrDetailSet;
                        }
                    }

                    $intNewId = 0;
                    $blnSaved = true;

                    if (!empty($arrDetailSets))
                    {
                        $objNewFormdata = \Database::getInstance()->prepare("INSERT INTO tl_formdata %s")->set($arrSet)->execute();
                        $intNewId = $objNewFormdata->insertId;

                        $strAlias = $this->Formdata->generateAlias($strAlias, $this->strFormFilterValue, $intNewId);
                        if (strlen($strAlias))
                        {
                            \Database::getInstance()->prepare("UPDATE tl_formdata %s WHERE id=?")->set(array('alias' => $strAlias))->execute($intNewId);
                        }

                        foreach ($arrDetailSets as $kD => $arrDetailSet)
                        {
                            $arrDetailSet['pid'] = $intNewId;
                            try
                            {
                                $objNewFormdataDetails = \Database::getInstance()->prepare("INSERT INTO tl_formdata_details %s")
                                    ->set($arrDetailSet)
                                    ->execute();
                            }
                            catch(\Exception $ee)
                            {
                                $blnSaved = false;
                            }
                        }

                        if ($blnSaved === false && $intNewId > 0)
                        {
                            \Database::getInstance()->prepare("DELETE FROM tl_formdata WHERE id=?")->execute($intNewId);
                        }
                    }
                    else
                    {
                        $blnSaved = false;
                    }

                    if ($blnSaved)
                    {
                        $intValid++;
                    }
                    else
                    {
                        $intInvalid++;
                    }

                    $intTotal++;

                }

                \Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_formdata']['import_confirm'], $intValid));

                if ($intInvalid > 0)
                {
                    \Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_formdata']['import_invalid'], $intInvalid));
                }

                // Add a log entry
                $this->log('Imported file "'.$objFile->filename.'" into form data "'.$strFormTitle.'", created '.$intValid.' new records', __METHOD__, TL_GENERAL);

                setcookie('BE_PAGE_OFFSET', 0, 0, '/');
                \Controller::reload();

            }

            // Generate preview and form to select import fields
            if ($strMode == 'preview')
            {
                return $this->formImportPreview($objFile, $strSeparator);
            }

        }

        return $this->formImportSource();

    }


    /**
     * Generate the form to select import source and basic settings and return it as HTML string
     * @return string
     */
    protected function formImportSource()
    {
        $arrSessionData = $this->Session->get('EFG');

        $objFileSelector = new \FileTree(\Widget::getAttributesFromDca($GLOBALS['TL_DCA']['tl_formdata']['fields']['import_source'], 'import_source', null, 'import_source', 'tl_formdata'));

        // Return form
        return '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=import', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_formdata']['import'][1].'</h2>
'.\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_formdata_import" class="tl_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_formdata_import">
<input type="hidden" name="FORM_MODE" value="preview">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="tl_tbox block">
  <div class="w50">
  <h3><label for="separator">'.$GLOBALS['TL_LANG']['MSC']['separator'][0].'</label></h3>
  <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset()">
    <option value="comma"'.($arrSessionData['import'][$this->strFormKey]['separator'] == 'comma' ? ' selected="selected"' : '').'>'.$GLOBALS['TL_LANG']['MSC']['comma'].'</option>
    <option value="semicolon"'.($arrSessionData['import'][$this->strFormKey]['separator'] == 'semicolon' || !isset($arrSessionData['import'][$this->strFormKey]['separator']) ? ' selected="selected"' : '').'>'.$GLOBALS['TL_LANG']['MSC']['semicolon'].'</option>
    <option value="tabulator"'.($arrSessionData['import'][$this->strFormKey]['separator'] == 'tabulator' ? ' selected="selected"' : '').'>'.$GLOBALS['TL_LANG']['MSC']['tabulator'].'</option>
  </select>'.(strlen($GLOBALS['TL_LANG']['MSC']['separator'][1]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['separator'][1].'</p>' : '').'
  </div>
  <div class="w50 m12 cbx">
  <div class="tl_checkbox_single_container">
  <input name="csv_has_header" id="csv_has_header" type="checkbox" value="1"'.($arrSessionData['import'][$this->strFormKey]['csv_has_header'] == '1' ? ' checked="checked"' : '').'>
  <label for="csv_has_header">'.$GLOBALS['TL_LANG']['tl_formdata']['csv_has_header'][0].'</label>
  </div>
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_formdata']['csv_has_header'][1].'</p>
  </div>

  <div class="clr">'
        . $objFileSelector->parse() . (strlen($GLOBALS['TL_LANG']['tl_formdata']['import_source'][1]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_formdata']['import_source'][1].'</p>' : '') . '

  </div>
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_formdata']['import'][0]).'" onfocus="document.cookie = \'BE_PAGE_OFFSET=0; path=/\';">
</div>

</div>
</form>' . $this->getFilepickerJavascript('reloadEfgImportSource');

    }


    /**
     * Generate the form to select the field mappings and return it as HTML string
     * @return string
     */
    protected function formImportPreview($objFile, $strSeparator)
    {

        $arrSessionData = $this->Session->get('EFG');
        $blnUseCsvHeader = ($arrSessionData['import'][$this->strFormKey]['csv_has_header'] == '1' ? true : false);

        $arrEntries = array();
        $resFile = $objFile->handle;

        $intTotal = 0;
        $intReadLines = 50;
        if ($blnUseCsvHeader)
        {
            $intReadLines++;
        }

        while(($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
        {
            $arrEntries[] = $arrRow;
            $intTotal++;
            if ($intTotal == $intReadLines)
            {
                break;
            }
        }

        if ($blnUseCsvHeader && !isset($arrSessionData['import'][$this->strFormKey]['import_cols']))
        {
            foreach ($arrEntries[0] as $col => $val)
            {
                if (array_key_exists($val, $this->arrImportableFields))
                {
                    $arrSessionData['import'][$this->strFormKey]['import_cols'][$col] = $val;
                }
                else
                {
                    $mxRes = array_search($val, $this->arrImportableFields);
                    if ($mxRes !== false)
                    {
                        $arrSessionData['import'][$this->strFormKey]['import_cols'][$col] = $mxRes;
                    }
                    else
                    {
                        $arrSessionData['import'][$this->strFormKey]['import_cols'][$col] = '__IGNORE__';
                    }
                }
            }
        }

        $this->Session->set('EFG', $arrSessionData);

        // plugin stylect cannot handle selects inside scrolling div
        // .. deactivate it
        $return = '
<script>
var Stylect = {
    convertSelects: function() { return; }
};
</script>
';

        $return .= '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=import', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_formdata']['import'][1].'</h2>'
            .\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_formdata_import" class="tl_form" method="post">
<div class="tl_formbody_edit tl_formdata_import">
    <input type="hidden" name="FORM_SUBMIT" value="tl_formdata_import">
    <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
    <input type="hidden" name="FORM_MODE" value="import">
    <input type="hidden" name="import_source" value="'.\Input::post('import_source').'">
    <input type="hidden" name="separator" value="'.\Input::post('separator').'">
    <input type="hidden" name="csv_has_header" value="'.\Input::post('csv_has_header').'">

    <div class="tl_tbox block">
        <h3>'.$GLOBALS['TL_LANG']['tl_formdata']['import_preview'][0].'</h3>
        <p class="tl_help">'.$GLOBALS['TL_LANG']['tl_formdata']['import_preview'][1].'</p>
        <div class="fd_import_prev">
            <div>';
        $return .= '
            <table class="fd_import_data">
                <thead><tr>';
        foreach ($arrEntries[0] as $col => $val)
        {
            $return .= '
                    <td>'.$this->importFieldmapMenu($arrEntries, $col, $val).'</td>';
        }
        $return .= '
                </tr></thead>';
        $return .= '
                <tbody>';

        if ($blnUseCsvHeader)
        {
            array_shift($arrEntries);
        }

        foreach ($arrEntries as $row)
        {
            $return .= '
                <tr>';
            foreach ($row as $col => $val)
            {
                $return .= '
                    <td>'.$val.'</td>';
            }
            $return .= '
            </tr>';
        }
        $return .= '
                </tbody>
            </table>';
        $return .= '
            </div>
        </div>
    </div>
</div>

<div class="tl_formbody_submit">
    <div class="tl_submit_container">
        <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_formdata']['import'][0]).'">
    </div>
</div>
</form>';

        return $return;

    }


    /**
     * Generate a dropdown menu to select destination field and return it as HTML string
     * @return string
     */
    protected function importFieldmapMenu(&$arrEntries, $col, $val)
    {

        $arrSessionData = $this->Session->get('EFG');

        $return = '
<select name="import_cols['.$col.']">
    <option value="__IGNORE__"'.((!isset($arrSessionData['import'][$this->strFormKey]['import_cols'][$col]) || $arrSessionData['import'][$this->strFormKey]['import_cols'][$col] == '__IGNORE__') ? ' selected="SELECTED"' : '').'>'.$GLOBALS['TL_LANG']['tl_formdata']['option_import_ignore'].'</option>';
        if (!empty($this->arrImportableFields))
        {
            foreach (array_keys($this->arrImportableFields) as $strFdField)
            {
                $selected = '';
                if (isset($arrSessionData['import'][$this->strFormKey]['import_cols']))
                {
                    if ($arrSessionData['import'][$this->strFormKey]['import_cols'][$col] == $strFdField)
                    {
                        $selected = ' selected="selected"';
                    }
                }
                $return .= '<option value="'.$strFdField.'"'.$selected.'>'.(isset($GLOBALS['TL_DCA']['tl_formdata']['fields'][$strFdField]['label'][0]) ? $GLOBALS['TL_DCA']['tl_formdata']['fields'][$strFdField]['label'][0] : $strFdField).'</option>';
            }
        }
        $return .= '
</select>';

        return $return;

    }


    public function export($strMode='csv')
    {

        if (strlen(\Input::get('expmode')))
        {
            $strMode = \Input::get('expmode');
        }

        $return = '';

        $blnCustomXlsExport = false;
        $blnCustomExport = false;
        $arrHookData = array();
        $arrHookDataColumns = array();

        if ($strMode=='xls')
        {
            // Check for HOOK efgExportXls
            if (array_key_exists('efgExportXls', $GLOBALS['TL_HOOKS']) && is_array($GLOBALS['TL_HOOKS']['efgExportXls']))
            {
                $blnCustomXlsExport = true;
            }
        }
        elseif ($strMode!='csv')
        {
            $blnCustomExport = true;
        }

        // filter or search for values
        $session = $this->Session->getData();

        $showFields = array_merge($this->arrBaseFields, $this->arrDetailFields);
        $ignoreFields = array('tstamp');

        if (!empty($this->arrExportIgnoreFields))
        {
            $ignoreFields = array_unique(array_merge($ignoreFields, $this->arrExportIgnoreFields));
        }

        $table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->ptable : $this->strTable;
        $table_alias = ($table == 'tl_formdata' ? ' f' : '');

        $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
        $firstOrderBy = preg_replace('/\s+.*$/i', '', $orderBy[0]);

        if (is_array($this->orderBy) && strlen($this->orderBy[0]))
        {
            $orderBy = $this->orderBy;
            $firstOrderBy = $this->firstOrderBy;
        }

        if (\Input::get('table') && $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] && \Database::getInstance()->fieldExists('pid', $this->strTable))
        {
            $this->procedure[] = 'pid=?';
            $this->values[] = \Input::get('id');
        }

        $query = "SELECT * " .(!empty($this->arrSqlDetails) ? ', '.implode(',' , array_values($this->arrSqlDetails)) : '') ." FROM " . $this->strTable . $table_alias;

        $sqlWhere = '';

        // Set search value from session
        $strSessionKey = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : (strlen($this->strFormKey)) ? $this->strFormKey : $this->strTable;
        if (strlen($session['search'][$strSessionKey]['value']))
        {
            $sqlSearchField = $session['search'][$strSessionKey]['field'];
            if (in_array($sqlSearchField, $this->arrDetailFields))
            {
                $sqlSearchField = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' . $session['search'][$strSessionKey]['field'] .'\' AND pid=f.id)';
            }
            $this->procedure[] = "CAST(".$sqlSearchField." AS CHAR) REGEXP ?";
            $this->values[] = $session['search'][$strSessionKey]['value'];
        }

        // Set filter from session
        $arrFilterFields = array();
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
        {
            if ($v['filter'])
            {
                $arrFilterFields[] = $k;
            }
        }
        if (!empty($arrFilterFields))
        {
            foreach ($arrFilterFields as $field)
            {
                if (isset($session['filter'][$strSessionKey][$field]))
                {
                    $sqlFilterField = $field;
                    if (in_array($field, $this->arrDetailFields))
                    {
                        $sqlFilterField = '(SELECT value FROM tl_formdata_details WHERE ff_name=\'' . $field .'\' AND pid=f.id)';
                    }

                    // Sort by day
                    if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(5, 6)))
                    {
                        if ($session['filter'][$strSessionKey][$field] == '')
                        {
                            $this->procedure[] = $sqlFilterField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$strSessionKey][$field]);
                            $this->procedure[] = $sqlFilterField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->dayBegin;
                            $this->values[] = $objDate->dayEnd;
                        }
                    }

                    // Sort by month
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(7, 8)))
                    {
                        if ($session['filter'][$strSessionKey][$field] == '')
                        {
                            $this->procedure[] = $sqlFilterField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$strSessionKey][$field]);
                            $this->procedure[] = $sqlFilterField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->monthBegin;
                            $this->values[] = $objDate->monthEnd;
                        }
                    }

                    // Sort by year
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(9, 10)))
                    {
                        if ($session['filter'][$strSessionKey][$field] == '')
                        {
                            $this->procedure[] = $sqlFilterField . "=''";
                        }
                        else
                        {
                            $objDate = new \Date($session['filter'][$strSessionKey][$field]);
                            $this->procedure[] = $sqlFilterField . ' BETWEEN ? AND ?';
                            $this->values[] = $objDate->yearBegin;
                            $this->values[] = $objDate->yearEnd;
                        }
                    }

                    // Manual filter
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'])
                    {
                        $this->procedure[] = $sqlFilterField . ' LIKE ?';
                        $this->values[] = '%"' . $session['filter'][$strSessionKey][$field] . '"%';
                    }

                    // Other sort algorithm
                    else
                    {
                        $this->procedure[] = $sqlFilterField . '=?';
                        $this->values[] = $session['filter'][$strSessionKey][$field];
                    }
                }
            }
        }

        if (!empty($this->procedure))
        {
            $arrProcedure = $this->procedure;

            foreach ($arrProcedure as $kProc => $vProc)
            {
                $strProcField = substr($vProc, 0, strpos($vProc, '='));
                if (in_array($strProcField, $this->arrDetailFields))
                {
                    $arrProcedure[$kProc] = "(SELECT value FROM tl_formdata_details WHERE ff_name='" . $strProcField . "' AND pid=f.id)=?";
                }
            }
            $sqlWhere .= ($sqlWhere != '' ? " AND " : " WHERE ") . implode(' AND ', $arrProcedure);
        }

        if ($sqlWhere != '')
        {
            $query .= $sqlWhere;
        }

        if (is_array($orderBy) && strlen($orderBy[0]))
        {
            foreach ($orderBy as $o => $strVal)
            {
                $arrOrderField = explode(' ', $strVal);
                $strOrderField = $arrOrderField[0];
                unset($arrOrderField);
                if (!in_array($strOrderField, $this->arrBaseFields))
                {
                    $orderBy[$o] = "(SELECT value FROM tl_formdata_details WHERE ff_name='" . $strOrderField . "' AND pid=f.id)";
                }
            }
            $query .= " ORDER BY " . implode(', ', $orderBy);
        }
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 1 && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] % 2) == 0)
        {
            $query .= " DESC";
        }

        $objRowStmt = \Database::getInstance()->prepare($query);
        $objRow = $objRowStmt->execute($this->values);

        $intRowCounter = -1;

        $strExpEncl = '';
        $strExpSep = ';';

        $useFormValues = $this->Formdata->arrStoringForms[substr($this->strFormKey, 3)]['useFormValues'];
        $useFieldNames = $this->Formdata->arrStoringForms[substr($this->strFormKey, 3)]['useFieldNames'];

        if ($strMode=='csv')
        {
            header('Content-Type: appplication/csv; charset='.($this->blnExportUTF8Decode ? $this->strExportConvertToCharset : 'utf-8'));
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="export_' . $this->strFormKey . '_' . date("Ymd_His") .'.csv"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Expires: 0');
        }
        elseif ($strMode=='xls')
        {
            if (!$blnCustomXlsExport)
            {
                $xls = new \xlsexport();
                $strXlsSheet = "Export";
                $xls->addworksheet($strXlsSheet);
            }
        }

        // List records
        if ($objRow->numRows)
        {
            $result = $objRow->fetchAllAssoc();

            // Rename each pid to its label and resort the result (sort by parent table)
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 3 && \Database::getInstance()->fieldExists('pid', $this->strTable))
            {
                $firstOrderBy = 'pid';

                foreach ($result as $k=>$v)
                {
                    $objField = \Database::getInstance()->prepare("SELECT " . $showFields[0] . " FROM " . $this->ptable . " WHERE id=?")
                        ->limit(1)
                        ->execute($v['pid']);
                    $result[$k]['pid'] = $objField->$showFields[0];
                }

                $aux = array();
                foreach ($result as $row)
                {
                    $aux[] = $row['pid'];
                }
                array_multisort($aux, SORT_ASC, $result);
            }

            // Process result and format values
            foreach ($result as $row)
            {
                $intRowCounter++;

                $args = array();
                $this->current[] = $row['id'];

                if ($intRowCounter == 0)
                {
                    if ($strMode == 'xls')
                    {
                        if (!$blnCustomXlsExport)
                        {
                            $xls->totalcol = count($showFields);
                        }
                    }

                    $strExpEncl = '"';
                    $strExpSep = '';

                    $intColCounter = -1;
                    foreach ($showFields as $k=>$v)
                    {
                        if (in_array($v, $ignoreFields))
                        {
                            continue;
                        }

                        $intColCounter++;

                        if ($useFieldNames)
                        {
                            $strName = $v;
                        }
                        elseif (strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['label'][0]))
                        {
                            $strName = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['label'][0];
                        }
                        elseif (strlen($GLOBALS['TL_LANG']['tl_formdata'][$v][0]))
                        {
                            $strName = $GLOBALS['TL_LANG']['tl_formdata'][$v][0];
                        }
                        else
                        {
                            $strName = strtoupper($v);
                        }

                        if (strlen($strName))
                        {
                            $strName = \String::decodeEntities($strName);
                        }

                        if ($this->blnExportUTF8Decode || ($strMode == 'xls' && !$blnCustomXlsExport))
                        {
                            $strName = $this->convertEncoding($strName, $GLOBALS['TL_CONFIG']['characterSet'], $this->strExportConvertToCharset);
                        }

                        if ($strMode=='csv')
                        {
                            $strName = str_replace('"', '""', $strName);
                            echo $strExpSep . $strExpEncl . $strName . $strExpEncl;
                            $strExpSep = ";";
                        }
                        elseif ($strMode=='xls')
                        {
                            if (!$blnCustomXlsExport)
                            {
                                $xls->setcell(array("sheetname" => $strXlsSheet,"row" => $intRowCounter, "col" => $intColCounter, "data" => $strName, "fontweight" => XLSFONT_BOLD, "vallign" => XLSXF_VALLIGN_TOP, "fontfamily" => XLSFONT_FAMILY_NORMAL));
                                $xls->setcolwidth($strXlsSheet,$intColCounter,0x1aff);
                            }
                            else
                            {
                                $arrHookDataColumns[$v] = $strName;
                            }
                        }
                        elseif ($blnCustomExport)
                        {
                            $arrHookDataColumns[$v] = $strName;
                        }

                    }

                    $intRowCounter++;

                    if ($strMode=='csv')
                    {
                        echo "\n";
                    }

                }

                $strExpSep = '';

                $intColCounter = -1;

                // Prepare field value
                foreach ($showFields as $k=>$v)
                {

                    if (in_array($v, $ignoreFields))
                    {
                        continue;
                    }

                    $intColCounter++;

                    $strVal = '';
                    $strVal = $row[$v];

                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'] == 'date'
                        && in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
                    {
                        $strVal = ($row[$v] ? date($GLOBALS['TL_CONFIG']['dateFormat'], $row[$v]) : '');
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'] == 'datim'
                        && in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
                    {
                        $strVal = ($row[$v] ? date($GLOBALS['TL_CONFIG']['datimFormat'], $row[$v]) : '');
                    }
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
                    {
                        $strVal = ($row[$v] ? date($GLOBALS['TL_CONFIG']['datimFormat'], $row[$v]) : '');
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'] == 'checkbox'
                        && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['multiple'])
                    {
                        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) && count($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) > 0)
                        {
                            if ($row[$v] != '')
                            {
                                if (!$useFormValues)
                                {
                                    $strVal = $row[$v];
                                }
                                else
                                {
                                    $arrCbOpt = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options'];
                                    $arrCbOpt = array_flip($arrCbOpt);
                                    $strVal = $arrCbOpt[$row[$v]];
                                }
                            }
                            else
                            {
                                $strVal = '';
                            }
                        }
                        else
                        {
                            $strVal = ($row[$v] != '') ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
                        }
                    }
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'],
                        array('radio', 'efgLookupRadio', 'select', 'conditionalselect', 'efgLookupSelect', 'checkbox', 'efgLookupCheckbox')))
                    {
                        $strSep = (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'])) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'] : '|';

                        // take the assigned value instead of the user readable output
                        if ($useFormValues == 1)
                        {
                            if ((strpos($row[$v], $strSep) === false)
                                && (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) && count($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) > 0))
                            {
                                // handle grouped options
                                $arrOptions = array();
                                foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options'] as $o => $mxVal)
                                {
                                    if (!is_array($mxVal))
                                    {
                                        $arrOptions[$o] = $mxVal;
                                    }
                                    else
                                    {
                                        foreach ($mxVal as $ov => $mxOVal)
                                        {
                                            $arrOptions[$ov] = $mxOVal;
                                        }
                                    }
                                }

                                $options = array_flip($arrOptions);
                                $strVal = $options[$row[$v]];
                            }
                            else
                            {
                                if ((is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) && count($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options']) > 0))
                                {
                                    // handle grouped options
                                    $arrOptions = array();
                                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['options'] as $o => $mxVal)
                                    {
                                        if ((!is_array($mxVal)))
                                        {
                                            $arrOptions[$o] = $mxVal;
                                        }
                                        else
                                        {
                                            foreach ($mxVal as $ov => $mxOVal)
                                            {
                                                $arrOptions[$ov] = $mxOVal;
                                            }
                                        }
                                    }

                                    $options = array_flip($arrOptions);

                                    $tmparr = explode($strSep, $row[$v]);
                                    $fieldvalues = array();
                                    foreach ($tmparr as $valuedesc)
                                    {
                                        array_push($fieldvalues, $options[$valuedesc]);
                                    }
                                    $strVal = implode(",\n", $fieldvalues);
                                }
                                else
                                {
                                    $strVal = strlen($row[$v]) ? str_replace($strSep, ",\n", $row[$v]) : '';
                                }
                            }
                        }
                        else
                        {
                            $strVal = strlen($row[$v]) ? str_replace($strSep, ",\n", $row[$v]) : '';
                        }
                    }
                    else
                    {
                        $row_v = deserialize($row[$v]);

                        if (!empty($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv']))
                        {
                            $row_v = explode($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['csv'], $row[$v]);
                        }

                        if (is_array($row_v))
                        {
                            $args_k = array();

                            foreach ($row_v as $option)
                            {
                                $args_k[] = strlen($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$option]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$option] : $option;
                            }

                            $args[$k] = implode(",\n", $args_k);
                        }
                        elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$row[$v]]))
                        {
                            $args[$k] = is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$row[$v]]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$row[$v]][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['reference'][$row[$v]];
                        }
                        else
                        {
                            $args[$k] = $row[$v];
                        }
                        $strVal = is_null($args[$k]) ? $args[$k] : vsprintf('%s', $args[$k]);
                    }

                    if (in_array($v, $this->arrBaseFields) || in_array($v, $this->arrOwnerFields))
                    {
                        if ($v == 'fd_member')
                        {
                            $strVal = $this->arrMembers[intval($row[$v])];
                        }
                        elseif ($v == 'fd_user')
                        {
                            $strVal = $this->arrUsers[intval($row[$v])];
                        }
                        elseif ($v == 'fd_member_group')
                        {
                            $strVal = $this->arrMemberGroups[intval($row[$v])];
                        }
                        elseif ($v == 'fd_user_group')
                        {
                            $strVal = $this->arrUserGroups[intval($row[$v])];
                        }
                    }

                    if (strlen($strVal))
                    {
                        $strVal = \String::decodeEntities($strVal);
                        $strVal = preg_replace(array('/<br.*\/*>/si'), array("\n"), $strVal);

                        if ($this->blnExportUTF8Decode || ($strMode == 'xls' && !$blnCustomXlsExport))
                        {
                            $strVal = $this->convertEncoding($strVal, $GLOBALS['TL_CONFIG']['characterSet'], $this->strExportConvertToCharset);
                        }
                    }

                    if ($strMode=='csv')
                    {
                        $strVal = str_replace('"', '""', $strVal);
                        echo $strExpSep . $strExpEncl . $strVal . $strExpEncl;

                        $strExpSep = ";";
                    }
                    elseif ($strMode=='xls')
                    {
                        if (!$blnCustomXlsExport)
                        {
                            $xls->setcell(array("sheetname" => $strXlsSheet,"row" => $intRowCounter, "col" => $intColCounter, "data" => $strVal, "vallign" => XLSXF_VALLIGN_TOP, "fontfamily" => XLSFONT_FAMILY_NORMAL));
                        }
                        else
                        {
                            $arrHookData[$intRowCounter][$v] = $strVal;
                        }
                    }
                    elseif ($blnCustomExport)
                    {
                        $arrHookData[$intRowCounter][$v] = $strVal;
                    }

                }

                if ($strMode=='csv')
                {
                    $strExpSep = '';
                    echo "\n";
                }

            }

        }

        if ($strMode=='xls')
        {
            if (!$blnCustomXlsExport)
            {
                $xls->sendfile("export_" . $this->strFormKey . "_" . date("Ymd_His") . ".xls");
                exit;
            }
            else
            {
                foreach ($GLOBALS['TL_HOOKS']['efgExportXls'] as $key => $callback)
                {
                    $this->import($callback[0]);
                    $res = $this->$callback[0]->$callback[1]($arrHookDataColumns, $arrHookData);
                }
            }
        }
        elseif ($blnCustomExport)
        {
            foreach ($GLOBALS['TL_HOOKS']['efgExport'] as $key => $callback)
            {
                $this->import($callback[0]);
                $res = $this->$callback[0]->$callback[1]($arrHookDataColumns, $arrHookData, $strMode);
            }
        }
        exit;
    }


    public function exportxls()
    {
        $this->export('xls');
    }


    /**
     * Convert encoding
     * @return String
     * @param $strString String to convert
     * @param $from charset to convert from
     * @param $to charset to convert to
     */
    public function convertEncoding($strString, $from, $to)
    {
        if ($from == $to)
        {
            return $strString;
        }

        if (USE_MBSTRING)
        {
            @mb_substitute_character('none');
            return @mb_convert_encoding($strString, $to, $from);
        }
        elseif (function_exists('iconv'))
        {
            if (strlen($iconv = @iconv($from, $to . '//IGNORE', $strString)))
            {
                return $iconv;
            }
            else
            {
                return @iconv($from, $to, $strString);
            }
        }
        return $strString;
    }


    private function getSubpaletteJavascript()
    {

        $strJs = "
<script>
    AjaxRequest.toggleEfgSubpalette = function (el, id, field) {
        el.blur();
        var item = $(id);

        if (item) {
            if (!el.value) {
                el.value = 1;
                el.checked = 'checked';
                item.setStyle('display', 'block');
                new Request.Contao({field:el}).post({'action':'toggleEfgSubpalette', 'id':id, 'field':field, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
            } else {
                el.value = '';
                el.checked = '';
                item.setStyle('display', 'none');
                new Request.Contao({field:el}).post({'action':'toggleEfgSubpalette', 'id':id, 'field':field, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
            }
            return;
        }

        new Request.Contao({
            field: el,
            evalScripts: false,
            onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
            onSuccess: function(txt, json) {
                var div = new Element('div', {
                    'id': id,
                    'html': txt,
                    'styles': {
                        'display': 'block'
                    }
                }).inject($(el).getParent('div').getParent('div'), 'after');

                // Execute scripts after the DOM has been updated
                if (json.javascript) $exec(json.javascript);

                el.value = 1;
                el.checked = 'checked';

                AjaxRequest.hideBox();
                Backend.addInteractiveHelp();
                Backend.addColorPicker();

                // HOOK
                window.fireEvent('subpalette'); // Backwards compatibility
                window.fireEvent('ajax_change');
            }
        }).post({'action':'toggleEfgSubpalette', 'id':id, 'field':field, 'load':1, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
    }

window.addEvent('domready', function(){
    $$('input[onclick^=AjaxRequest.toggleSubpalette]').each(function(item){
        item.set('onclick', (item.get('onclick').replace(/toggleSubpalette/, 'toggleEfgSubpalette')))
    });
});
</script>";

        return $strJs;

    }


    private function getFilepickerJavascript($strReload)
    {

        $strJs = "
<script>
function handleEfgFileselectorButton(){
    $$('a[href*=contao/file.php]').addEvent('click', function(e){
        var el = e.target;
        var elHidden = el.getParent('div.selector_container').getPrevious('input[type=hidden]');
        var opt = { 'id': elHidden.get('name'), 'url': e.target.get('href') };

        $$('div#simple-modal div.simple-modal-footer a.btn.primary').removeEvents('click').addEvent('click', function() {
            var val = [],
                frm = null,
                frms = window.frames;
            for (var i=0; i<frms.length; i++) {
                if (frms[i].name == 'simple-modal-iframe') {
                    frm = frms[i];
                    break;
                }
            }
            if (frm === null) {
                alert('Could not find the SimpleModal frame');
                return;
            }
            if (frm.document.location.href.indexOf('contao/main.php') != -1) {
                alert(Contao.lang.picker);
                return; // see #5704
            }
            var inp = frm.document.getElementById('tl_listing').getElementsByTagName('input');
            for (var i=0; i<inp.length; i++) {
                if (!inp[i].checked || inp[i].id.match(/^check_all_/)) continue;
                if (!inp[i].id.match(/^reset_/)) val.push(inp[i].get('value'));
            }
            if (opt.tag) {
                $(opt.tag).value = val.join(',');
                if (opt.url.match(/page\.php/)) {
                    $(opt.tag).value = '{{link_url::' + $(opt.tag).value + '}}';
                }
                opt.self.set('href', opt.self.get('href').replace(/&value=[^&]*/, '&value='+val.join(',')));
            } else {
                $('ctrl_'+opt.id).value = val.join(\"\t\");
                var act = (opt.url.indexOf('contao/page.php') != -1) ? 'reloadPagetree' : '" . $strReload . "';
                new Request.Contao({
                    field: $('ctrl_'+opt.id),
                    evalScripts: false,
                    onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
                    onSuccess: function(txt, json) {
                        $('ctrl_'+opt.id).getParent('div').set('html', json.content);
                        json.javascript && Browser.exec(json.javascript);
                        AjaxRequest.hideBox();
                        window.fireEvent('ajax_change');
                    }
                }).post({'action':act, 'name':opt.id, 'value':$('ctrl_'+opt.id).value, 'REQUEST_TOKEN':Contao.request_token});
            }
            $('simple-modal').hide();
            $('simple-modal-overlay').hide();
            document.body.setStyle('overflow', 'auto');
        });
    });
}

window.addEvent('domready', function(){
    handleEfgFileselectorButton();
});
</script>";

        return $strJs;

    }

}
