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
	'ContaoMaterial\FileUpload'         => 'system/modules/contao-material/classes/FileUpload.php',
	'ContaoMaterial\DropZone'           => 'system/modules/contao-material/classes/DropZone.php',
	'ContaoMaterial\DataContainer'      => 'system/modules/contao-material/classes/DataContainer.php',
	'ContaoMaterial\Message'            => 'system/modules/contao-material/classes/Message.php',
	'ContaoMaterial\Backend'            => 'system/modules/contao-material/classes/Backend.php',
	'ContaoMaterial\Helper'             => 'system/modules/contao-material/classes/Helper.php',
	'ContaoMaterial\Database\Installer' => 'system/modules/contao-material/classes/Installer.php',
	'ContaoMaterial\Versions'           => 'system/modules/contao-material/classes/Versions.php',
	'ContaoMaterial\Messages'           => 'system/modules/contao-material/classes/Messages.php',

	// Drivers
	'ContaoMaterial\DC_Table'           => 'system/modules/contao-material/drivers/DC_Table.php',
	'ContaoMaterial\DC_Formdata'        => 'system/modules/contao-material/drivers/DC_Formdata.php',
	'ContaoMaterial\DC_Folder'          => 'system/modules/contao-material/drivers/DC_Folder.php',
	'ContaoMaterial\DC_File'            => 'system/modules/contao-material/drivers/DC_File.php',

	// Widgets
	'ContaoMaterial\ChmodTable'         => 'system/modules/contao-material/widgets/ChmodTable.php',
	'ContaoMaterial\TextArea'           => 'system/modules/contao-material/widgets/TextArea.php',
	'ContaoMaterial\FileSelector'       => 'system/modules/contao-material/widgets/FileSelector.php',
	'ContaoMaterial\PageSelector'       => 'system/modules/contao-material/widgets/PageSelector.php',
	'ContaoMaterial\SelectMenu'         => 'system/modules/contao-material/widgets/SelectMenu.php',
	'ContaoMaterial\InputUnit'          => 'system/modules/contao-material/widgets/InputUnit.php',
	'ContaoMaterial\TextField'          => 'system/modules/contao-material/widgets/TextField.php',
	'ContaoMaterial\TableWizard'        => 'system/modules/contao-material/widgets/TableWizard.php',

	// Library
	'ContaoMaterial\Image'              => 'system/modules/contao-material/library/Image.php',
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
	'dev_labels'       => 'system/modules/contao-material/templates',
	'dev_autoload'     => 'system/modules/contao-material/templates',
	'be_switch'        => 'system/modules/contao-material/templates',
	'be_widget_rdo'    => 'system/modules/contao-material/templates',
	'be_no_page'       => 'system/modules/contao-material/templates',
	'be_widget_pw'     => 'system/modules/contao-material/templates',
	'be_navigation'    => 'system/modules/contao-material/templates',
	'dev_extension'    => 'system/modules/contao-material/templates',
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
