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
* Stores and outputs messages
*
* The class handles system messages which are shown to the user. You can add
* messages from anywhere in the application.
*
* Usage:
*
*     Message::addError('Please enter your name');
*     Message::addConfirmation('The data has been stored');
*     Message::addNew('There are two new messages');
*     Message::addInfo('You can upload only two files');
*
* @author Leo Feyer <https://github.com/leofeyer>
*/
class Message extends \Contao\Message
{
    /**
     * Get a CSS class for a given message type
     *
     * @param string $type message type
     *
     * @return string CSS class
     */
    protected static function getCssClass($type)
    {
        return '-' . strtolower(substr($type, 3));
    }

    /**
    * Return all messages as HTML
    *
    * @param boolean $blnDcLayout If true, the line breaks are different
    * @param boolean $blnNoWrapper If true, there will be no wrapping DIV
    *
    * @return string The messages HTML markup
    */
    public static function generate($blnDcLayout = false, $blnNoWrapper = false)
    {
        $strMessages = '';

        // Regular messages
        foreach (static::getTypes() as $strType)
        {
            if (!is_array($_SESSION[$strType]))
            {
                continue;
            }

            $strClass = self::getCssClass($strType);
            $_SESSION[$strType] = array_unique($_SESSION[$strType]);

            foreach ($_SESSION[$strType] as $strMessage)
            {
                if ($strType == 'TL_RAW')
                {
                    $strMessages .= $strMessage;
                }
                else
                {
                    $template = new \BackendTemplate('be_message');
                    $template->cssClass = $strClass;
                    $template->message = $strMessage;

                    $strMessages .= $template->parse();
                }
            }

            if (!$_POST)
            {
                $_SESSION[$strType] = array();
            }
        }

        $strMessages = trim($strMessages);

        // Wrapping container
        if (!$blnNoWrapper && $strMessages != '')
        {
            $strMessages = sprintf('%s<div class="messages">%s%s%s</div>%s', ($blnDcLayout ? "\n\n" : "\n"), "\n", $strMessages, "\n", ($blnDcLayout ? '' : "\n"));
        }

        return $strMessages;
    }
}
