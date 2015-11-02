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
class DC_Table extends \Contao\DC_Table
{
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

            $class = '';
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

                $class = '';
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
            $arrButtons['saveNedit'] = '<button type="submit" name="saveNedit" id="saveNedit" class="btn-flat orange-text text-lighten-2" accesskey="e">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNedit']).'</button>';
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

        // Add the buttons and end the form
        $return .= '

        <div class="card-action">
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

    protected function panel()
    {
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] == '')
        {
            return '';
        }

        $intFilterPanel = 0;
        $arrPanels = array();

        $panels = '';
        $arrSubPanelsUnsorted = trimsplit(',', str_replace(';', ',', $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout']));
        $arrSubPanels = ['sort', 'filter', 'search', 'limit'];

        foreach ($arrSubPanels as $kSubPanel => $strSubPanel)
        {
            if (!in_array($strSubPanel, $arrSubPanelsUnsorted))
            {
                unset($arrSubPanels[$kSubPanel]);
            }
        }

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

                <div class="submit-panel subpanel card-action js-subpanel" id="submit-subpanel">
                <button type="submit" class="btn waves-effect grey lighten-5 black-text" data-position="top" data-delay="50" data-tooltip="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '"><i class="material-icons left">refresh</i> ' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '</button>
                </div>';
            }

            $return .= '
            <div class="panel">' . $arrPanels[$i] . $submit . '

            </div>';
        }

        $return = '
        <form action="'.ampersand(\Environment::get('request'), true).'" class="tl_form filters-form" method="post">
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
            <div class="col m4 l3">
            <select name="tl_limit" class="' . (($session['filter'][$filter]['limit'] != 'all' && $this->total > \Config::get('resultsPerPage')) ? ' active' : '') . '" onchange="this.form.submit()">
            <option value="tl_limit">'.$GLOBALS['TL_LANG']['MSC']['filterRecords'].'</option>'.$options.'
            </select>
            </div> ';
        }

        return '

        <div class="limit-panel subpanel card-action row js-subpanel" id="limit-subpanel">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['showOnly'] . ':</strong></div> '.$fields.'
        </div>';
    }

    /**
     * List all records of the current table and return them as HTML string
     *
     * @return string
     */
    protected function listView()
    {
        $return = '';
        $table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->ptable : $this->strTable;
        $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
        $firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

        if (is_array($this->orderBy) && $this->orderBy[0] != '')
        {
            $orderBy = $this->orderBy;
            $firstOrderBy = $this->firstOrderBy;
        }

        $query = "SELECT * FROM " . $this->strTable;

        if (!empty($this->procedure))
        {
            $query .= " WHERE " . implode(' AND ', $this->procedure);
        }

        if (!empty($this->root) && is_array($this->root))
        {
            $query .= (!empty($this->procedure) ? " AND " : " WHERE ") . "id IN(" . implode(',', array_map('intval', $this->root)) . ")";
        }

        if (is_array($orderBy) && $orderBy[0] != '')
        {
            foreach ($orderBy as $k=>$v)
            {
                list($key, $direction) = explode(' ', $v, 2);

                if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['eval']['findInSet'])
                {
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback']))
                    {
                        $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'][0];
                        $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'][1];

                        $this->import($strClass);
                        $keys = $this->$strClass->$strMethod($this);
                    }
                    elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback']))
                    {
                        $keys = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback']($this);
                    }
                    else
                    {
                        $keys = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options'];
                    }

                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['eval']['isAssociative'] || array_is_assoc($keys))
                    {
                        $keys = array_keys($keys);
                    }

                    $orderBy[$k] = $this->Database->findInSet($v, $keys);
                }
                elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['flag'], array(5, 6, 7, 8, 9, 10)))
                {
                    $orderBy[$k] = "CAST($key AS SIGNED)" . ($direction ? " $direction" : ""); // see #5503
                }
            }

            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 3)
            {
                $firstOrderBy = 'pid';
                $showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];

                $query .= " ORDER BY (SELECT " . $showFields[0] . " FROM " . $this->ptable . " WHERE " . $this->ptable . ".id=" . $this->strTable . ".pid), " . implode(', ', $orderBy);

                // Set the foreignKey so that the label is translated (also for backwards compatibility)
                if ($GLOBALS['TL_DCA'][$table]['fields']['pid']['foreignKey'] == '')
                {
                    $GLOBALS['TL_DCA'][$table]['fields']['pid']['foreignKey'] = $this->ptable . '.' . $showFields[0];
                }

                // Remove the parent field from label fields
                array_shift($showFields);
                $GLOBALS['TL_DCA'][$table]['list']['label']['fields'] = $showFields;
            }
            else
            {
                $query .= " ORDER BY " . implode(', ', $orderBy);
            }
        }

        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 1 && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] % 2) == 0)
        {
            $query .= " DESC";
        }

        $objRowStmt = $this->Database->prepare($query);

        if ($this->limit != '')
        {
            $arrLimit = explode(',', $this->limit);
            $objRowStmt->limit($arrLimit[1], $arrLimit[0]);
        }

        $objRow = $objRowStmt->execute($this->values);
        $this->bid = ($return != '') ? $this->bid : 'tl_buttons';

        // Display buttos
        if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] || !empty($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations']))
        {
            $return .= '

            <div class="card-action" id="'.$this->bid.'">'.((\Input::get('act') == 'select' || $this->ptable) ? '
            <a href="'.$this->getReferer(true, $this->ptable).'" class="header_back" data-position="top" data-delay="50" data-tooltip"'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']) ? '
            <a href="contao/main.php?'.$GLOBALS['TL_DCA'][$this->strTable]['config']['backlink'].'" class="header_back" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : '')) . ((\Input::get('act') != 'select') ? '
            '.((!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? '<a href="'.(($this->ptable != '') ? $this->addToUrl('act=create' .
            (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] < 4) ? '&amp;mode=2' : '') . '&amp;pid=' . $this->intId) : $this->addToUrl('act=create')).'" class="header-new btn-floating btn-large waves-effect waves-light red tooltipped" data-position="left" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()"><i class="material-icons">add</i></a> ' : '') . $this->generateGlobalButtons() : '') .
            '</div>' . \Message::generate(true);
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

            <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form'.((\Input::get('act') == 'select') ? ' unselectable' : '').'" method="post" novalidate>
            <div class="tl_formbody">
            <input type="hidden" name="FORM_SUBMIT" value="tl_select">
            <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').'

            <div class="listing-container list_view">'.((\Input::get('act') == 'select') ? '

            <div class="select-trigger">
            <input type="checkbox" id="select-trigger" onclick="Backend.toggleCheckboxes(this)" class="tree-checkbox">
            <label for="select-trigger" class="select-trigger-label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label>
            </div>' : '').'

            <table class="listing' . ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ? ' showColumns' : '') . ' bordered highlight responsive-table">';

            // Automatically add the "order by" field as last column if we do not have group headers
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'])
            {
                $blnFound = false;

                // Extract the real key and compare it to $firstOrderBy
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] as $f)
                {
                    if (strpos($f, ':') !== false)
                    {
                        list($f,) = explode(':', $f, 2);
                    }

                    if ($firstOrderBy == $f)
                    {
                        $blnFound = true;
                        break;
                    }
                }

                if (!$blnFound)
                {
                    $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][] = $firstOrderBy;
                }
            }

            // Generate the table header if the "show columns" option is active
            if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'])
            {
                $return .= '
                <tr>';

                foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] as $f)
                {
                    if (strpos($f, ':') !== false)
                    {
                        list($f,) = explode(':', $f, 2);
                    }

                    $return .= '
                    <th class="row-headline col-' . $f . (($f == $firstOrderBy) ? ' ordered-by' : '') . '">'.(is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label']).'</th>';
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
                $args = array();
                $this->current[] = $row['id'];
                $showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];

                // Label
                foreach ($showFields as $k=>$v)
                {
                    // Decrypt the value
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['encrypt'])
                    {
                        $row[$v] = \Encryption::decrypt(deserialize($row[$v]));
                    }

                    if (strpos($v, ':') !== false)
                    {
                        list($strKey, $strTable) = explode(':', $v);
                        list($strTable, $strField) = explode('.', $strTable);

                        $objRef = $this->Database->prepare("SELECT " . $strField . " FROM " . $strTable . " WHERE id=?")
                                                 ->limit(1)
                                                 ->execute($row[$strKey]);

                        $args[$k] = $objRef->numRows ? $objRef->$strField : '';
                    }
                    elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
                    {
                        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['rgxp'] == 'date')
                        {
                            $args[$k] = $row[$v] ? \Date::parse(\Config::get('dateFormat'), $row[$v]) : '-';
                        }
                        elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['rgxp'] == 'time')
                        {
                            $args[$k] = $row[$v] ? \Date::parse(\Config::get('timeFormat'), $row[$v]) : '-';
                        }
                        else
                        {
                            $args[$k] = $row[$v] ? \Date::parse(\Config::get('datimFormat'), $row[$v]) : '-';
                        }
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['multiple'])
                    {
                        $args[$k] = ($row[$v] != '') ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : '';
                    }
                    else
                    {
                        $row_v = deserialize($row[$v]);

                        if (is_array($row_v))
                        {
                            $args_k = array();

                            foreach ($row_v as $option)
                            {
                                $args_k[] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$option] ?: $option;
                            }

                            $args[$k] = implode(', ', $args_k);
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
                            $args[$k] = $row[$v];
                        }
                    }
                }

                // Shorten the label it if it is too long
                $label = vsprintf($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['format'] ?: '%s', $args);

                if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'] < strlen(strip_tags($label)))
                {
                    $label = trim(\StringUtil::substrHtml($label, $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['maxCharacters'])) . ' …';
                }

                // Remove empty brackets (), [], {}, <> and empty tags from the label
                $label = preg_replace('/\( *\) ?|\[ *\] ?|\{ *\} ?|< *> ?/', '', $label);
                $label = preg_replace('/<[^>]+>\s*<\/[^>]+>/', '', $label);

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
                <tr class="'.((++$eoCount % 2 == 0) ? 'even' : 'odd').' click2edit toggle-select" onmouseover="Theme.hoverRow(this,1)" onmouseout="Theme.hoverRow(this,0)">
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
                    foreach ($args as $j=>$arg)
                    {
                        $return .= '<td colspan="' . $colspan . '" class="item col-' . $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][$j] . (($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][$j] == $firstOrderBy) ? ' ordered-by' : '') . '">' . ($arg ?: '-') . '</td>';
                    }
                }
                else
                {
                    $return .= '<td class="item">' . $label . '</td>';
                }

                // Buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
                $return .= ((\Input::get('act') == 'select') ? '
                <td class="item actions -select"><input type="checkbox" name="IDS[]" id="ids_'.$row['id'].'" class="tree-checkbox" value="'.$row['id'].'"><label for="ids_'.$row['id'].'"></label></td>' : '
                <td class="item actions">'.$this->generateButtons($row, $this->strTable, $this->root).'</td>') . '
                </tr>';
            }

            // Close the table
            $return .= '
            </table>

            </div>';

            // Close the form
            if (\Input::get('act') == 'select')
            {
                // Submit buttons
                $arrButtons = array();

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
                {
                    $arrButtons['delete'] = '<button type="submit" name="delete" id="delete" class="btn-flat" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')" >'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'</button>';
                }

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'])
                {
                    $arrButtons['copy'] = '<button type="submit" name="copy" id="copy" class="btn-flat" accesskey="c" >'.specialchars($GLOBALS['TL_LANG']['MSC']['copySelected']).'</button>';
                }

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
                {
                    $arrButtons['override'] = '<button type="submit" name="override" id="override" class="btn-flat" accesskey="v" >'.specialchars($GLOBALS['TL_LANG']['MSC']['overrideSelected']).'</button>';
                    $arrButtons['edit'] = '<button type="submit" name="edit" id="edit" class="btn" accesskey="s" >'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'</button>';
                }

                // Call the buttons_callback (see #4691)
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback)
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

                $return .= '

                <div class="card-action">

                <div class="submit-container">
                ' . implode(' ', array_reverse($arrButtons)) . '
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
     * List all records of the current table as tree and return them as HTML string
     *
     * @return string
     */
    protected function treeView()
    {
        $table = $this->strTable;
        $treeClass = 'tree';

        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6)
        {
            $table = $this->ptable;
            $treeClass = 'tree-extended';

            \System::loadLanguageFile($table);
            $this->loadDataContainer($table);
        }

        $session = $this->Session->getData();

        // Toggle the nodes
        if (\Input::get('ptg') == 'all')
        {
            $node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

            // Expand tree
            if (!is_array($session[$node]) || empty($session[$node]) || current($session[$node]) != 1)
            {
                $session[$node] = array();
                $objNodes = $this->Database->execute("SELECT DISTINCT pid FROM " . $table . " WHERE pid>0");

                while ($objNodes->next())
                {
                    $session[$node][$objNodes->pid] = 1;
                }
            }

            // Collapse tree
            else
            {
                $session[$node] = array();
            }

            $this->Session->setData($session);
            $this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
        }

        // Return if a mandatory field (id, pid, sorting) is missing
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && (!$this->Database->fieldExists('id', $table) || !$this->Database->fieldExists('pid', $table) || !$this->Database->fieldExists('sorting', $table)))
        {
            return \Message::parseMessage(\Message::getCssClass('tl_info'), 'Table "'.$table.'" can not be shown as tree, because the "id", "pid" or "sorting" field is missing!');
        }

        // Return if there is no parent table
        if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6 && !strlen($this->ptable))
        {
            return \Message::parseMessage(\Message::getCssClass('tl_info'), 'Table "'.$table.'" can not be shown as extended tree, because there is no parent table!');
        }

        $blnClipboard = false;
        $arrClipboard = $this->Session->get('CLIPBOARD');

        // Check the clipboard
        if (!empty($arrClipboard[$this->strTable]))
        {
            $blnClipboard = true;
            $arrClipboard = $arrClipboard[$this->strTable];
        }

        // Load the fonts to display the paste hint
        \Config::set('loadGoogleFonts', $blnClipboard);

        $label = $GLOBALS['TL_DCA'][$table]['config']['label'];
        $icon = $GLOBALS['TL_DCA'][$table]['list']['sorting']['icon'] ?: 'pagemounts.gif';
        $label = \Image::getHtml($icon).' <label>'.$label.'</label>';

        // Begin buttons container
        $return = '
        <div class="card-action" id="tl_buttons">'.((\Input::get('act') == 'select') ? '
        <a href="'.$this->getReferer(true).'" class="header_back" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']) ? '
        <a href="contao/main.php?'.$GLOBALS['TL_DCA'][$this->strTable]['config']['backlink'].'" class="header_back" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : '')) .
        ((\Input::get('act') != 'select' && !$blnClipboard && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? '
        <a href="'.$this->addToUrl('act=paste&amp;mode=create').'" class="header-new btn-floating btn-large waves-effect waves-light red tooltipped" data-position="left" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()"><i class="material-icons">add</i></a> ' : '') .
        ((\Input::get('act') != 'select' && !$blnClipboard) ? $this->generateGlobalButtons() : '') . ($blnClipboard ? '<a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard" data-position="top" data-delay="50" data-tooltip="'.
        specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ' : '') . '
        </div>' . \Message::generate(true);

        $tree = '';
        $blnHasSorting = $this->Database->fieldExists('sorting', $table);
        $blnNoRecursion = false;

        // Limit the results by modifying $this->root
        if ($session['search'][$this->strTable]['value'] != '')
        {
            $for = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? 'pid' : 'id';

            if ($session['search'][$this->strTable]['field'] == 'id')
            {
                $objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE id=?")
                                          ->execute($session['search'][$this->strTable]['value']);
            }
            else
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

                    $objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE (" . sprintf($strPattern, $fld) . " OR " . sprintf($strPattern, "(SELECT $f FROM $t WHERE $t.id={$this->strTable}.$fld)") . ") GROUP BY $for")
                                              ->execute($session['search'][$this->strTable]['value'], $session['search'][$this->strTable]['value']);
                }
                else
                {
                    $objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE " . sprintf($strPattern, $fld) . " GROUP BY $for")
                                              ->execute($session['search'][$this->strTable]['value']);
                }
            }

            if ($objRoot->numRows < 1)
            {
                $this->root = array();
            }
            else
            {
                // Respect existing limitations (root IDs)
                if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']))
                {
                    $arrRoot = array();

                    while ($objRoot->next())
                    {
                        if (count(array_intersect($this->root, $this->Database->getParentRecords($objRoot->$for, $table))) > 0)
                        {
                            $arrRoot[] = $objRoot->$for;
                        }
                    }

                    $this->root = $arrRoot;
                }
                else
                {
                    $blnNoRecursion = true;
                    $this->root = $objRoot->fetchEach($for);
                }
            }
        }

        // Call a recursive function that builds the tree
        for ($i=0, $c=count($this->root); $i<$c; $i++)
        {
            $tree .= $this->generateTree($table, $this->root[$i], array('p'=>$this->root[($i-1)], 'n'=>$this->root[($i+1)]), $blnHasSorting, -20, ($blnClipboard ? $arrClipboard : false), ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && $this->root[$i] == $arrClipboard['id']), false, $blnNoRecursion);
        }

        // Return if there are no records
        if ($tree == '' && \Input::get('act') != 'paste')
        {
            return $return . \Message::parseMessage(\Message::getCssClass('tl_info'), $GLOBALS['TL_LANG']['MSC']['noResult']);
        }

        $return .= '<div class="card-content">';

        $return .= ((\Input::get('act') == 'select') ? '

        <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="form'.((\Input::get('act') == 'select') ? ' unselectable' : '').'" method="post" novalidate>
        <div class="formbody">
        <input type="hidden" name="FORM_SUBMIT" value="tl_select">
        <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').($blnClipboard ? '

        <div id="paste_hint">
        <p>'.$GLOBALS['TL_LANG']['MSC']['selectNewPosition'].'</p>
        </div>' : '').'

        <div class="listing-container tree_view" id="listing">'.(isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['breadcrumb']) ? $GLOBALS['TL_DCA'][$table]['list']['sorting']['breadcrumb'] : '').((\Input::get('act') == 'select') ? '

        <div class="select-trigger">
        <label for="tl_select_trigger" class="select-trigger-label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="select-trigger" onclick="Backend.toggleCheckboxes(this)" class="tree-checkbox">
        </div>' : '').'

        <ul class="listing '. $treeClass .' collapsible" data-collapsible="expandable">
        <li class="row-top"><div class="item">'.$label.'</div> <div class="actions">';

        $_buttons = '&nbsp;';

        // Show paste button only if there are no root records specified
        if (\Input::get('act') != 'select' && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && ((!count($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] !== false) || $GLOBALS['TL_DCA'][$table]['list']['sorting']['rootPaste']))
        {
            // Call paste_button_callback (&$dc, $row, $table, $cr, $childs, $previous, $next)
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
            {
                $strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
                $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

                $this->import($strClass);
                $_buttons = $this->$strClass->$strMethod($this, array('id'=>0), $table, false, $arrClipboard);
            }
            elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
            {
                $_buttons = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']($this, array('id'=>0), $table, false, $arrClipboard);
            }
            else
            {
                $imagePasteInto = \Image::getHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);
                $_buttons = '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid=0'.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
            }
        }

        // End table
        $return .= $_buttons . '</div></li>'.$tree.'
        </ul>

        </div>';

        // Close the form
        if (\Input::get('act') == 'select')
        {
            // Submit buttons
            $arrButtons = array();

            if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
            {
                $arrButtons['delete'] = '<button type="submit" name="delete" id="delete" class="btn-flat" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')">'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'</button>';
            }

            if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'])
            {
                $arrButtons['cut'] = '<button type="submit" name="cut" id="cut" class="btn-flat" accesskey="x">'.specialchars($GLOBALS['TL_LANG']['MSC']['moveSelected']).'</button>';
            }

            if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'])
            {
                $arrButtons['copy'] = '<button type="submit" name="copy" id="copy" class="btn-flat" accesskey="c">'.specialchars($GLOBALS['TL_LANG']['MSC']['copySelected']).'</button>';
            }

            if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
            {
                $arrButtons['override'] = '<button type="submit" name="override" id="override" class="btn-flat" accesskey="v">'.specialchars($GLOBALS['TL_LANG']['MSC']['overrideSelected']).'</button>';
                $arrButtons['edit'] = '<button type="submit" name="edit" id="edit" class="btn" accesskey="s">'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'</button>';
            }

            $arrButtons = array_reverse($arrButtons);

            // Call the buttons_callback (see #4691)
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback)
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

            $return .= '

            <div class="card-action">

            <div class="submit-container">
            ' . implode(' ', $arrButtons) . '
            </div>

            </div>
            </div>
            </form>';
        }

        $return .= '</div>';

        return $return;
    }

    /**
	 * Generate the filter panel and return it as HTML string
	 *
	 * @param integer $intFilterPanel
	 *
	 * @return string
	 */
	protected function filterMenu($intFilterPanel)
	{
		$fields = '';
		$this->bid = 'tl_buttons_a';
		$sortingFields = array();
		$session = $this->Session->getData();
		$filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4) ? $this->strTable.'_'.CURRENT_ID : $this->strTable;

		// Get the sorting fields
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
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

			$this->Session->setData($session);
		}

		// Set filter from table configuration
		else
		{
			foreach ($sortingFields as $field)
			{
				if (isset($session['filter'][$filter][$field]))
				{
					// Sort by day
					if (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(5, 6)))
					{
						if ($session['filter'][$filter][$field] == '')
						{
							$this->procedure[] = $field . "=''";
						}
						else
						{
							$objDate = new \Date($session['filter'][$filter][$field]);
							$this->procedure[] = $field . ' BETWEEN ? AND ?';
							$this->values[] = $objDate->dayBegin;
							$this->values[] = $objDate->dayEnd;
						}
					}

					// Sort by month
					elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(7, 8)))
					{
						if ($session['filter'][$filter][$field] == '')
						{
							$this->procedure[] = $field . "=''";
						}
						else
						{
							$objDate = new \Date($session['filter'][$filter][$field]);
							$this->procedure[] = $field . ' BETWEEN ? AND ?';
							$this->values[] = $objDate->monthBegin;
							$this->values[] = $objDate->monthEnd;
						}
					}

					// Sort by year
					elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(9, 10)))
					{
						if ($session['filter'][$filter][$field] == '')
						{
							$this->procedure[] = $field . "=''";
						}
						else
						{
							$objDate = new \Date($session['filter'][$filter][$field]);
							$this->procedure[] = $field . ' BETWEEN ? AND ?';
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
							$this->procedure[] = $this->Database->findInSet('?', $field, true);
							$this->values[] = $session['filter'][$filter][$field];
						}
						else
						{
							$this->procedure[] = $field . ' LIKE ?';
							$this->values[] = '%"' . $session['filter'][$filter][$field] . '"%';
						}
					}

					// Other sort algorithm
					else
					{
						$this->procedure[] = $field . '=?';
						$this->values[] = $session['filter'][$filter][$field];
					}
				}
			}
		}

		// Add sorting options
		foreach ($sortingFields as $cnt=>$field)
		{
			$arrValues = array();
			$arrProcedure = array();

			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 4)
			{
				$arrProcedure[] = 'pid=?';
				$arrValues[] = CURRENT_ID;
			}

			if (!empty($this->root) && is_array($this->root))
			{
				$arrProcedure[] = "id IN(" . implode(',', array_map('intval', $this->root)) . ")";
			}

			// Check for a static filter (see #4719)
			if (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']) && is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter'] as $fltr)
				{
					$arrProcedure[] = $fltr[0];
					$arrValues[] = $fltr[1];
				}
			}

			// Support empty ptable fields (backwards compatibility)
			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'])
			{
				$arrProcedure[] = ($this->ptable == 'tl_article') ? "(ptable=? OR ptable='')" : "ptable=?";
				$arrValues[] = $this->ptable;
			}

			$objFields = $this->Database->prepare("SELECT DISTINCT " . $field . " FROM " . $this->strTable . ((is_array($arrProcedure) && strlen($arrProcedure[0])) ? ' WHERE ' . implode(' AND ', $arrProcedure) : ''))
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

					foreach ($options as $k=>$v)
					{
						if ($v == '')
						{
							$options[$v] = '-';
						}
						else
						{
							$options[$v] = \Date::parse(\Config::get('dateFormat'), $v);
						}

						unset($options[$k]);
					}
				}

				// Sort by month
				elseif (in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(7, 8)))
				{
					($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] == 8) ? rsort($options) : sort($options);

					foreach ($options as $k=>$v)
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

					foreach ($options as $k=>$v)
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

					// TODO: find a more effective solution
					foreach($options as $option)
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
				foreach ($options as $kk=>$vv)
				{
					$value = $blnDate ? $kk : $vv;

					// Options callback
					if (!empty($options_callback) && is_array($options_callback))
					{
						$vv = $options_callback[$vv];
					}

					// Replace the ID with the foreign key
					elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey']))
					{
						$key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey'], 2);

						$objParent = $this->Database->prepare("SELECT " . $key[1] . " AS value FROM " . $key[0] . " WHERE id=?")
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

					// Get the name of the parent record (see #2703)
					elseif ($field == 'pid')
					{
						$this->loadDataContainer($this->ptable);
						$showFields = $GLOBALS['TL_DCA'][$this->ptable]['list']['label']['fields'];

						if (!$showFields[0])
						{
							$showFields[0] = 'id';
						}

						$objShowFields = $this->Database->prepare("SELECT " . $showFields[0] . " FROM ". $this->ptable . " WHERE id=?")
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
		}

		return '

        <div class="filter-panel subpanel card-action row js-subpanel" id="filter-subpanel">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['filter'] . ':</strong></div> ' . $fields . '
        </div>';
	}

    /**
	 * Return a search form that allows to search results using regular expressions
	 *
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

        <div class="search-panel subpanel card-action row js-subpanel" id="search-subpanel">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['search'] . ':</strong></div>
        <div class="col m4 l3">
        <select name="tl_field" class="tl_select' . ($active ? ' active' : '') . '">
        '.implode("\n", $options_sorter).'
        </select>
        </div>
        <div class="col"> = </div>
        <div class="col m4 l3">
        <input type="search" name="tl_value" class="tl_text' . ($active ? ' active' : '') . '" value="'.specialchars($session['search'][$this->strTable]['value']).'">
        </div>
        </div>';
	}

    /**
	 * Return a select menu that allows to sort results by a particular field
	 *
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

        <div class="sorting-panel subpanel card-action row js-subpanel" id="sorting-subpanel">
        <div class="col m12"><strong>' . $GLOBALS['TL_LANG']['MSC']['sortBy'] . ':</strong></div>
        <div class="col m4 l3">
        <select name="tl_sort" id="tl_sort" class="tl_select">
        '.implode("\n", $options_sorter).'
        </select>
        </div>
        </div>';
	}

    /**
	 * Recursively generate the tree and return it as HTML string
	 *
	 * @param string  $table
	 * @param integer $id
	 * @param array   $arrPrevNext
	 * @param boolean $blnHasSorting
	 * @param integer $intMargin
	 * @param array   $arrClipboard
	 * @param boolean $blnCircularReference
	 * @param boolean $protectedPage
	 * @param boolean $blnNoRecursion
	 *
	 * @return string
	 */
	protected function generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin=0, $arrClipboard=null, $blnCircularReference=false, $protectedPage=false, $blnNoRecursion=false)
	{
		static $session;

		$session = $this->Session->getData();
		$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

		// Toggle nodes
		if (\Input::get('ptg'))
		{
			$session[$node][\Input::get('ptg')] = (isset($session[$node][\Input::get('ptg')]) && $session[$node][\Input::get('ptg')] == 1) ? 0 : 1;
			$this->Session->setData($session);

			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objRow = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
								 ->limit(1)
								 ->execute($id);

		// Return if there is no result
		if ($objRow->numRows < 1)
		{
			$this->Session->setData($session);

			return '';
		}

		$return = '';
		$intSpacing = 20;
		$childs = array();

		// Add the ID to the list of current IDs
		if ($this->strTable == $table)
		{
			$this->current[] = $objRow->id;
		}

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $this->strTable != $table)
			{
				$objChilds = $this->Database->prepare("SELECT id FROM " . $table . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
											->execute($id);

				if ($objChilds->numRows)
				{
					$childs = $objChilds->fetchEach('id');
				}
			}
		}

		$blnProtected = false;

		// Check whether the page is protected
		if ($table == 'tl_page')
		{
			$blnProtected = ($objRow->protected || $protectedPage) ? true : false;
		}

		$session[$node][$id] = (is_int($session[$node][$id])) ? $session[$node][$id] : 0;
		$mouseover = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $table == $this->strTable) ? ' toggle-select"' : '"';

        $tableClass = str_replace(['tl_', '_'], ['', '-'], $table);

        // if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $objRow->type == 'root') || $table != $this->strTable)
        // {
            $return .= "\n  " . '<li class="row-container click2edit'.$mouseover.'><div class="collapsible-header"><div class="item -' . $tableClass . '">';
        // }
        // else
        // {
            // $return .= "\n  " . '<div class="item -' . $tableClass . '">';
        // }

		// Calculate label and add a toggle button
		$args = array();
		$folderAttribute = 'style="margin-left:20px"';
		$showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];
		$level = ($intMargin / $intSpacing + 1);

		if (!empty($childs))
		{
			$folderAttribute = '';
			$img = ($session[$node][$id] == 1) ? 'folMinus.gif' : 'folPlus.gif';
			$alt = ($session[$node][$id] == 1) ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			$return .= '<a href="'.$this->addToUrl('ptg='.$id).'" title="'.specialchars($alt).'" onclick="Backend.getScrollOffset();return AjaxRequest.toggleStructure(this,\''.$node.'_'.$id.'\','.$level.','.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'].')">'.\Image::getHtml($img, '', 'style="margin-right:2px"').'</a>';
		}

		foreach ($showFields as $k=>$v)
		{
			// Decrypt the value
			if ($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['encrypt'])
			{
				$objRow->$v = \Encryption::decrypt(deserialize($objRow->$v));
			}

			if (strpos($v, ':') !== false)
			{
				list($strKey, $strTable) = explode(':', $v);
				list($strTable, $strField) = explode('.', $strTable);

				$objRef = $this->Database->prepare("SELECT " . $strField . " FROM " . $strTable . " WHERE id=?")
										 ->limit(1)
										 ->execute($objRow->$strKey);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
			{
				$args[$k] = \Date::parse(\Config::get('datimFormat'), $objRow->$v);
			}
			elseif ($GLOBALS['TL_DCA'][$table]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['multiple'])
			{
				$args[$k] = ($objRow->$v != '') ? (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : $v) : '';
			}
			else
			{
				$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$objRow->$v] ?: $objRow->$v;
			}
		}

		$label = vsprintf(((strlen($GLOBALS['TL_DCA'][$table]['list']['label']['format'])) ? $GLOBALS['TL_DCA'][$table]['list']['label']['format'] : '%s'), $args);

		// Shorten the label if it is too long
		if ($GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] < utf8_strlen(strip_tags($label)))
		{
			$label = trim(\StringUtil::substrHtml($label, $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'])) . ' …';
		}

		$label = preg_replace('/\(\) ?|\[\] ?|\{\} ?|<> ?/', '', $label);

		// Call the label_callback ($row, $label, $this)
		if (is_array($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
		{
			$strClass = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][0];
			$strMethod = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][1];

			$this->import($strClass);
			$return .= $this->$strClass->$strMethod($objRow->row(), $label, $this, $folderAttribute, false, $blnProtected);
		}
		elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
		{
			$return .= $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']($objRow->row(), $label, $this, $folderAttribute, false, $blnProtected);
		}
		else
		{
			$return .= \Image::getHtml('iconPLAIN.gif', '') . ' ' . $label;
		}

		$return .= '</div> <div class="actions">';
		$previous = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['pp'] : $arrPrevNext['p'];
		$next = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['nn'] : $arrPrevNext['n'];
		$_buttons = '';

		// Regular buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
		if ($this->strTable == $table)
		{
			$_buttons .= (\Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_'.$id.'" class="tree-checkbox" value="'.$id.'">' : $this->generateButtons($objRow->row(), $table, $this->root, $blnCircularReference, $childs, $previous, $next);
		}

		// Paste buttons
		if ($arrClipboard !== false && \Input::get('act') != 'select')
		{
			$_buttons .= ' ';

			// Call paste_button_callback(&$dc, $row, $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
			{
				$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				$_buttons .= $this->$strClass->$strMethod($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next);
			}
			elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
			{
				$_buttons .= $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next);
			}
			else
			{
				$imagePasteAfter = \Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id));
				$imagePasteInto = \Image::getHtml('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id));

				// Regular tree (on cut: disable buttons of the page all its childs to avoid circular references)
				if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5)
				{
					$_buttons .= ($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id'])) || (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && !$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['rootPaste'] && in_array($id, $this->root))) ? \Image::getHtml('pasteafter_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
					$_buttons .= ($arrClipboard['mode'] == 'paste' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteinto_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
				}

				// Extended tree
				else
				{
					$_buttons .= ($this->strTable == $table) ? (($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteafter_.gif') : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ') : '';
					$_buttons .= ($this->strTable != $table) ? '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ' : '';
				}
			}
		}

        $return .= $_buttons . '</div>';

		// Begin a new submenu
		if (!$blnNoRecursion)
		{
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '</div><div class="collapsible-body" id="'.$node.'-'.$id.'"><ul class="level-'.$level.' collapsible" data-collapsible="expandable">';
			}

			// Add the records of the parent table
			if ($session[$node][$id] == 1)
			{
				if (is_array($childs))
				{
					for ($k=0, $c=count($childs); $k<$c; $k++)
					{
						$return .= $debug = $this->generateTree($table, $childs[$k], array('p'=>$childs[($k-1)], 'n'=>$childs[($k+1)]), $blnHasSorting, ($intMargin + $intSpacing), $arrClipboard,
                        ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $childs[$k] == $arrClipboard['id']) || $blnCircularReference) ? true : false), ($blnProtected || $protectedPage));
					}
				}
			}

			// Close the submenu
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '</ul>';

                if (!$arrPrevNext['n'])
                {
                    $return .=  '</div>';
                }
			}
		}

        if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] != 5 || $objRow->type != 'root') && $table == $this->strTable && !$arrPrevNext['nn'])
        {

            $return .=  '</div></li>';
        }

		// Add the records of the table itself
		if ($table != $this->strTable)
		{
			$objChilds = $this->Database->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))->execute($id);

			if ($objChilds->numRows)
			{
				$ids = $objChilds->fetchEach('id');

				for ($j=0, $c=count($ids); $j<$c; $j++)
				{
					$return .= $this->generateTree($this->strTable, $ids[$j], array('pp'=>$ids[($j-1)], 'nn'=>$ids[($j+1)]), $blnHasSorting, ($intMargin + $intSpacing + 20), $arrClipboard, false, ($j<(count($ids)-1) || !empty($childs)));
				}
			}
		}

		$this->Session->setData($session);

		return $return;
	}
}
