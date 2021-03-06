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
 * Provide methods to modify the file system.
 *
 * @property string  $path
 * @property string  $extension
 * @property boolean $createNewVersion
 * @property boolean $isDbAssisted
 *
 */
class DC_Folder extends \Contao\DC_Folder
{
    /**
     * List all files and folders of the file system
     *
     * @return string
     */
    public function showAll()
    {
        $return = '';

        // Add to clipboard
        if (\Input::get('act') == 'paste')
        {
            if (\Input::get('mode') != 'create' && \Input::get('mode') != 'move')
            {
                $this->isValid($this->intId);
            }

            $arrClipboard = $this->Session->get('CLIPBOARD');

            $arrClipboard[$this->strTable] = array
            (
                'id' => $this->urlEncode($this->intId),
                'childs' => \Input::get('childs'),
                'mode' => \Input::get('mode')
                );

            $this->Session->set('CLIPBOARD', $arrClipboard);
        }

        // Get the session data and toggle the nodes
        if (\Input::get('tg') == 'all')
        {
            $session = $this->Session->getData();

            // Expand tree
            if (!is_array($session['filetree']) || empty($session['filetree']) || current($session['filetree']) != 1)
            {
                $session['filetree'] = $this->getMD5Folders(\Config::get('uploadPath'));
            }
            // Collapse tree
            else
            {
                $session['filetree'] = array();
            }

            $this->Session->setData($session);
            $this->redirect(preg_replace('/(&(amp;)?|\?)tg=[^& ]*/i', '', \Environment::get('request')));
        }

        $blnClipboard = false;
        $arrClipboard = $this->Session->get('CLIPBOARD');

        // Check clipboard
        if (!empty($arrClipboard[$this->strTable]))
        {
            $blnClipboard = true;
            $arrClipboard = $arrClipboard[$this->strTable];
        }

        // Load the fonts to display the paste hint
        \Config::set('loadGoogleFonts', $blnClipboard);

        $this->import('Files');
        $this->import('BackendUser', 'User');

        // Call recursive function tree()
        if (empty($this->arrFilemounts) && !is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] !== false)
        {
            $return .= $this->generateTree(TL_ROOT . '/' . \Config::get('uploadPath'), 0, false, false, ($blnClipboard ? $arrClipboard : false));
        }
        else
        {
            for ($i=0, $c=count($this->arrFilemounts); $i<$c; $i++)
            {
                if ($this->arrFilemounts[$i] != '' && is_dir(TL_ROOT . '/' . $this->arrFilemounts[$i]))
                {
                    $return .= $this->generateTree(TL_ROOT . '/' . $this->arrFilemounts[$i], 0, true, false, ($blnClipboard ? $arrClipboard : false));
                }
            }
        }

        // Check for the "create new" button
        $clsNew = 'header_new_folder';
        $lblNew = $GLOBALS['TL_LANG'][$this->strTable]['new'][0];
        $ttlNew = $GLOBALS['TL_LANG'][$this->strTable]['new'][1];
        $hrfNew = '&amp;act=paste&amp;mode=create';

        if (isset($GLOBALS['TL_DCA'][$this->strTable]['list']['new']))
        {
            $clsNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['class'];
            $lblNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['label'][0];
            $ttlNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['label'][1];
            $hrfNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['href'];
        }

