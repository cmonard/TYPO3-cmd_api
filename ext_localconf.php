<?php
if (!defined('TYPO3_MODE')) die('Access denied.');

// récupération de la conf
$cmd_conf = unserialize($_EXTCONF);

// on charge les custom button du mode liste
if ($cmd_conf['customButton']) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array('className' => 'CMD\\CmdApi\\Cbl\\LocalRecordList');
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][$_EXTKEY] = 'EXT:cmd_api/Classes/Cbl/LocalRecordList.php:&CMD\\CmdApi\\Cbl\\Localrecordlist_getTable';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][$_EXTKEY] = 'EXT:cmd_api/Classes/Cbl/LocalRecordList.php:&CMD\\CmdApi\\Cbl\\Localrecordlist_actions';
}

// Alternate BE sorting for tables, change by TS: mod.web_list.alternateSortingField.table = field ASC/DESC
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'][$_EXTKEY] = 'CMD\\CmdApi\\DBList\\beAlternateSorting';

?>