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
 * Provide methods to manage back end controllers.
 *
 * @property \Ajax $objAjax
 */
abstract class Backend extends \Contao\Backend
{
    /**
     * Add a breadcrumb menu to the page tree
     *
     * @param string $strKey
     *
     * @throws \RuntimeException
     */
    public static function addPagesBreadcrumb($strKey='tl_page_node')
    {
        $objSession = \Session::getInstance();

        // Set a new node
        if (isset($_GET['node']))
        {
            // Check the path (thanks to Arnaud Buchoux)
            if (\Validator::isInsecurePath(\Input::get('node', true)))
            {
                throw new \RuntimeException('Insecure path ' . \Input::get('node', true));
            }

            $objSession->set($strKey, \Input::get('node', true));
            \Controller::redirect(preg_replace('/&node=[^&]*/', '', \Environment::get('request')));
        }

        $intNode = $objSession->get($strKey);

        if ($intNode < 1)
        {
            return;
        }

        // Check the path (thanks to Arnaud Buchoux)
        if (\Validator::isInsecurePath($intNode))
        {
            throw new \RuntimeException('Insecure path ' . $intNode);
        }

        $arrIds   = array();
        $arrLinks = array();
        $objUser  = \BackendUser::getInstance();

        // Generate breadcrumb trail
        if ($intNode)
        {
            $intId = $intNode;
            $objDatabase = \Database::getInstance();

            do
            {
                $objPage = $objDatabase->prepare("SELECT * FROM tl_page WHERE id=?")
                ->limit(1)
                ->execute($intId);

                if ($objPage->numRows < 1)
                {
                    // Currently selected page does not exits
                    if ($intId == $intNode)
                    {
                        $objSession->set($strKey, 0);

                        return;
                    }

                    break;
                }

                $arrIds[] = $intId;

                // No link for the active page
                if ($objPage->id == $intNode)
                {
                    $arrLinks[] = \Backend::addPageIcon($objPage->row(), '', null, '', true) . ' ' . $objPage->title;
                }
                else
                {
                    $arrLinks[] = \Backend::addPageIcon($objPage->row(), '', null, '', true) . ' <a href="' . \Controller::addToUrl('node='.$objPage->id) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">' . $objPage->title . '</a>';
                }

                // Do not show the mounted pages
                if (!$objUser->isAdmin && $objUser->hasAccess($objPage->id, 'pagemounts'))
                {
                    break;
                }

                $intId = $objPage->pid;
            }
            while ($intId > 0 && $objPage->type != 'root');
        }

        // Check whether the node is mounted
        if (!$objUser->hasAccess($arrIds, 'pagemounts'))
        {
            $objSession->set($strKey, 0);

            \System::log('Page ID '.$intNode.' was not mounted', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = array($intNode);

        // Add root link
        $arrLinks[] = \Helper::getIconHtml('pagemounts.gif') .' <a href="' . \Controller::addToUrl('node=0') . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';
        $arrLinks = array_reverse($arrLinks);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] .= '

        <ul class="breadcrumb">
            <li>' . implode(' &gt; </li><li>', $arrLinks) . '</li>
        </ul>';
   }

    /**
     * Add an image to each page in the tree
     *
     * @param array          $row
     * @param string         $label
     * @param \DataContainer $dc
     * @param string         $imageAttribute
     * @param boolean        $blnReturnImage
     * @param boolean        $blnProtected
     *
     * @return string
     */
    public static function addPageIcon($row, $label, \DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false)
    {
        if ($blnProtected)
        {
            $row['protected'] = true;
        }

        $image = \Controller::getPageStatusIcon((object) $row);
        $imageAttribute = trim($imageAttribute . ' data-icon="' . \Controller::getPageStatusIcon((object) array_merge($row, array('published'=>'1'))) . '" data-icon-disabled="' . \Controller::getPageStatusIcon((object) array_merge($row, array('published'=>''))) . '"');

        // Return the image only
        if ($blnReturnImage)
        {
            return \Helper::getIconHtml($image, '', $imageAttribute);
        }

        // Mark root pages
        if ($row['type'] == 'root' || \Input::get('do') == 'article')
        {
            $label = '<strong>' . $label . '</strong>';
        }

        // Add the breadcrumb link
        $label = '<a href="' . \Controller::addToUrl('node='.$row['id']) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">' . $label . '</a>';

        // Return the image
        return '<a href="contao/main.php?do=feRedirect&amp;page='.$row['id'].'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['view']).'"' . (($dc->table != 'tl_page') ? ' class="tl_gray"' : '') . ' target="_blank">'.\Helper::getIconHtml($image, '', $imageAttribute).'</a> '.$label;
    }

    /**
     * Add a breadcrumb menu to the file tree
     *
     * @param string $strKey
     *
     * @throws \RuntimeException
     */
    public static function addFilesBreadcrumb($strKey='tl_files_node')
    {
        $objSession = \Session::getInstance();

        // Set a new node
        if (isset($_GET['node']))
        {
            // Check the path (thanks to Arnaud Buchoux)
            if (\Validator::isInsecurePath(\Input::get('node', true)))
            {
                throw new \RuntimeException('Insecure path ' . \Input::get('node', true));
            }

            $objSession->set($strKey, \Input::get('node', true));
            \Controller::redirect(preg_replace('/(&|\?)node=[^&]*/', '', \Environment::get('request')));
        }

        $strNode = $objSession->get($strKey);

        if ($strNode == '')
        {
            return;
        }

        // Check the path (thanks to Arnaud Buchoux)
        if (\Validator::isInsecurePath($strNode))
        {
            throw new \RuntimeException('Insecure path ' . $strNode);
        }

        // Currently selected folder does not exist
        if (!is_dir(TL_ROOT . '/' . $strNode))
        {
            $objSession->set($strKey, '');

            return;
        }

        $objUser  = \BackendUser::getInstance();
        $strPath  = \Config::get('uploadPath');
        $arrNodes = explode('/', preg_replace('/^' . preg_quote(\Config::get('uploadPath'), '/') . '\//', '', $strNode));
        $arrLinks = array();

        // Add root link
        $arrLinks[] = \Helper::getIconHtml('filemounts.gif') . ' <a href="' . \Controller::addToUrl('node=') . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';

        // Generate breadcrumb trail
        foreach ($arrNodes as $strFolder)
        {
            $strPath .= '/' . $strFolder;

            // Do not show pages which are not mounted
            if (!$objUser->hasAccess($strPath, 'filemounts'))
            {
                continue;
            }

            // No link for the active folder
            if ($strPath == $strNode)
            {
                $arrLinks[] = \Helper::getIconHtml('folderC.gif') . $strFolder;
            }
            else
            {
                $arrLinks[] = \Helper::getIconHtml('folderC.gif') . ' <a href="' . \Controller::addToUrl('node='.$strPath) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">' . $strFolder . '</a>';
            }
        }

        // Check whether the node is mounted
        if (!$objUser->hasAccess($strNode, 'filemounts'))
        {
            $objSession->set($strKey, '');

            \System::log('Folder ID '.$strNode.' was not mounted', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'] = array($strNode);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_files']['list']['sorting']['breadcrumb'] .= '

        <ul class="breadcrumb">
            <li>' . implode(' &gt; </li><li>', $arrLinks) . '</li>
        </ul>';
    }
}
