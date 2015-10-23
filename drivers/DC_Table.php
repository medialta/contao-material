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
 * Provide methods to modify the database.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DC_Table extends \Contao\DC_Table {

    public function edit($intId=null, $ajaxId=null)
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
        {
            $this->log('Table "'.$this->strTable.'" is not editable', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        if ($intId != '')
        {
            $this->intId = $intId;
        }

        // Get the current record
        $objRow = $this->Database->prepare("SELECT * FROM " . $this->strTable . " WHERE id=?")
                                 ->limit(1)
                                 ->execute($this->intId);

        // Redirect if there is no record with the given ID
        if ($objRow->numRows < 1)
        {
            $this->log('Could not load record "'.$this->strTable.'.id='.$this->intId.'"', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $this->objActiveRecord = $objRow;

        $return = '';
        $this->values[] = $this->intId;
        $this->procedure[] = 'id=?';

        $this->blnCreateNewVersion = false;
        $objVersions = new \Versions($this->strTable, $this->intId);

        if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'])
        {
            // Compare versions
            if (\Input::get('versions'))
            {
                $objVersions->compare();
            }

            // Restore a version
            if (\Input::post('FORM_SUBMIT') == 'tl_version' && \Input::post('version') != '')
            {
                $objVersions->restore(\Input::post('version'));
                $this->reload();
            }
        }

        $objVersions->initialize();

        // Build an array from boxes and rows
        $this->strPalette = $this->getPalette();
        $boxes = trimsplit(';', $this->strPalette);
        $legends = array();

        if (!empty($boxes))
        {
            foreach ($boxes as $k=>$v)
            {
                $eCount = 1;
                $boxes[$k] = trimsplit(',', $v);

                foreach ($boxes[$k] as $kk=>$vv)
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

            $class = 'tl_tbox';
            $fs = $this->Session->get('fieldset_states');
            $blnIsFirst = true;

            // Render boxes
            foreach ($boxes as $k=>$v)
            {
                $strAjax = '';
                $blnAjax = false;
                $key = '';
                $cls = '';
                $legend = '';

                if (isset($legends[$k]))
                {
                    list($key, $cls) = explode(':', $legends[$k]);
                    $legend = "\n" . '<div class="collapsible-header '.($cls == 'hide' ? '' : 'active').'" onclick="AjaxRequest.toggleFieldset(this,\'' . $key . '\',\'' . $this->strTable . '\')">' . (isset($GLOBALS['TL_LANG'][$this->strTable][$key]) ? $GLOBALS['TL_LANG'][$this->strTable][$key] : $key) . '</div><div class="collapsible-body">';
                }

                /*if (isset($fs[$this->strTable][$key]))
                {
                    $class .= ($fs[$this->strTable][$key] ? '' : ' collapsed');
                }
                else
                {
                    $class .= (($cls && $legend) ? ' ' . $cls : '');
                }*/

                $return .= "\n\n" . '<li' . ($key ? ' id="pal_'.$key.'"' : '') . ' class="' . $class . ($legend ? '' : ' nolegend') . '">' . $legend;

                // Build rows of the current box
                foreach ($v as $vv)
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

                    // Autofocus the first field
                    if ($blnIsFirst && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] == 'text')
                    {
                        $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['autofocus'] = 'autofocus';
                        $blnIsFirst = false;
                    }

                    // Convert CSV fields (see #2890)
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'] && isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv']))
                    {
                        $this->varValue = trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'], $this->varValue);
                    }

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
                    }

                    // Re-set the current value
                    $this->objActiveRecord->{$this->strField} = $this->varValue;

                    // Build the row and pass the current palette string (thanks to Tristan Lins)
                    $blnAjax ? $strAjax .= $this->row($this->strPalette) : $return .= $this->row($this->strPalette);
                }

                $class = 'tl_box';
                $return .= "\n" . '</div></li>';
            }
        }

        // Versions overview
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'])
        {
            $version = $objVersions->renderDropdown();
        }
        else
        {
            $version = '';
        }

        // Submit buttons
        $arrButtons = array();
        $arrButtons['save'] = '<input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['save']).'">';

        if (!\Input::get('nb'))
        {
            $arrButtons['saveNclose'] = '<input type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']).'">';
        }

        if (!\Input::get('popup') && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'])
        {
            $arrButtons['saveNcreate'] = '<input type="submit" name="saveNcreate" id="saveNcreate" class="tl_submit" accesskey="n" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNcreate']).'">';
        }

        if (\Input::get('s2e'))
        {
            $arrButtons['saveNedit'] = '<input type="submit" name="saveNedit" id="saveNedit" class="tl_submit" accesskey="e" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNedit']).'">';
        }
        elseif (!\Input::get('popup') && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4 || strlen($this->ptable) || $GLOBALS['TL_DCA'][$this->strTable]['config']['switchToEdit']))
        {
            $arrButtons['saveNback'] = '<input type="submit" name="saveNback" id="saveNback" class="tl_submit" accesskey="g" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNback']).'">';
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

        // Add the buttons and end the form
        $return .= '
