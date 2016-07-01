<?php

/**
 * Contao Open Source CMS
 *
 * @author Medialta <http://www.medialta.com>
 * @package ContaoMaterial
 * @copyright Medialta
 * @license LGPL-3.0+
 */

namespace ContaoMaterial\Database;


/**
 * Compares the existing database structure with the DCA table settings and
 * calculates the queries needed to update the database.
 */
class Installer extends \Contao\Database\Installer
{

    /**
     * Generate a HTML form with queries and return it as string
     *
     * @return string The form HTML markup
     */
    public function generateSqlForm()
    {
        $count = 0;
        $return = '';
        $sql_command = $this->compileCommands();

        if (empty($sql_command))
        {
            return '';
        }

        $_SESSION['sql_commands'] = array();

        $arrOperations = array
        (
            'CREATE'        => $GLOBALS['TL_LANG']['tl_install']['CREATE'],
            'ALTER_ADD'     => $GLOBALS['TL_LANG']['tl_install']['ALTER_ADD'],
            'ALTER_CHANGE'  => $GLOBALS['TL_LANG']['tl_install']['ALTER_CHANGE'],
            'ALTER_DROP'    => $GLOBALS['TL_LANG']['tl_install']['ALTER_DROP'],
            'DROP'          => $GLOBALS['TL_LANG']['tl_install']['DROP']
        );

        foreach ($arrOperations as $command=>$label)
        {
            if (is_array($sql_command[$command]))
            {
                // Headline
                $return .= '
    <h4>
      '.$label.'
    </h4>';

                // Check all
                $return .= '
    <ul class="collection with-header">
      <li class="collection-header">
        <input type="checkbox" id="check_all_' . $count . '" class="tl_checkbox" onclick="Backend.toggleCheckboxElements(this, \'' . strtolower($command) . '\')">
        <label for="check_all_' . $count . '" style="color:#a6a6a6"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label>
      </li>';

                // Fields
                foreach ($sql_command[$command] as $vv)
                {
                    $key = md5($vv);
                    $_SESSION['sql_commands'][$key] = $vv;

                    $return .= '
    <li class="collection-item">
      <input type="checkbox" name="sql[]" id="sql_'.$count.'" class="tl_checkbox ' . strtolower($command) . '" value="'.$key.'"'.((stristr($command, 'DROP') === false) ? ' checked="checked"' : '').'>
      <label for="sql_'.$count++.'">'.$vv.'</label>
    </li>';
                }
                $return .= '</ul>';
            }
        }

        return '
<div id="sql_wrapper">
  <table id="sql_table">'.$return.'
  </table>
</div>';
    }


}
