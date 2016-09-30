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
 * Class Helper
 */
class Helper extends \System
{
    /**
     * Post login hook
     *
     * @param \User $objUser current logged in user
     */
    public function postLogin($objUser)
    {
        $session = \Session::getInstance();
        $modules = $session->get('backend_modules');

        if (is_array($modules) && !empty($modules))
        {
            foreach ($modules as $groupName => $value)
            {
                $modules[$groupName] = 1;
            }

            $session->set('backend_modules', $modules);
        }
    }

    /**
     * Returns true if given string is an image filename
     *
     * @param string $src Icon name or filename
     *
     * @return boolean true if current icon is an image filename
     */
    public static function isImage($src)
    {
        $src = rawurldecode($src);

        if (strpos($src, '/') === false)
        {
            if (strncmp($src, 'icon', 4) === 0)
            {
                $src = 'assets/contao/images/' . $src;
            }
            else
            {
                $src = 'system/themes/' . \Backend::getTheme() . '/images/' . $src;
            }
        }

        return file_exists(TL_ROOT . '/' . $src);
    }

    /**
     * Returns true if given string is an inactive icon (not clickable)
     *
     * @param string $src Icon name or filename
     *
     * @return boolean true if current icon is inactive
     */
    public static function isInactiveIcon($src)
    {
        $inactive = false;

        if (self::isImage($src))
        {
            $filename = basename($src, strrchr($src, '.'));
            $inactive = substr($filename, -1) == '_';
        }

        return $inactive;
    }

    /**
     * Gets the active image corresponding to an inactive one
     *
     * @param string $inactiveImage Inactive image filename
     *
     * @return string Active image filename
     */
    public static function getActiveImage($inactiveImage)
    {
        $extension = strrchr($inactiveImage, '.');
        $filename = basename($inactiveImage, $extension);

        return substr($filename, 0, -1) . $extension;
    }

    /**
     * Calls formatButtonCallback for each button in a set of HTML buttons
     *
     * @param string $html HTML string containing multiple buttons
     *
     * @return string HTML string with image replaced by icon
     */
    public static function formatMultipleButtonCallback($html)
    {
        // Separates the links
        preg_match_all('/(<a.*\\/a>)+/iU', $html, $matches);

        if (isset($matches[1]))
        {
            $html = '';

            foreach ($matches[1] as $k => $match)
            {
                $html .= static::formatButtonCallback($match);
            }
        }

        return $html;
    }

    /**
     * Replaces an HTML image by the corresponding Material Design icon
     *
     * @param string $html HTML string containing image
     * @param boolean $dropdownSet true if current button is in a dropdown
     * @param string $title label for current button
     *
     * @return string HTML string with image replaced by icon
     */
    public static function formatButtonCallback($html, $dropdownSet = false, $title = '')
    {
        // Replaces image by icon
        preg_match_all('/(<img.*src=\"(.*)\".*>)/mU', $html, $matches);

        if (isset($matches[1][0]) && isset($matches[2][0]) && strlen($matches[1][0]) && strlen($matches[2][0]))
        {
            $iconFile = basename($matches[2][0]);
            $icon = self::getIconHtml($iconFile);

            if ($dropdownSet)
            {
                $icon .= $title;
            }

            $html = str_replace($matches[1][0], $icon, $html);
        }

        // Replaces title by a tooltip
        $html = preg_replace('/(.* )title(=".*"[ >].*)/mU', '$1data-position="top" data-delay="50" data-tooltip$2', $html);

        // Adds classes
        $regexClass = '/(<a[^<]* class="[^<]*)("[^<]*>)/mU';
        $classes = ($dropdownSet) ? '' : 'btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped';

        if (isset($iconFile) && strlen($classes) && in_array($iconFile, ['pasteafter.gif', 'pasteinto.gif']))
        {
            $classes .= ' paste-action -' . substr($iconFile, 5, -4);
        }

        if (preg_match($regexClass, $html))
        {
            $html = preg_replace($regexClass, '$1 ' . $classes . '$2', $html);
        }
        else
        {
            $html = preg_replace('/(<a[^<]* href="[^<]*")([^<]*>)/mU', '$1 class="' . $classes . '"$2', $html);
        }

        return $html;
    }