</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  ' . implode(' ', $arrButtons) . '
</div>

</div>
</form>

<script>
  window.addEvent(\'domready\', function() {
    Theme.focusInput("'.$this->strTable.'");
  });
</script>';

        // Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
        $return = $version . '
<div id="tl_buttons">' . (\Input::get('nb') ? '&nbsp;' : '
<a href="'.$this->getReferer(true).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>') . '
</div>
'.\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form" method="post" enctype="' . ($this->blnUploadable ? 'multipart/form-data' : 'application/x-www-form-urlencoded') . '"'.(!empty($this->onsubmit) ? ' onsubmit="'.implode(' ', $this->onsubmit).'"' : '').'>
<ul class="collapsible" data-collapsible="expandable">
<input type="hidden" name="FORM_SUBMIT" value="'.specialchars($this->strTable).'">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
<input type="hidden" name="FORM_FIELDS[]" value="'.specialchars($this->strPalette).'">'.($this->noReload ? '

<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['general'].'</p>' : '').$return;

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

            // Save the current version
            if ($this->blnCreateNewVersion)
            {
                $objVersions->create();

                // Call the onversion_callback
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onversion_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onversion_callback'] as $callback)
                    {
                        if (is_array($callback))
                        {
                            $this->import($callback[0]);
                            $this->$callback[0]->$callback[1]($this->strTable, $this->intId, $this);
                        }
                        elseif (is_callable($callback))
                        {
                            $callback($this->strTable, $this->intId, $this);
                        }
                    }
                }

                $this->log('A new version of record "'.$this->strTable.'.id='.$this->intId.'" has been created'.$this->getParentEntries($this->strTable, $this->intId), __METHOD__, TL_GENERAL);
            }

            // Set the current timestamp (-> DO NOT CHANGE THE ORDER version - timestamp)
            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'])
            {
                $this->Database->prepare("UPDATE " . $this->strTable . " SET ptable=?, tstamp=? WHERE id=?")
                               ->execute($this->ptable, time(), $this->intId);
            }
            else
            {
                $this->Database->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE id=?")
                               ->execute(time(), $this->intId);
            }

            // Redirect
            if (isset($_POST['saveNclose']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);

                $this->redirect($this->getReferer());
            }
            elseif (isset($_POST['saveNedit']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);

                $strUrl = $this->addToUrl($GLOBALS['TL_DCA'][$this->strTable]['list']['operations']['edit']['href'], false);
                $strUrl = preg_replace('/(&amp;)?(s2e|act)=[^&]*/i', '', $strUrl);

                $this->redirect($strUrl);
            }
            elseif (isset($_POST['saveNback']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);

                if ($this->ptable == '')
                {
                    $this->redirect(TL_SCRIPT . '?do=' . \Input::get('do'));
                }
                // TODO: try to abstract this
                elseif (($this->ptable == 'tl_theme' && $this->strTable == 'tl_style_sheet') || ($this->ptable == 'tl_page' && $this->strTable == 'tl_article'))
                {
                    $this->redirect($this->getReferer(false, $this->strTable));
                }
                else
                {
                    $this->redirect($this->getReferer(false, $this->ptable));
                }
            }
            elseif (isset($_POST['saveNcreate']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);

                $strUrl = TL_SCRIPT . '?do=' . \Input::get('do');

                if (isset($_GET['table']))
                {
                    $strUrl .= '&amp;table=' . \Input::get('table');
                }

                // Tree view
                if ($this->treeView)
                {
                    $strUrl .= '&amp;act=create&amp;mode=1&amp;pid=' . $this->intId;
                }

                // Parent view
                elseif ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4)
                {
                    $strUrl .= $this->Database->fieldExists('sorting', $this->strTable) ? '&amp;act=create&amp;mode=1&amp;pid=' . $this->intId . '&amp;id=' . $this->activeRecord->pid : '&amp;act=create&amp;mode=2&amp;pid=' . $this->activeRecord->pid;
                }

                // List view
                else
                {
                    $strUrl .= ($this->ptable != '') ? '&amp;act=create&amp;mode=2&amp;pid=' . CURRENT_ID : '&amp;act=create';
                }

                $this->redirect($strUrl . '&amp;rt=' . REQUEST_TOKEN);
            }

            $this->reload();
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

        // Store search value in the current session
        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            $session['search'][$this->strTable]['value'] = '';
            $session['search'][$this->strTable]['field'] = \Input::post('tl_field', true);

            // Make sure the regular expression is valid
            if (\Input::postRaw('tl_value') != '')
            {
                try
                {
                    $this->Database->prepare("SELECT * FROM " . $this->strTable . " WHERE " . \Input::post('tl_field', true) . " REGEXP ?")
                                   ->limit(1)
                                   ->execute(\Input::postRaw('tl_value'));

                    $session['search'][$this->strTable]['value'] = \Input::postRaw('tl_value');
                }
                catch (\Exception $e) {}
            }

            $this->Session->setData($session);
        }

        // Set the search value from the session
        elseif ($session['search'][$this->strTable]['value'] != '')
        {
            $strPattern = "CAST(%s AS CHAR) REGEXP ?";

            if (substr(\Config::get('dbCollation'), -3) == '_ci')
            {
                $strPattern = "LOWER(CAST(%s AS CHAR)) REGEXP LOWER(?)";
            }

            $fld = $session['search'][$this->strTable]['field'];

            if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$fld]['foreignKey']))
            {
                list($t, $f) = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$fld]['foreignKey']);
                $this->procedure[] = "(" . sprintf($strPattern, $fld) . " OR " . sprintf($strPattern, "(SELECT $f FROM $t WHERE $t.id={$this->strTable}.$fld)") . ")";
                $this->values[] = $session['search'][$this->strTable]['value'];
            }
            else
            {
                $this->procedure[] = sprintf($strPattern, $fld);
            }

            $this->values[] = $session['search'][$this->strTable]['value'];
        }

        $options_sorter = array();

        foreach ($searchFields as $field)
        {
            $option_label = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] ?: (is_array($GLOBALS['TL_LANG']['MSC'][$field]) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : $GLOBALS['TL_LANG']['MSC'][$field]);
            $options_sorter[utf8_romanize($option_label).'_'.$field] = '  <option value="'.specialchars($field).'"'.(($field == $session['search'][$this->strTable]['field']) ? ' selected="selected"' : '').'>'.$option_label.'</option>';
        }

        // Sort by option values
        $options_sorter = natcaseksort($options_sorter);
        $active = ($session['search'][$this->strTable]['value'] != '') ? true : false;

        return '

