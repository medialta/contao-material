<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   ContaoMaterial
 * @author    Medialta
 * @license   GNU/LGPL
 * @copyright Medialta 2015
 */


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['postLogin'][] = array('Helper', 'postLogin');

/**
 * Cover image
 */
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['faq'] = '/system/modules/contao-material/assets/img/cover_faq.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['settings'] = '/system/modules/contao-material/assets/img/cover_settings.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['files'] = '/system/modules/contao-material/assets/img/cover_files.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['calendar'] = '/system/modules/contao-material/assets/img/cover_calendar.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['news'] = '/system/modules/contao-material/assets/img/cover_news.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['themes'] = '/system/modules/contao-material/assets/img/cover_themes.jpg';

unset($GLOBALS['TL_JAVASCRIPT']['mcw']);