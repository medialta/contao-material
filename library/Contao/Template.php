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

use MatthiasMullie\Minify;


/**
 * Parses and outputs template files
 *
 * The class supports loading template files, adding variables to them and then
 * printing them to the screen. It functions as abstract parent class for the
 * two core classes "BackendTemplate" and "FrontendTemplate".
 *
 * Usage:
 *
 *     $template = new BackendTemplate();
 *     $template->name = 'Leo Feyer';
 *     $template->output();
 *
 * @property string $style
 * @property array  $cssID
 * @property string $class
 * @property string $inColumn
 * @property string $headline
 * @property array  $hl
 *
 */
class Template extends \Contao\Template
{
    /**
     * Return the debug bar string
     *
     * @return string The debug bar markup
     */
    protected function getDebugBar()
    {
        $intReturned = 0;
        $intAffected = 0;

        // Count the totals (see #3884)
        if (is_array($GLOBALS['TL_DEBUG']['database_queries']))
        {
            foreach ($GLOBALS['TL_DEBUG']['database_queries'] as $k=>$v)
            {
                $intReturned += $v['return_count'];
                $intAffected += $v['affected_count'];
                unset($GLOBALS['TL_DEBUG']['database_queries'][$k]['return_count']);
                unset($GLOBALS['TL_DEBUG']['database_queries'][$k]['affected_count']);
            }
        }

        $intElapsed = (microtime(true) - TL_START);

        $strDebug = sprintf(
            "<!-- indexer::stop -->\n"
            . '<div id="contao-debug">'
            . '<p>'
                . '<span class="debug-time">Execution time: %s ms</span>'
                . '<span class="debug-memory">Memory usage: %s</span>'
                . '<span class="debug-db">Database queries: %d</span>'
                . '<span class="debug-rows">Rows: %d returned, %s affected</span>'
                . '<span class="debug-models">Registered models: %d</span>'
                . '<span id="debug-tog">&nbsp;</span>'
            . '</p>'
            . '<div><pre>',
            $this->getFormattedNumber(($intElapsed * 1000), 0),
            $this->getReadableSize(memory_get_peak_usage()),
            count($GLOBALS['TL_DEBUG']['database_queries']),
            $intReturned,
            $intAffected,
            \Model\Registry::getInstance()->count()
        );

        ksort($GLOBALS['TL_DEBUG']);

        ob_start();
        print_r($GLOBALS['TL_DEBUG']);
        $strDebug .= ob_get_contents();
        ob_end_clean();

        unset($GLOBALS['TL_DEBUG']);

        $strDebug .= '</pre></div></div>'
            . $this->generateInlineScript(
                "$(document).ready(function() {"
                    . "$('body').addClass('debug-enabled " . \Input::cookie('CONTAO_CONSOLE') . "');"
                    . "$('#debug-tog').click(function(e) {"
                        . "$('body').toggleClass('debug-closed');"
                        . "document.cookie = 'CONTAO_CONSOLE=' + ($('body').hasClass('debug-closed') ? 'debug-closed' : '') + '; path=" . (TL_PATH ?: '/') . "';"
                    . "});"
                . "});",
                ($this->strFormat == 'xhtml')
            )
            . "\n<!-- indexer::continue -->\n\n"
        ;

        return $strDebug;
    }
}