<div class="tl_search tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['MSC']['search'] . ':</strong>
<select name="tl_field" class="browser-default' . ($active ? ' active' : '') . '">
'.implode("\n", $options_sorter).'
</select>
<span> = </span>
<input type="search" name="tl_value" class="tl_text' . ($active ? ' active' : '') . '" value="'.specialchars($session['search'][$this->strTable]['value']).'">
</div>';
    }

    protected function sortMenu()
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] != 2 && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] != 4)
        {
            return '';
        }

        $sortingFields = array();

        // Get sorting fields
        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
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

        // Add PID to order fields
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 3 && $this->Database->fieldExists('pid', $this->strTable))
        {
            array_unshift($orderBy, 'pid');
        }

        // Set sorting from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters')
        {
            $strSort = \Input::post('tl_sort');

            // Validate the user input (thanks to aulmn) (see #4971)
            if (in_array($strSort, $sortingFields))
            {
                $session['sorting'][$this->strTable] = in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strSort]['flag'], array(2, 4, 6, 8, 10, 12)) ? "$strSort DESC" : $strSort;
                $this->Session->setData($session);
            }
        }

        // Overwrite the "orderBy" value with the session value
        elseif (strlen($session['sorting'][$this->strTable]))
        {
            $overwrite = preg_quote(preg_replace('/\s+.*$/', '', $session['sorting'][$this->strTable]), '/');
            $orderBy = array_diff($orderBy, preg_grep('/^'.$overwrite.'/i', $orderBy));

            array_unshift($orderBy, $session['sorting'][$this->strTable]);

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

            $options_sorter[$options_label] = '  <option value="'.specialchars($field).'"'.((!strlen($session['sorting'][$this->strTable]) && $field == $firstOrderBy || $field == str_replace(' DESC', '', $session['sorting'][$this->strTable])) ? ' selected="selected"' : '').'>'.$options_label.'</option>';
        }

        // Sort by option values
        uksort($options_sorter, 'strcasecmp');

        return '

<div class="tl_sorting tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['MSC']['sortBy'] . ':</strong>
<select name="tl_sort" id="tl_sort" class="browser-default">
'.implode("\n", $options_sorter).'
</select>
</div>';
    }

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
            $this->reload();
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

