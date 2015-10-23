<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'ContaoMaterial',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'ContaoMaterial\Message'            => 'system/modules/contao-material/classes/Message.php',
	'ContaoMaterial\Helper'             => 'system/modules/contao-material/classes/Helper.php',
	'ContaoMaterial\Database\Installer' => 'system/modules/contao-material/classes/Installer.php',

	// Drivers
	'ContaoMaterial\DC_Table'           => 'system/modules/contao-material/drivers/DC_Table.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_confirm'       => 'system/modules/contao-material/templates',
	'be_forbidden'     => 'system/modules/contao-material/templates',
	'be_no_layout'     => 'system/modules/contao-material/templates',
	'be_pagination'    => 'system/modules/contao-material/templates',
	'be_password'      => 'system/modules/contao-material/templates',
	'be_install'       => 'system/modules/contao-material/templates',
	'be_rebuild_index' => 'system/modules/contao-material/templates',
	'be_widget_chk'    => 'system/modules/contao-material/templates',
	'be_no_forward'    => 'system/modules/contao-material/templates',
	'be_picker'        => 'system/modules/contao-material/templates',
	'be_welcome'       => 'system/modules/contao-material/templates',
	'be_widget'        => 'system/modules/contao-material/templates',
	'be_main'          => 'system/modules/contao-material/templates',
	'be_live_update'   => 'system/modules/contao-material/templates',
	'be_maintenance'   => 'system/modules/contao-material/templates',
	'be_wildcard'      => 'system/modules/contao-material/templates',
	'be_switch'        => 'system/modules/contao-material/templates',
	'be_widget_rdo'    => 'system/modules/contao-material/templates',
	'be_no_page'       => 'system/modules/contao-material/templates',
	'be_widget_pw'     => 'system/modules/contao-material/templates',
	'be_navigation'    => 'system/modules/contao-material/templates',
	'be_no_root'       => 'system/modules/contao-material/templates',
	'be_login'         => 'system/modules/contao-material/templates',
	'be_no_active'     => 'system/modules/contao-material/templates',
	'be_popup'         => 'system/modules/contao-material/templates',
	'be_changelog'     => 'system/modules/contao-material/templates',
	'be_help'          => 'system/modules/contao-material/templates',
	'be_incomplete'    => 'system/modules/contao-material/templates',
	'be_diff'          => 'system/modules/contao-material/templates',
	'be_unavailable'   => 'system/modules/contao-material/templates',
	'be_referer'       => 'system/modules/contao-material/templates',
	'be_preview'       => 'system/modules/contao-material/templates',
	'be_purge_data'    => 'system/modules/contao-material/templates',
	'be_message'       => 'system/modules/contao-material/templates',
	'be_error'         => 'system/modules/contao-material/templates',
));