    /**
     * Returns a Material Design icon HTML corresponding to a Contao image
     *
     * @param string $src        The image path
     * @param string $alt        An optional alt attribute
     * @param string $attributes A string of other attributes
     *
     * @return string The icon HTML tag
     */
    public static function getIconHtml($src, $alt = '', $attributes = '')
    {
        $srcgif = str_replace('.png', '.gif', $src);
        $icon = '';
        $inactive = self::isInactiveIcon($src);

        if (self::isImage($src) || self::isImage($srcgif))
        {
            if (!isset($GLOBALS['MD_ICONS']))
            {
                require __DIR__ . '/../config/icons.php';
            }

            if (isset($GLOBALS['MD_ICONS'][$src]))
            {
                $icon = $GLOBALS['MD_ICONS'][$src];
            }
            else if (isset($GLOBALS['MD_ICONS'][basename($src)]))
            {
                $icon = $GLOBALS['MD_ICONS'][basename($src)];
            }
            else if (isset($GLOBALS['MD_ICONS'][$srcgif]))
            {
                $icon = $GLOBALS['MD_ICONS'][$srcgif];
            }
            else if (isset($GLOBALS['MD_ICONS'][basename($srcgif)]))
            {
                $icon = $GLOBALS['MD_ICONS'][basename($srcgif)];
            }
            else if ($inactive)
            {
                $activeImage = self::getActiveImage($src);

                if (isset($GLOBALS['MD_ICONS'][$activeImage]))
                {
                    $icon = $GLOBALS['MD_ICONS'][$activeImage];
                }
            }
        }

        if (strlen($icon))
        {
            $icon = '<i class="material-icons">' . $icon . '</i>';

            if ($inactive)
            {
                $icon = '<span class="inactive-option">' . $icon . '</span>';
            }
        }
        else
        {
            $icon = '<span class="old-icon-wrapper">' . self::getHtml($src, $alt, $attributes) . '</span>';
        }

        return $icon;
    }

    /**
     * Generate an image tag and return it as string
     *
     * @param string $src        The image path
     * @param string $alt        An optional alt attribute
     * @param string $attributes A string of other attributes
     *
     * @return string The image HTML tag
     */
    public static function getHtml($src, $alt='', $attributes='')
    {
        $static = TL_FILES_URL;
        $src = rawurldecode($src);
        $srcpng = str_replace('.gif', '.png', $src);

        if (strpos($src, '/') === false)
        {
            if (strncmp($src, 'icon', 4) === 0)
            {
                if (file_exists( __DIR__ . '/../assets/images/' . $srcpng)) {
                    $src = 'system/modules/contao-material/assets/images/' . $srcpng;
                } else {
                    $static = TL_ASSETS_URL;
                    $src = 'assets/contao/images/' . $src;
                }
            }
            else
            {
                if (file_exists( __DIR__ . '/../assets/images/' . $srcpng)) {
                    $src = 'system/modules/contao-material/assets/images/' . $srcpng;
                } else {
                    $src = 'system/themes/' . \Backend::getTheme() . '/images/' . $src;
                }
            }
        }

        if (!file_exists(TL_ROOT .'/'. $src))
        {
            return '';
        }

        $objFile = new \File($src, true);

        return '<img src="' . $static . \System::urlEncode($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . specialchars($alt) . '"' . (($attributes != '') ? ' ' . $attributes : '') . '>';
    }

    /**
    * Check if latest contao-material version
    *
    * @return boolean
    */
    public static function latestContaoMaterial()
    {

        $repository = 'medialta/contao-material';
        $url = 'https://api.github.com/repos/' . $repository . '/tags';

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];

        $context = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);
        $decode = json_decode($content);

        if ($decode[0])
        {
            $tag = $decode[0]->name;
            if (VERSION_CONTAO_MATERIAL != $tag)
            {
                return false;
            }
        }

        return true;
    }
}