        $imagePasteInto = Helper::getIconHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);

        // Build the tree
        $return = '
        <div id="tl_buttons" class="card-action">'.((\Input::get('act') == 'select') ? '
            <a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5 data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">keyboard_backspace</i></a> ' : '') . ((\Input::get('act') != 'select' && !$blnClipboard && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? '
            <a href="'.$this->addToUrl($hrfNew).'" class="'.$clsNew.' tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars($ttlNew).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$lblNew.'</a>
            <a href="'.$this->addToUrl('&amp;act=paste&amp;mode=move').'" class="header-new btn-floating btn-large waves-effect waves-light green tooltipped" data-position="left" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['move'][1]).'" onclick="Backend.getScrollOffset()"><i class="material-icons">add</i></a> ' : '') . ($blnClipboard ? '
            <a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ' : $this->generateGlobalButtons()) . '
        </div>' . \Message::generate(true) . '<div class="card-content">' . ((\Input::get('act') == 'select') ? '

            <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form'.((\Input::get('act') == 'select') ? ' unselectable' : '').'" method="post" novalidate>
                <div class="tl_formbody">
                    <input type="hidden" name="FORM_SUBMIT" value="tl_select">
                    <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').($blnClipboard ? '

                    <div class="paste-hint">' . \Message::parseMessage(\Message::getCssClass('tl_info'), $GLOBALS['TL_LANG']['MSC']['selectNewPosition'] . '<i class="material-icons paste-hint-icon">arrow_downward</i>') . '</div>' : '').'

                    <div class="listing_container tree_view" id="listing">'.(isset($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['breadcrumb']) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['breadcrumb'] : '').((\Input::get('act') == 'select') ? '

                        <div class="select-trigger">
                            <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox"><label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label>
                        </div>' : '').'

                    <ul class="listing listing-files tree collapsible" data-collapsible="expandable">
                        <li class="row-top"><div class="item">'.Helper::getIconHtml('filemounts.gif').' <label>'.$GLOBALS['TL_LANG']['MSC']['filetree'].'</label></div> <div class="actions">'.(($blnClipboard && empty($this->arrFilemounts) && !is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] !== false) ? '<a href="'.$this->addToUrl('&amp;act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.\Config::get('uploadPath').(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" class="btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped paste-action -into" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a>' : '&nbsp;').'</div></li>'.$return.'
                    </ul>

              </div>';

        // Close the form
              if (\Input::get('act') == 'select')
              {
            // Submit buttons
                $arrButtons = array();

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
                {
                    $arrButtons['delete'] = '<input type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirmFile'].'\')" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'">';
                }

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'])
                {
                    $arrButtons['cut'] = '<input type="submit" name="cut" id="cut" class="tl_submit" accesskey="x" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['moveSelected']).'">';
                }

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'])
                {
                    $arrButtons['copy'] = '<input type="submit" name="copy" id="copy" class="tl_submit" accesskey="c" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['copySelected']).'">';
                }

                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
                {
                    $arrButtons['edit'] = '<input type="submit" name="edit" id="edit" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'">';
                }

            // Call the buttons_callback (see #4691)
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback)
                    {
                        if (is_array($callback))
                        {
                            $this->import($callback[0]);
                            $arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
                        }
                        elseif (is_callable($callback))
                        {
                            $arrButtons = $callback($arrButtons, $this);
                        }
                    }
                }

                $return .= '

                <div class="tl_formbody_submit" style="text-align:right">

                    <div class="tl_submit_container">
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
     * Move one or more local files to the server
     *
     * @param boolean $blnIsAjax
     *
     * @return string
     */
    public function move($blnIsAjax=false)
    {
        $strFolder = \Input::get('pid', true);

        if (!file_exists(TL_ROOT . '/' . $strFolder) || !$this->isMounted($strFolder))
        {
            $this->log('Folder "'.$strFolder.'" was not mounted or is not a directory', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        if (!preg_match('/^'.preg_quote(\Config::get('uploadPath'), '/').'/i', $strFolder))
        {
            $this->log('Parent folder "'.$strFolder.'" is not within the files directory', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        // Empty clipboard
        if (!$blnIsAjax)
        {
            $arrClipboard = $this->Session->get('CLIPBOARD');
            $arrClipboard[$this->strTable] = array();
            $this->Session->set('CLIPBOARD', $arrClipboard);
        }

        // Instantiate the uploader
        $this->import('BackendUser', 'User');
        $class = $this->User->uploader;

        // See #4086
        if (!class_exists($class))
        {
            $class = 'FileUpload';
        }

        /** @var \FileUpload $objUploader */
        $objUploader = new $class();

        // Process the uploaded files
        if (\Input::post('FORM_SUBMIT') == 'tl_upload')
        {
            // Generate the DB entries
            if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($strFolder))
            {
                // Upload the files
                $arrUploaded = $objUploader->uploadTo($strFolder);

                if (empty($arrUploaded) && !$objUploader->hasError())
                {
                    \Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
                    $this->reload();
                }

                foreach ($arrUploaded as $strFile)
                {
                    $objFile = \FilesModel::findByPath($strFile);

                    // Existing file is being replaced (see #4818)
                    if ($objFile !== null)
                    {
                        $objFile->tstamp = time();
                        $objFile->path   = $strFile;
                        $objFile->hash   = md5_file(TL_ROOT . '/' . $strFile);
                        $objFile->save();
                    }
                    else
                    {
                        \Dbafs::addResource($strFile);
                    }
                }
            }
            else
            {
                // Not DB-assisted, so just upload the file
                $arrUploaded = $objUploader->uploadTo($strFolder);
            }

            // HOOK: post upload callback
            if (isset($GLOBALS['TL_HOOKS']['postUpload']) && is_array($GLOBALS['TL_HOOKS']['postUpload']))
            {
                foreach ($GLOBALS['TL_HOOKS']['postUpload'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->{$callback[0]}->{$callback[1]}($arrUploaded);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($arrUploaded);
                    }
                }
            }

            // Update the hash of the target folder
            if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($strFolder))
            {
                \Dbafs::updateFolderHashes($strFolder);
            }

            // Redirect or reload
            if (!$objUploader->hasError())
            {
                // Do not purge the html folder (see #2898)
                if (\Input::post('uploadNback') && !$objUploader->hasResized())
                {
                    \Message::reset();
                    $this->redirect($this->getReferer());
                }

                $this->reload();
            }
        }

        // Submit buttons
        $arrButtons = array();
        $arrButtons['upload'] = '<button type="submit" name="upload" class="btn orange lighten-2" accesskey="s">'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['upload']).'</button>';
        $arrButtons['uploadNback'] = '<button type="submit" name="uploadNback" class="btn-flat orange-text text-lighten-2" accesskey="c">'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['uploadNback']).'</button>';

        // Call the buttons_callback (see #4691)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
                }
                elseif (is_callable($callback))
                {
                    $arrButtons = $callback($arrButtons, $this);
                }
            }
        }

        // Display the upload form
        return '
        <div id="tl_buttons" class="card-action">
            <a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">keyboard_backspace</i></a>
        </div>
        '.\Message::generate().'
        <form action="'.ampersand(\Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form" method="post"'.(!empty($this->onsubmit) ? ' onsubmit="'.implode(' ', $this->onsubmit).'"' : '').' enctype="multipart/form-data">
            <div class="tl_formbody_edit">
                <input type="hidden" name="FORM_SUBMIT" value="tl_upload">
                <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
                <input type="hidden" name="MAX_FILE_SIZE" value="'.\Config::get('maxFileSize').'">

                <div class="card-content">
                  <h3>'.$GLOBALS['TL_LANG'][$this->strTable]['fileupload'][0].'</h3>'.$objUploader->generateMarkup().'
              </div>

              </div>

              <div class="card-action">

                <div class="submit-container">
                  ' . implode(' ', $arrButtons) . '
              </div>

            </div>

        </form>';
    }

    /**
     * Auto-generate a form to rename a file or folder
     *
     * @return string
     */
    public function edit()
    {
        $return = '';
        $this->noReload = false;
        $this->isValid($this->intId);

        if (!file_exists(TL_ROOT . '/' . $this->intId) || !$this->isMounted($this->intId))
        {
            $this->log('File or folder "'.$this->intId.'" was not mounted or could not be found', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objModel = null;
		$objVersions = null;

		// Add the versioning routines
		if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($this->intId))
		{
			if (stristr($this->intId, '__new__') === false)
			{
				$objModel = \FilesModel::findByPath($this->intId);

				if ($objModel === null)
				{
					$objModel = \Dbafs::addResource($this->intId);
				}

				$this->objActiveRecord = $objModel;
			}

			$this->blnCreateNewVersion = false;

			/** @var \FilesModel $objModel */
			$objVersions = new \Versions($this->strTable, $objModel->id);

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
		}
		else
		{
			// Unset the database fields
			$GLOBALS['TL_DCA'][$this->strTable]['fields'] = array_intersect_key($GLOBALS['TL_DCA'][$this->strTable]['fields'], array('name' => true, 'protected' => true));
		}

        // Build an array from boxes and rows (do not show excluded fields)
        $this->strPalette = $this->getPalette();
        $boxes = trimsplit(';', $this->strPalette);

        if (!empty($boxes))
        {
            // Get fields
            foreach ($boxes as $k=>$v)
            {
                $boxes[$k] = trimsplit(',', $v);

                foreach ($boxes[$k] as $kk=>$vv)
                {
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['exclude'] || !isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]))
                    {
                        unset($boxes[$k][$kk]);
                    }
                }

                // Unset a box if it does not contain any fields
                if (empty($boxes[$k]))
                {
                    unset($boxes[$k]);
                }
            }

            // Render boxes
            $class = 'collapsible-body';

            foreach ($boxes as $v)
            {
                $return .= '
                <div class="'.$class.'" style="display:block">';

                // Build rows of the current box
                foreach ($v as $vv)
                {
                    $this->strField = $vv;
                    $this->strInputName = $vv;

                    // Load the current value
                    if ($vv == 'name')
                    {
                        $objFile = is_dir(TL_ROOT . '/' . $this->intId) ? new \Folder($this->intId) : new \File($this->intId, true);

						$this->strPath = str_replace(TL_ROOT . '/', '', $objFile->dirname);
						$this->strExtension = ($objFile->origext != '') ? '.'.$objFile->origext : '';
						$this->varValue = $objFile->filename;

                        // Fix Unix system files like .htaccess
                        if (strncmp($this->varValue, '.', 1) === 0)
                        {
                            $this->strExtension = '';
                        }

                        // Clear the current value if it is a new folder
                        if (\Input::post('FORM_SUBMIT') != 'tl_files' && \Input::post('FORM_SUBMIT') != 'tl_templates' && $this->varValue == '__new__')
                        {
                            $this->varValue = '';
                        }
                    }
                    else
                    {
                        $this->varValue = ($objModel !== null) ? $objModel->$vv : null;
                    }

                    // Call load_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->varValue = $this->{$callback[0]}->{$callback[1]}($this->varValue, $this);
                            }
                            elseif (is_callable($callback))
                            {
                                $this->varValue = $callback($this->varValue, $this);
                            }
                        }
                    }

                    // Build row
                    $return .= $this->row();
                }

                $class = 'collapsible-body';
                $return .= '
                <input type="hidden" name="FORM_FIELDS[]" value="'.specialchars($this->strPalette).'">
                <div class="clear"></div>
                </div>';
            }
        }

        // Versions overview
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] && $this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($this->intId))
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
        $arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="btn-flat orange-text text-lighten-2" accesskey="c">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']).'</button>';

        // Call the buttons_callback (see #4691)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
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

        <div class="submit-container">
        ' . implode(' ', $arrButtons) . '
        </div>

        </ul>
        </form>';

        // Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
        $return = $version . '
        <div id="tl_buttons" class="card-action">
        <a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">keyboard_backspace</i></a>
        </div>
        '.\Message::generate().'
        <form action="'.ampersand(\Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form" method="post"'.(!empty($this->onsubmit) ? ' onsubmit="'.implode(' ', $this->onsubmit).'"' : '').'>'.($this->noReload ? '
        <p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['general'].'</p>' : '').'
        <ul class="collapsible dca-edit" data-collapsible="expandable">
        <input type="hidden" name="FORM_SUBMIT" value="'.specialchars($this->strTable).'">
        <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">'.$return;

        // Reload the page to prevent _POST variables from being sent twice
        if (\Input::post('FORM_SUBMIT') == $this->strTable && !$this->noReload)
        {
            // Trigger the onsubmit_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->{$callback[0]}->{$callback[1]}($this);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($this);
                    }
                }
            }

            // Save the current version
            if ($this->blnCreateNewVersion && $objModel !== null)
            {
                $objVersions->create();

                // Call the onversion_callback
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onversion_callback']))
                {
                    @trigger_error('Using the onversion_callback has been deprecated and will no longer work in Contao 5.0. Use the oncreate_version_callback instead.', E_USER_DEPRECATED);

                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onversion_callback'] as $callback)
                    {
                        if (is_array($callback))
                        {
                            $this->import($callback[0]);
                            $this->{$callback[0]}->{$callback[1]}($this->strTable, $objModel->id, $this);
                        }
                        elseif (is_callable($callback))
                        {
                            $callback($this->strTable, $objModel->id, $this);
                        }
                    }
                }

                $this->log('A new version of file "'.$objFile->path.'" has been created', __METHOD__, TL_GENERAL);
            }

            // Set the current timestamp (-> DO NOT CHANGE THE ORDER version - timestamp)
            if ($this->blnIsDbAssisted && $objModel !== null)
            {
                $this->Database->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE id=?")
                ->execute(time(), $objModel->id);
            }

            // Redirect
            if (isset($_POST['saveNclose']))
            {
                \Message::reset();
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);
                $this->redirect($this->getReferer());
            }

            // Reload
            if ($this->blnIsDbAssisted && $this->objActiveRecord !== null)
            {
                $this->redirect($this->addToUrl('id='.$this->urlEncode($this->objActiveRecord->path)));
            }
            else
            {
                $this->redirect($this->addToUrl('id='.$this->urlEncode($this->strPath.'/'.$this->varValue).$this->strExtension));
            }
        }

        // Set the focus if there is an error
        if ($this->noReload)
        {
            $return .= '

            <script>
            $(document).ready(function($) {
                Backend.vScrollTo(($(\'#' . $this->strTable . ' label.error\').offset().top - 20));
            });
            </script>';
        }

        return $return;
    }

    /**
     * Load the source editor
     *
     * @return string
     */
    public function source()
    {
        $this->isValid($this->intId);

        if (is_dir(TL_ROOT .'/'. $this->intId))
        {
            $this->log('Folder "'.$this->intId.'" cannot be edited', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }
        elseif (!file_exists(TL_ROOT .'/'. $this->intId))
        {
            $this->log('File "'.$this->intId.'" does not exist', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $this->import('BackendUser', 'User');

        // Check user permission
        if (!$this->User->hasAccess('f5', 'fop'))
        {
            $this->log('Not enough permissions to edit the file source of file "'.$this->intId.'"', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objFile = new \File($this->intId, true);

        // Check whether file type is editable
        if (!in_array($objFile->extension, trimsplit(',', strtolower(\Config::get('editableFiles')))))
        {
            $this->log('File type "'.$objFile->extension.'" ('.$this->intId.') is not allowed to be edited', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objMeta = null;
		$objVersions = null;

        // Add the versioning routines
        if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($this->intId))
        {
            $objMeta = \FilesModel::findByPath($objFile->value);

            if ($objMeta === null)
            {
                $objMeta = \Dbafs::addResource($objFile->value);
            }

            $objVersions = new \Versions($this->strTable, $objMeta->id);

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

                    // Purge the script cache (see #7005)
                    if ($objFile->extension == 'css' || $objFile->extension == 'scss' || $objFile->extension == 'less')
                    {
                        $this->import('Automator');
                        $this->Automator->purgeScriptCache();
                    }

                    $this->reload();
                }
            }

            $objVersions->initialize();
        }

        $strContent = $objFile->getContent();

        if ($objFile->extension == 'svgz')
        {
            $strContent = gzdecode($strContent);
        }

        // Process the request
        if (\Input::post('FORM_SUBMIT') == 'tl_files')
        {
            // Restore the basic entities (see #7170)
            $strSource = \StringUtil::restoreBasicEntities(\Input::postRaw('source'));

            // Save the file
            if (md5($strContent) != md5($strSource))
            {
                if ($objFile->extension == 'svgz')
                {
                    $strSource = gzencode($strSource);
                }

                // Write the file
                $objFile->write($strSource);
                $objFile->close();

                // Update the database
                if ($this->blnIsDbAssisted && $objMeta !== null)
                {
                    /** @var \FilesModel $objMeta */
                    $objMeta->hash = $objFile->hash;
                    $objMeta->save();

                    $objVersions->create();
                }

                // Purge the script cache (see #7005)
                if ($objFile->extension == 'css' || $objFile->extension == 'scss' || $objFile->extension == 'less')
                {
                    $this->import('Automator');
                    $this->Automator->purgeScriptCache();
                }
            }

            if (\Input::post('saveNclose'))
            {
                \System::setCookie('BE_PAGE_OFFSET', 0, 0);
                $this->redirect($this->getReferer());
            }

            $this->reload();
        }

        $codeEditor = '';

        // Prepare the code editor
        if (\Config::get('useCE'))
        {
            $selector = 'ctrl_source';
            $type = $objFile->extension;

            // Load the code editor configuration
            ob_start();
            include TL_ROOT . '/system/modules/contao-material/config/ace.php';
            $codeEditor = ob_get_contents();
            ob_end_clean();

            unset($selector, $type);
        }

        // Versions overview
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] && $this->blnIsDbAssisted && $objVersions !== null)
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
        $arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="btn-flat orange-text text-lighten-2" accesskey="c">'.specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']).'</button>';

        // Call the buttons_callback (see #4691)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
                }
                elseif (is_callable($callback))
                {
                    $arrButtons = $callback($arrButtons, $this);
                }
            }
        }

        // Add the form
        return $version . '
        <div id="tl_buttons" class="card-action">
            <a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">arrow_back</i></a>
        </div>
        '.\Message::generate().'
        <form action="'.ampersand(\Environment::get('request'), true).'" id="tl_files" class="tl_form" method="post">
            <div class="tl_formbody_edit">
                <input type="hidden" name="FORM_SUBMIT" value="tl_files">
                <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
                <div class="tl_tbox">
                  <h3><label for="ctrl_source">'.$GLOBALS['TL_LANG']['tl_files']['editor'][0].'</label></h3>
                  <textarea name="source" id="ctrl_source" class="tl_textarea monospace" rows="12" cols="80" style="height:400px" onfocus="Backend.getScrollOffset()">' . "\n" . htmlspecialchars($strContent) . '</textarea>' . ((\Config::get('showHelp') && strlen($GLOBALS['TL_LANG']['tl_files']['editor'][1])) ? '
                      <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_files']['editor'][1].'</p>' : '') . '
              </div>
          </div>

          <div class="card-action">

              ' . implode(' ', $arrButtons) . '
          </div>

        </form>' . "\n\n" . $codeEditor;
    }

    /**
     * Synchronize the file system with the database
     *
     * @return string
     */
    public function sync()
    {
        if (!$this->blnIsDbAssisted)
        {
            return '';
        }

        $this->import('BackendUser', 'User');
        $this->loadLanguageFile('tl_files');

        // Check the permission to synchronize
        if (!$this->User->hasAccess('f6', 'fop'))
        {
            $this->log('Not enough permissions to synchronize the file system', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        // Synchronize
        $strLog = \Dbafs::syncFiles();

        // Show the results
        $arrMessages = array();
        $arrCounts   = array('Added'=>0, 'Changed'=>0, 'Unchanged'=>0, 'Moved'=>0, 'Deleted'=>0);

        // Read the log file
        $fh = fopen(TL_ROOT . '/' . $strLog, 'rb');

        while (($buffer = fgets($fh)) !== false)
        {
            list($type, $file) = explode('] ', trim(substr($buffer, 1)), 2);

            // Add a message depending on the type
            switch ($type)
            {
                case 'Added';
                $arrMessages[] = '<p class="tl_new">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncAdded'], specialchars($file)) . '</p>';
                break;

                case 'Changed';
                $arrMessages[] = '<p class="tl_info">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncChanged'], specialchars($file)) . '</p>';
                break;

                case 'Unchanged';
                $arrMessages[] = '<p class="tl_confirm hidden">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncUnchanged'], specialchars($file)) . '</p>';
                break;

                case 'Moved';
                list($source, $target) = explode(' to ', $file, 2);
                $arrMessages[] = '<p class="tl_info">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncMoved'], specialchars($source), specialchars($target)) . '</p>';
                break;

                case 'Deleted';
                $arrMessages[] = '<p class="tl_error">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncDeleted'], specialchars($file)) . '</p>';
                break;
            }

            ++$arrCounts[$type];
        }

        // Close the log file
        unset($buffer);
        fclose($fh);

        // Confirm
        \Message::addConfirmation($GLOBALS['TL_LANG']['tl_files']['syncComplete']);

        $return = '
        <div id="tl_buttons" class="card-action">
            <a href="'.$this->getReferer(true).'" class="header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()"><i class="material-icons black-text">keyboard_backspace</i></a>
        </div>
        '.\Message::generate().'
        <div id="sync-results">
        <p class="left">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncResult'], \System::getFormattedNumber($arrCounts['Added'], 0), \System::getFormattedNumber($arrCounts['Changed'], 0), \System::getFormattedNumber($arrCounts['Unchanged'], 0), \System::getFormattedNumber($arrCounts['Moved'], 0), \System::getFormattedNumber($arrCounts['Deleted'], 0)) . '</p>
        <p class="right"><input type="checkbox" id="show-hidden" class="tl_checkbox" onclick="Backend.toggleUnchanged()"> <label for="show-hidden">' . $GLOBALS['TL_LANG']['tl_files']['syncShowUnchanged'] . '</label></p>
        <div class="clear"></div>
        </div>
        <div class="tl_message nobg sync-result" id="result-list" style="margin-bottom:2em">';

        // Add the messages
        foreach ($arrMessages as $strMessage)
        {
            $return .= "\n  " . $strMessage;
        }

        $return .= '
        </div>
        <div class="card-action">
        <div class="submit-container">
        <a href="'.$this->getReferer(true).'" class="btn orange lighten-2 white-text" style="display:inline-block">'.$GLOBALS['TL_LANG']['MSC']['continue'].'</a>
        </div>
        </div>
        ';

        return $return;
    }

    /**
     * Render the file tree and return it as HTML string
     *
     * @param string  $path
     * @param integer $intMargin
     * @param boolean $mount
     * @param boolean $blnProtected
     * @param array   $arrClipboard
     *
     * @return string
     */
    protected function generateTree($path, $intMargin, $mount=false, $blnProtected=false, $arrClipboard=null)
    {
        static $session;
        $session = $this->Session->getData();

        // Get the session data and toggle the nodes
        if (\Input::get('tg'))
        {
            $session['filetree'][\Input::get('tg')] = (isset($session['filetree'][\Input::get('tg')]) && $session['filetree'][\Input::get('tg')] == 1) ? 0 : 1;
            $this->Session->setData($session);
            $this->redirect(preg_replace('/(&(amp;)?|\?)tg=[^& ]*/i', '', \Environment::get('request')));
        }

        $return = '';
        $files = array();
        $folders = array();
        $intSpacing = 20;
        $level = ($intMargin / $intSpacing);

        // Mount folder
        if ($mount)
        {
            $folders = array($path);
        }

        // Scan directory and sort the result
        else
        {
            foreach (scan($path) as $v)
            {
                if (strncmp($v, '.', 1) === 0)
                {
                    continue;
                }

                if (is_file($path . '/' . $v))
                {
                    $files[] = $path . '/' . $v;
                }
                else
                {
                    if ($v == '__new__')
                    {
                        $this->Files->rmdir(str_replace(TL_ROOT . '/', '', $path) . '/' . $v);
                    }
                    else
                    {
                        $folders[] = $path . '/' . $v;
                    }
                }
            }

            natcasesort($folders);
            $folders = array_values($folders);

            natcasesort($files);
            $files = array_values($files);
        }

        // Folders
        for ($f=0, $c=count($folders); $f<$c; $f++)
        {
            $md5 = substr(md5($folders[$f]), 0, 8);
            $content = scan($folders[$f]);
            $currentFolder = str_replace(TL_ROOT . '/', '', $folders[$f]);
            $session['filetree'][$md5] = is_numeric($session['filetree'][$md5]) ? $session['filetree'][$md5] : 0;
            $currentEncoded = $this->urlEncode($currentFolder);
            $countFiles = count($content);

            // Subtract files that will not be shown
            foreach ($content as $file)
            {
                if (strncmp($file, '.', 1) === 0)
                {
                    --$countFiles;
                }
                elseif (!empty($this->arrValidFileTypes) && is_file($folders[$f] . '/' . $file) && !in_array(strtolower(substr($file, (strrpos($file, '.') + 1))), $this->arrValidFileTypes))
                {
                    --$countFiles;
                }
            }
            $isNodeActive = ($session['filetree'][$md5] == 1) ? ' active' : '';
            $return .= "\n  " . '<li class="folder row-container click2edit toggle_select"><div class="collapsible-header' . $isNodeActive . (!empty($countFiles) ? ' -with-child' : '') . '"><div class="item">';

            // Add a toggle button if there are childs
            if ($countFiles > 0)
            {
                $alt = ($session['filetree'][$md5] == 1) ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
                $return .= '<a href="'.$this->addToUrl('tg='.$md5).'" class="tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars($alt).'" onclick="Backend.getScrollOffset(); return AjaxRequest.toggleFileManager(this, \'filetree_'.$md5.'\', \''.str_replace("'", "\'", $currentFolder).'\', '.$level.')"><i class="material-icons expand-icon">expand_less</i></a>';
            }

            $protected = ($blnProtected === true || array_search('.htaccess', $content) !== false) ? true : false;
            $folderImg = $protected ? 'folderCP.gif' : 'folderC.gif';

            // Add the current folder
            $strFolderNameEncoded = utf8_convert_encoding(specialchars(basename($currentFolder)), \Config::get('characterSet'));
            $return .= Helper::getIconHtml($folderImg, '').' <a href="' . $this->addToUrl('fn='.$currentEncoded) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'"><strong>'.$strFolderNameEncoded.'</strong></a></div> <div class="actions">';

            // Paste buttons
            if ($arrClipboard !== false && \Input::get('act') != 'select')
            {
                $imagePasteInto = Helper::getIconHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);
                $return .= (($arrClipboard['mode'] == 'cut' || $arrClipboard['mode'] == 'copy') && preg_match('/^' . preg_quote($arrClipboard['id'], '/') . '/i', $currentFolder)) ? Helper::getIconHtml('pasteinto_.gif') : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$currentEncoded.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" class="btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped paste-action -into" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
            }
            // Default buttons
            else
            {
                // Do not display buttons for mounted folders
                if ($this->User->isAdmin || !in_array($currentFolder, $this->User->filemounts))
                {
                    $return .= (\Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_'.md5($currentEncoded).'" class="tl_tree_checkbox" value="'.$currentEncoded.'">' : $this->generateButtons(array('id'=>$currentEncoded, 'popupWidth'=>640, 'popupHeight'=>132, 'fileNameEncoded'=>$strFolderNameEncoded), $this->strTable);
                }

                // Upload button
                if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] && \Input::get('act') != 'select')
                {
                    $return .= ' <a href="'.$this->addToUrl('&amp;act=move&amp;mode=2&amp;pid='.$currentEncoded).'" class="btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars(sprintf($GLOBALS['TL_LANG']['tl_files']['uploadFF'], $currentEncoded)).'">'.\Helper::getIconHtml('new.gif', $GLOBALS['TL_LANG'][$this->strTable]['move'][0]).'</a>';
                }
            }

            $return .= '</div>';

            // Call the next node
            if (!empty($content) && $session['filetree'][$md5] == 1)
            {
                $return .= '</div><div class="collapsible-body" id="filetree_'.$md5.'"><ul class="level-'.$level.' collapsible" data-collapsible="expandable">';
                $return .= $this->generateTree($folders[$f], ($intMargin + $intSpacing), false, $protected, $arrClipboard);
                $return .= '</ul></div>';
            }
        }

        // Process files
        for ($h=0, $c=count($files); $h<$c; $h++)
        {
            $thumbnail = '';
            $popupWidth = 600;
            $popupHeight = 161;
            $currentFile = str_replace(TL_ROOT . '/', '', $files[$h]);

            $objFile = new \File($currentFile, true);

            if (!empty($this->arrValidFileTypes) && !in_array($objFile->extension, $this->arrValidFileTypes))
            {
                continue;
            }

            $currentEncoded = $this->urlEncode($currentFile);
            $return .= "\n  " . '<li class="row-container click2edit toggle_select"><div class="collapsible-header"><div class="item">';
            $thumbnail .= ' <span class="tl_gray">('.$this->getReadableSize($objFile->filesize);

            if ($objFile->width && $objFile->height)
            {
                $thumbnail .= ', '.$objFile->width.'x'.$objFile->height.' px';
            }

            $thumbnail .= ')</span>';

            // Generate the thumbnail
            if ($objFile->isImage)
            {
                if ($objFile->viewHeight > 0)
                {
                    if ($objFile->width && $objFile->height)
                    {
                        $popupWidth = ($objFile->width > 600) ? ($objFile->width + 61) : 661;
                        $popupHeight = ($objFile->height + 210);
                    }
                    else
                    {
                        $popupWidth = 661;
                        $popupHeight = 625 / $objFile->viewWidth * $objFile->viewHeight + 210;
                    }

                    if (\Config::get('thumbnails') && ($objFile->isSvgImage || $objFile->height <= \Config::get('gdMaxImgHeight') && $objFile->width <= \Config::get('gdMaxImgWidth')))
                    {
                        $thumbnail .= '<br><img src="' . TL_FILES_URL . \Image::get($currentEncoded, 400, (($objFile->height && $objFile->height < 50) ? $objFile->height : 50), 'box') . '" alt="" class="preview">';
                    }
                }
                else
                {
                    $popupHeight = 360; // dimensionless SVGs are rendered at 300x150px, so the popup needs to be 150px + 210px high
                }
            }

            $strFileNameEncoded = utf8_convert_encoding(specialchars(basename($currentFile)), \Config::get('characterSet'));

            // No popup links for templates and in the popup file manager
            if ($this->strTable == 'tl_templates' || \Input::get('popup'))
            {
                $return .= Helper::getIconHtml($objFile->icon).' '.$strFileNameEncoded.$thumbnail.'</div> <div class="actions">';
            }
            else
            {
                $return .= '<a href="'. $currentEncoded.'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['view']).'" target="_blank">' . Helper::getIconHtml($objFile->icon, $objFile->mime).'</a> '.$strFileNameEncoded.$thumbnail.'</div> <div class="actions">';
            }

            // Buttons
            if ($arrClipboard !== false && \Input::get('act') != 'select')
            {
                $_buttons = '&nbsp;';
            }
            else
            {
                $_buttons = (\Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_'.md5($currentEncoded).'" class="tl_tree_checkbox" value="'.$currentEncoded.'">' : $this->generateButtons(array('id'=>$currentEncoded, 'popupWidth'=>$popupWidth, 'popupHeight'=>$popupHeight, 'fileNameEncoded'=>$strFileNameEncoded), $this->strTable);
            }

            $return .= $_buttons . '</div></div></li>';
        }

        return $return;
    }
}
