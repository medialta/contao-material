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
 * Provide methods to handle input field "file tree".
 *
 * @property string  $path
 * @property string  $fieldType
 * @property string  $sort
 * @property boolean $files
 * @property boolean $filesOnly
 * @property string  $extensions
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileSelector extends \Contao\FileSelector
{

    /**
     * Submit user input
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Path nodes
     * @var array
     */
    protected $arrNodes = array();

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_widget';


    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $this->import('BackendUser', 'User');
        $this->convertValuesToPaths();

        $strNode = $this->Session->get('tl_files_picker');

        // Unset the node if it is not within the path (see #5899)
        if ($strNode != '' && $this->path != '')
        {
            if (strncmp($strNode . '/', $this->path . '/', strlen($this->path) + 1) !== 0)
            {
                $this->Session->remove('tl_files_picker');
            }
        }

        // Add the breadcrumb menu
        if (\Input::get('do') != 'files')
        {
            \Backend::addFilesBreadcrumb('tl_files_picker');
        }

        $tree = '';

        // Root nodes (breadcrumb menu)
        if (!empty($GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root']))
        {
            $nodes = $this->eliminateNestedPaths($GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root']);

            foreach ($nodes as $node)
            {
                $tree .= $this->renderFiletree(TL_ROOT . '/' . $node, 0, true);
            }
        }

        // Show a custom path (see #4926)
        elseif ($this->path != '')
        {
            $tree .= $this->renderFiletree(TL_ROOT . '/' . $this->path, 0);
        }

        // Start from root
        elseif ($this->User->isAdmin)
        {
            $tree .= $this->renderFiletree(TL_ROOT . '/' . \Config::get('uploadPath'), 0);
        }

        // Show mounted files to regular users
        else
        {
            $nodes = $this->eliminateNestedPaths($this->User->filemounts);

            foreach ($nodes as $node)
            {
                $tree .= $this->renderFiletree(TL_ROOT . '/' . $node, 0, true);
            }
        }

        // Select all checkboxes
        if ($this->fieldType == 'checkbox')
        {
            $strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="actions"><label for="check_all_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</label> <input type="checkbox" id="check_all_' . $this->strId . '" class="tl_tree_checkbox" value="" onclick="Backend.toggleCheckboxGroup(this,\'' . $this->strName . '\')"></div><div style="clear:both"></div></li>';
        }
        // Reset radio button selection
        else
        {
            $strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="actions"><label for="reset_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['resetSelected'] . '</label> <input type="radio" name="' . $this->strName . '" id="reset_' . $this->strName . '" class="tl_tree_radio" value="" onfocus="Backend.getScrollOffset()"><label for="reset_' . $this->strName . '"></label></div><div style="clear:both"></div></li>';
        }

        // Return the tree
        return '<ul class="white listing listing-files tree collapsible tree_view picker_selector'.(($this->strClass != '') ? ' ' . $this->strClass : '').'" id="'.$this->strId.'" data-collapsible="expandable">
    <li class="row-top"><div class="item">'.Helper::getIconHtml($GLOBALS['TL_DCA']['tl_files']['list']['sorting']['icon'] ?: 'filemounts.gif').'<label> '.(\Config::get('websiteTitle') ?: 'Contao Open Source CMS').'</label></div> <div class="actions">&nbsp;</div><div style="clear:both"></div></li>'.$tree.$strReset.'
  </ul>';
    }


    /**
     * Generate a particular subpart of the file tree and return it as HTML string
     *
     * @param integer $folder
     * @param string  $strField
     * @param integer $level
     * @param boolean $mount
     *
     * @return string
     */
    public function generateAjax($folder, $strField, $level, $mount=false)
    {
        if (!\Environment::get('isAjaxRequest'))
        {
            return '';
        }

        $this->strField = $strField;
        $this->loadDataContainer($this->strTable);

        // Load the current values
        switch ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'])
        {
            case 'File':
                if (\Config::get($this->strField) != '')
                {
                    $this->varValue = \Config::get($this->strField);
                }
                break;

            case 'Table':
                $this->import('Database');

                if (!$this->Database->fieldExists($this->strField, $this->strTable))
                {
                    break;
                }

                $objField = $this->Database->prepare("SELECT " . $this->strField . " FROM " . $this->strTable . " WHERE id=?")
                                           ->limit(1)
                                           ->execute($this->strId);

                if ($objField->numRows)
                {
                    $this->varValue = deserialize($objField->{$this->strField});
                }
                break;
        }

        $this->convertValuesToPaths();

        return $this->renderFiletree(TL_ROOT . '/' . $folder, ($level * 20), $mount);
    }


    /**
     * Recursively render the filetree
     *
     * @param string  $path
     * @param integer $intMargin
     * @param boolean $mount
     * @param boolean $blnProtected
     *
     * @return string
     */
    protected function renderFiletree($path, $intMargin, $mount=false, $blnProtected=false)
    {
        // Invalid path
        if (!is_dir($path))
        {
            return '';
        }

        // Make sure that $this->varValue is an array (see #3369)
        if (!is_array($this->varValue))
        {
            $this->varValue = array($this->varValue);
        }

        static $session;
        $session = $this->Session->getData();

        $flag = substr($this->strField, 0, 2);
        $node = 'tree_' . $this->strTable . '_' . $this->strField;
        $xtnode = 'tree_' . $this->strTable . '_' . $this->strName;

        // Get session data and toggle nodes
        if (\Input::get($flag.'tg'))
        {
            $session[$node][\Input::get($flag.'tg')] = (isset($session[$node][\Input::get($flag.'tg')]) && $session[$node][\Input::get($flag.'tg')] == 1) ? 0 : 1;
            $this->Session->setData($session);
            $this->redirect(preg_replace('/(&(amp;)?|\?)'.$flag.'tg=[^& ]*/i', '', \Environment::get('request')));
        }

        $return = '';
        $intSpacing = 20;
        $files = array();
        $folders = array();
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

                if (is_dir($path . '/' . $v))
                {
                    $folders[] = $path . '/' . $v;
                }
                else
                {
                    $files[] = $path . '/' . $v;
                }
            }
        }

        natcasesort($folders);
        $folders = array_values($folders);

        natcasesort($files);
        $files = array_values($files);

        // Sort descending (see #4072)
        if ($this->sort == 'desc')
        {
            $folders = array_reverse($folders);
            $files = array_reverse($files);
        }

        $folderClass = ($this->files || $this->filesOnly) ? 'folder row-container' : 'file row-container';

        // Process folders
        for ($f=0, $c=count($folders); $f<$c; $f++)
        {
            $countFiles = 0;
            $content = scan($folders[$f]);
            $return .= "\n    " . '<li class="'.$folderClass.' toggle_select">';

            // Check whether there are subfolders or files
            foreach ($content as $v)
            {
                if (is_dir($folders[$f] . '/' . $v) || $this->files || $this->filesOnly)
                {
                    $countFiles++;
                }
            }

            $tid = md5($folders[$f]);
            $folderAttribute = 'style="margin-left:20px"';
            $session[$node][$tid] = is_numeric($session[$node][$tid]) ? $session[$node][$tid] : 0;
            $currentFolder = str_replace(TL_ROOT . '/', '', $folders[$f]);
            $blnIsOpen = ($session[$node][$tid] == 1 || count(preg_grep('/^' . preg_quote($currentFolder, '/') . '\//', $this->varValue)) > 0);
            $isNodeActive = ($session[$node][$tid] == 1) ? ' active' : '';

            // Add a toggle button if there are childs
            if ($countFiles > 0)
            {
                $folderAttribute = '';
                $alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
                $return .= '<div class="collapsible-header' . $isNodeActive . '"><div class="item"><a href="'.$this->addToUrl($flag.'tg='.$tid).'" title="'.specialchars($alt).'" onclick="return AjaxRequest.toggleFiletree(this,\''.$xtnode.'_'.$tid.'\',\''.$currentFolder.'\',\''.$this->strField.'\',\''.$this->strName.'\','.$level.')"><i class="material-icons expand-icon">expand_less</i></a>';
            }

            $protected = ($blnProtected === true || array_search('.htaccess', $content) !== false) ? true : false;
            $folderImg = ($blnIsOpen && $countFiles > 0) ? ($protected ? 'folderOP.gif' : 'folderO.gif') : ($protected ? 'folderCP.gif' : 'folderC.gif');
            $folderLabel = ($this->files || $this->filesOnly) ? '<strong>'.specialchars(basename($currentFolder)).'</strong>' : specialchars(basename($currentFolder));

            // Add the current folder
            $return .= Helper::getIconHtml($folderImg, '', $folderAttribute).' <a href="' . $this->addToUrl('node='.$this->urlEncode($currentFolder)) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$folderLabel.'</a></div><div class="actions">';

            // Add a checkbox or radio button
            if (!$this->filesOnly)
            {
                switch ($this->fieldType)
                {
                    case 'checkbox':
                        $return .= '<input type="checkbox" name="'.$this->strName.'[]" id="'.$this->strName.'_'.md5($currentFolder).'" class="tl_tree_checkbox" value="'.specialchars($currentFolder).'" onfocus="Backend.getScrollOffset()"'.$this->optionChecked($currentFolder, $this->varValue).'><label for="'.$this->strName.'_'.md5($currentFolder).'"></label>';
                        break;

                    case 'radio':
                        $return .= '<input type="radio" name="'.$this->strName.'" id="'.$this->strName.'_'.md5($currentFolder).'" class="tl_tree_radio" value="'.specialchars($currentFolder).'" onfocus="Backend.getScrollOffset()"'.$this->optionChecked($currentFolder, $this->varValue).'><label for="'.$this->strName.'_'.md5($currentFolder).'"></label>';
                        break;
                }
            }

            $return .= '</div>';

            // Call the next node
            if ($countFiles > 0 && $blnIsOpen)
            {
                $return .= '</div><div class="collapsible-body" id="'.$xtnode.'_'.$tid.'" style="display:block"><ul class="level-'.$level.' collapsible" data-collapsible="expandable">';
                $return .= $this->renderFiletree($folders[$f], ($intMargin + $intSpacing), false, $protected);
                $return .= '</ul></div>';
            }
        }

        // Process files
        if ($this->files || $this->filesOnly)
        {
            $allowedExtensions = null;

            if ($this->extensions != '')
            {
                $allowedExtensions = trimsplit(',', $this->extensions);
            }

            for ($h=0, $c=count($files); $h<$c; $h++)
            {
                $thumbnail = '';
                $currentFile = str_replace(TL_ROOT . '/', '', $files[$h]);
                $currentEncoded = $this->urlEncode($currentFile);

                $objFile = new \File($currentFile, true);

                // Check file extension
                if (is_array($allowedExtensions) && !in_array($objFile->extension, $allowedExtensions))
                {
                    continue;
                }

                $return .= "\n    " . '<li class="file row-container file_toggle_select"><div class="collapsible-header"><div class="item">';

                // Generate thumbnail
                if ($objFile->isImage && $objFile->viewHeight > 0)
                {
                    if ($objFile->width && $objFile->height)
                    {
                        $thumbnail .= ' <span class="tl_gray">(' . $objFile->width . 'x' . $objFile->height . ')</span>';
                    }

                    if (\Config::get('thumbnails') && ($objFile->isSvgImage || $objFile->height <= \Config::get('gdMaxImgHeight') && $objFile->width <= \Config::get('gdMaxImgWidth')))
                    {
                        $thumbnail .= '<br><img src="' . TL_FILES_URL . \Image::get($currentEncoded, 400, (($objFile->height && $objFile->height < 50) ? $objFile->height : 50), 'box') . '" alt="">';
                    }
                }

                $return .= Helper::getIconHtml($objFile->icon, $objFile->mime).' '.utf8_convert_encoding(specialchars(basename($currentFile)), \Config::get('characterSet')).$thumbnail.'</div><div class="actions">';

                // Add checkbox or radio button
                switch ($this->fieldType)
                {
                    case 'checkbox':
                        $return .= '<input type="checkbox" name="'.$this->strName.'[]" id="'.$this->strName.'_'.md5($currentFile).'" class="tl_tree_checkbox" value="'.specialchars($currentFile).'" onfocus="Backend.getScrollOffset()"'.$this->optionChecked($currentFile, $this->varValue).'><label for="'.$this->strName.'_'.md5($currentFile).'"></label>';
                        break;

                    case 'radio':
                        $return .= '<input type="radio" name="'.$this->strName.'" id="'.$this->strName.'_'.md5($currentFile).'" class="tl_tree_radio" value="'.specialchars($currentFile).'" onfocus="Backend.getScrollOffset()"'.$this->optionChecked($currentFile, $this->varValue).'><label for="'.$this->strName.'_'.md5($currentFile).'"></label>';
                        break;
                }

                $return .= '</div></div></li>';
            }
        }

        return $return;
    }


    /**
     * Translate the file IDs to file paths
     */
    protected function convertValuesToPaths()
    {
        if (empty($this->varValue))
        {
            return;
        }

        if (!is_array($this->varValue))
        {
            $this->varValue = array($this->varValue);
        }
        elseif (empty($this->varValue[0]))
        {
            $this->varValue = array();
        }

        if (empty($this->varValue))
        {
            return;
        }

        // TinyMCE will pass the path instead of the ID
        if (strncmp($this->varValue[0], \Config::get('uploadPath') . '/', strlen(\Config::get('uploadPath')) + 1) === 0)
        {
            return;
        }

        // Ignore the numeric IDs when in switch mode (TinyMCE)
        if (\Input::get('switch'))
        {
            return;
        }

        $objFiles = \FilesModel::findMultipleByIds($this->varValue);

        if ($objFiles !== null)
        {
            $this->varValue = array_values($objFiles->fetchEach('path'));
        }
    }
}
