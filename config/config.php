<?php

/**
 * Contao Open Source CMS
 *
 * @author Medialta <http://www.medialta.com>
 * @package ContaoMaterial
 * @copyright Medialta
 * @license LGPL-3.0+
 */

/**
 * Version
 */
define('VERSION_CONTAO_MATERIAL', '3.5.18');

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['postLogin'][] = array('Helper', 'postLogin');

/**
 * Cover image
 */
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['news'] = 'system/modules/contao-material/assets/img/cover_news.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['calendar'] = 'system/modules/contao-material/assets/img/cover_calendar.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['faq'] = 'system/modules/contao-material/assets/img/cover_faq.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['newsletter'] = 'system/modules/contao-material/assets/img/cover_newsletter.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['form'] = 'system/modules/contao-material/assets/img/cover_form.jpg';

$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['themes'] = 'system/modules/contao-material/assets/img/cover_themes.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['page'] = 'system/modules/contao-material/assets/img/cover_page.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['tpl_editor'] = 'system/modules/contao-material/assets/img/cover_tpl_editor.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['columnset'] = 'system/modules/contao-material/assets/img/cover_columnset.jpg';

$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['member'] = 'system/modules/contao-material/assets/img/cover_member.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['mgroup'] = 'system/modules/contao-material/assets/img/cover_member.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['user'] = 'system/modules/contao-material/assets/img/cover_user.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['group'] = 'system/modules/contao-material/assets/img/cover_user.jpg';

$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['files'] = 'system/modules/contao-material/assets/img/cover_files.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['settings'] = 'system/modules/contao-material/assets/img/cover_settings.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['maintenance'] = 'system/modules/contao-material/assets/img/cover_maintenance.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['undo'] = 'system/modules/contao-material/assets/img/cover_undo.jpg';

$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['autoload'] = 'system/modules/contao-material/assets/img/cover_autoload.jpg';
$GLOBALS['CONTAO_MATERIAL']['COVER_IMAGE']['extension'] = 'system/modules/contao-material/assets/img/cover_extension.jpg';

unset($GLOBALS['TL_JAVASCRIPT']['mcw']);

/**
 * Templates added manually
 */
\TemplateLoader::addFile('dev_autoload', 'system/modules/contao-material/templates');
\TemplateLoader::addFile('dev_extension', 'system/modules/contao-material/templates');
\TemplateLoader::addFile('dev_labels', 'system/modules/contao-material/templates');
