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
 * Provide methods to modify the file system.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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

        $imagePasteInto = \Image::getHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);

        // Build the tree
        $return = '
<div id="tl_buttons" class="card-action">'.((\Input::get('act') == 'select') ? '
<a href="'.$this->getReferer(true).'" class="header-back tooltipped" data-position="right" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : '') . ((\Input::get('act') != 'select' && !$blnClipboard) ? '
<a href="'.$this->addToUrl($hrfNew).'" class="'.$clsNew.' tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars($ttlNew).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$lblNew.'</a> ' . ((!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? '<a href="'.$this->addToUrl('&amp;act=paste&amp;mode=move').'" class="header-new btn-floating btn-large waves-effect waves-light red tooltipped" data-position="left" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['move'][1]).'" onclick="Backend.getScrollOffset()"><i class="material-icons">add</i></a> ' : '') . $this->generateGlobalButtons() : '') . ($blnClipboard ? '<a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard tooltipped" data-position="top" data-delay="50" data-tooltip="'.specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ' : '') . '
</div>' . \Message::generate(true) . ((\Input::get('act') == 'select') ? '

<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form'.((\Input::get('act') == 'select') ? ' unselectable' : '').'" method="post" novalidate>
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').($blnClipboard ? '

<div id="paste_hint">
  <p>'.$GLOBALS['TL_LANG']['MSC']['selectNewPosition'].'</p>
</div>' : '').'

<div class="tl_listing_container tree_view" id="tl_listing">'.(isset($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['breadcrumb']) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['breadcrumb'] : '').((\Input::get('act') == 'select') ? '

<div class="tl_select_trigger">
<label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">
</div>' : '').'

<ul class="tl_listing">
  <li class="tl_folder_top" onmouseover="Theme.hoverDiv(this,1)" onmouseout="Theme.hoverDiv(this,0)"><div class="tl_left">'.\Image::getHtml('filemounts.gif').' '.$GLOBALS['TL_LANG']['MSC']['filetree'].'</div> <div class="tl_right">'.(($blnClipboard && empty($this->arrFilemounts) && !is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] !== false) ? '<a href="'.$this->addToUrl('&amp;act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.\Config::get('uploadPath').(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a>' : '&nbsp;').'</div><div style="clear:both"></div></li>'.$return.'
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
                        $arrButtons = $this->$callback[0]->$callback[1]($arrButtons, $this);
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

        return $return;
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
<div id="tl_buttons">
<a href="'.$this->getReferer(true).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>
'.\Message::generate().'
<div id="sync-results">
  <p class="left">' . sprintf($GLOBALS['TL_LANG']['tl_files']['syncResult'], \System::getFormattedNumber($arrCounts['Added'], 0), \System::getFormattedNumber($arrCounts['Changed'], 0), \System::getFormattedNumber($arrCounts['Unchanged'], 0), \System::getFormattedNumber($arrCounts['Moved'], 0), \System::getFormattedNumber($arrCounts['Deleted'], 0)) . '</p>
  <p class="right"><input type="checkbox" id="show-hidden" class="tl_checkbox" onclick="Backend.toggleUnchanged()"> <label for="show-hidden">' . $GLOBALS['TL_LANG']['tl_files']['syncShowUnchanged'] . '</label></p>
  <div class="clear"></div>
</div>
<div class="tl_message nobg" id="result-list" style="margin-bottom:2em">';

        // Add the messages
        foreach ($arrMessages as $strMessage)
        {
            $return .= "\n  " . $strMessage;
        }

        $return .= '
</div>

<div class="tl_submit_container">
  <a href="'.$this->getReferer(true).'" class="tl_submit" style="display:inline-block">'.$GLOBALS['TL_LANG']['MSC']['continue'].'</a>
</div>
';

        return $return;
    }
}