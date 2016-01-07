<?php

$GLOBALS['TL_DCA']['tl_article']['list']['label']['label_callback'] = array('tl_article_contaomaterial', 'addIcon');

class tl_article_contaomaterial extends Backend
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Add an image to each page in the tree
     *
     * @param array  $row
     * @param string $label
     *
     * @return string
     */
    public function addIcon($row, $label)
    {
        $time = \Date::floorToMinute();
        $published = ($row['published'] && ($row['start'] == '' || $row['start'] <= $time) && ($row['stop'] == '' || $row['stop'] > ($time + 60)));

        return '<a href="contao/main.php?do=feRedirect&amp;page='.$row['pid'].'&amp;article='.(($row['alias'] != '' && !Config::get('disableAlias')) ? $row['alias'] : $row['id']).'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['view']).'" target="_blank">'.Helper::getIconHtml('articles'.($published ? '' : '_').'.gif', '', 'data-icon="articles.gif" data-icon-disabled="articles_.gif"').'</a> '.$label;
    }
}