<div class="tl_submit_panel tl_subpanel">
<input type="image" name="filter" id="filter" src="' . TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/reload.gif" class="tl_img_submit" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '" alt="' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '">
</div>';
            }

            $return .= '
<div class="tl_panel">' . $submit . $arrPanels[$i] . '

<div class="clear"></div>

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

    protected function limitMenu($blnOptional=false)
    {
        $session = $this->Session->getData();
        $filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : $this->strTable;
        $fields = '';

        // Set limit from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters' || \Input::post('FORM_SUBMIT') == 'tl_filters_limit')
        {
            $strLimit = \Input::post('tl_limit');

            if ($strLimit == 'tl_limit')
            {
                unset($session['filter'][$filter]['limit']);
            }
            else
            {
                // Validate the user input (thanks to aulmn) (see #4971)
                if ($strLimit == 'all' || preg_match('/^[0-9]+,[0-9]+$/', $strLimit))
                {
                    $session['filter'][$filter]['limit'] = $strLimit;
                }
            }

            $this->Session->setData($session);

            if (\Input::post('FORM_SUBMIT') == 'tl_filters_limit')
            {
                $this->reload();
            }
        }

        // Set limit from table configuration
        else
        {
            $this->limit = ($session['filter'][$filter]['limit'] != '') ? (($session['filter'][$filter]['limit'] == 'all') ? null : $session['filter'][$filter]['limit']) : '0,' . \Config::get('resultsPerPage');

            $arrProcedure = $this->procedure;
            $arrValues = $this->values;
            $query = "SELECT COUNT(*) AS count FROM " . $this->strTable;

            if (!empty($this->root) && is_array($this->root))
            {
                $arrProcedure[] = 'id IN(' . implode(',', $this->root) . ')';
            }

            // Support empty ptable fields (backwards compatibility)
            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'])
            {
                $arrProcedure[] = ($this->ptable == 'tl_article') ? "(ptable=? OR ptable='')" : "ptable=?";
                $arrValues[] = $this->ptable;
            }

            if (!empty($arrProcedure))
            {
                $query .= " WHERE " . implode(' AND ', $arrProcedure);
            }

            $objTotal = $this->Database->prepare($query)->execute($arrValues);
            $this->total = $objTotal->count;
            $options_total = 0;
            $blnIsMaxResultsPerPage = false;

            // Overall limit
            if ($this->total > \Config::get('maxResultsPerPage') && ($this->limit === null || preg_replace('/^.*,/', '', $this->limit) == \Config::get('maxResultsPerPage')))
            {
                if ($this->limit === null)
                {
                    $this->limit = '0,' . \Config::get('maxResultsPerPage');
                }

                $blnIsMaxResultsPerPage = true;
                \Config::set('resultsPerPage', \Config::get('maxResultsPerPage'));
                $session['filter'][$filter]['limit'] = \Config::get('maxResultsPerPage');
            }

            $options = '';

            // Build options
            if ($this->total > 0)
            {
                $options = '';
                $options_total = ceil($this->total / \Config::get('resultsPerPage'));

                // Reset limit if other parameters have decreased the number of results
                if ($this->limit !== null && ($this->limit == '' || preg_replace('/,.*$/', '', $this->limit) > $this->total))
                {
                    $this->limit = '0,'.\Config::get('resultsPerPage');
                }

                // Build options
                for ($i=0; $i<$options_total; $i++)
                {
                    $this_limit = ($i*\Config::get('resultsPerPage')).','.\Config::get('resultsPerPage');
                    $upper_limit = ($i*\Config::get('resultsPerPage')+\Config::get('resultsPerPage'));

                    if ($upper_limit > $this->total)
                    {
                        $upper_limit = $this->total;
                    }

                    $options .= '
  <option value="'.$this_limit.'"' . \Widget::optionSelected($this->limit, $this_limit) . '>'.($i*\Config::get('resultsPerPage')+1).' - '.$upper_limit.'</option>';
                }

                if (!$blnIsMaxResultsPerPage)
                {
                    $options .= '
  <option value="all"' . \Widget::optionSelected($this->limit, null) . '>'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</option>';
                }
            }

            // Return if there is only one page
            if ($blnOptional && ($this->total < 1 || $options_total < 2))
            {
                return '';
            }

            $fields = '
<select name="tl_limit" class="browser-default' . (($session['filter'][$filter]['limit'] != 'all' && $this->total > \Config::get('resultsPerPage')) ? ' active' : '') . '" onchange="this.form.submit()">
  <option value="tl_limit">'.$GLOBALS['TL_LANG']['MSC']['filterRecords'].'</option>'.$options.'
</select> ';
        }

        return '

<div class="tl_limit tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['MSC']['showOnly'] . ':</strong> '.$fields.'
</div>';
    }
}