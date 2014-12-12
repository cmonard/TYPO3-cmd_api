<?php

namespace CMD\CmdApi;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christophe Monard <contact@cmonard.fr>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Librairie de fonctions commune aux extensions
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * methode d'appel:
 * 	$cmdLib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CMD\\CmdApi\\Lib', $this[[[, $prefixArray], $dateArray], $wrap]);
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   77: class Lib
 *  123:        public function __construct(&$caller, $prefixArray = array(), $dateArray = array(), wrap = '')
 *  154:        public function initFlexform($flex = '')
 *  185:        public function getTemplate($type)
 *  226:        public function getGlobalMarker($content)
 *  252:        public function getLanguage($content)
 *  276:        public function getPiVars($content)
 *  328:        public function getTCALanguage($content, $table = '', $tableInMarker = FALSE)
 *  360:        public function insertLanguagesAndClean($content, $tableArray = array())
 *  395:        public function subpartContent($content, $subpart, $keep = TRUE)
 *  406:        public function getUrl($url, $insertSiteUrl = FALSE)
 *  450:        public function generateMarkersFromTableRow($table, $row, $tableInMarker = FALSE, $generateLabel = FALSE, $getOL = TRUE, $localizedAsUID = FALSE, $nbRes = 1)
 *  536:        protected function getRowLanguage($table, $row)
 *  554:        public function drawDoubleSelectBox($table, $field, $Ftable, $label, $MM = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $row = '', $getOL = TRUE)
 *  572:        public function drawSimpleSelectBox($table, $field, $Ftable, $label, $pushEmpty = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $getOL = TRUE)
 *  587:        public function drawRTE(&$markerArray, $table, $field, $value = '', $tableInMarker = FALSE) (need rtehtmlarea)
 *  600:        public function getRTE($table, $row, $field) (need rtehtmlarea)
 *  613:        public function generateInputMarkersFromTable($table, $pid = 0, $row = array(), $tableInMarker = FALSE)
 *  626:        public function getRequiredMarkerForTable($table, $label = 1, $tableInMarker = FALSE)
 *  639:        public function testFieldForTable($table, &$row, &$evaluatedRow)
 *  653:        public function getListGetPageBrowser($nbPage, $pointerName = 'pointer') (need pagebrowser)
 *  686:        public function initXajax($functions = array()) (need xajax)
 *  700:        public function getXajaxFunction($funcName, $method, $formName = '', $otherParams = '') (need xajax)
 *  712:        public function setXajaxAssign($response = array()) (need xajax)
 *  727:        public function quoteForLike($fields, $table, $value, $pos = 'end', $reverseSearch = FALSE, $escapeString = TRUE)
 *  738:        public function cleanIntWhere($field, $value)
 *  749:        public function cleanIntFind($field, $value)
 *
 * TOTAL FUNCTIONS: 26
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Lib {

        // Variable interne
        protected $caller = object;
        protected $siteURL;
        protected $contentOL = 1;
        protected $flexInitialised = FALSE;
        public $localcObj;
        protected $optionSplitArray = array();
        protected $optionSplitCounter = array();
        protected $wrap = '###|###'; // wrap pour les marqueurs
        protected $prefix = array(
            'll' => 'label_', // proviens du locallang
            'tca' => 'tca_', // proviens de la TCA de la table
            'input' => 'input_', // nom du champ sur lequel appliquer le form input
            'pivars' => 'pivars_', // les pivars
            'check' => 'checked_', // les pivars type radio, select, checkbox
            'field' => 'field_', // valeur du form input
            'required' => 'required_', // proviens de la TCA de la table, indique les champs marqué required
            'local' => 'localCobj_', // marker personnalisé spécifique à chaque ligne
            'global' => 'globalCobj_', // marker personnalisé global de fin de traitement
        );
        protected $dateFormat = array(
            'active' => FALSE,
            'date' => '%d/%m/%Y',
            'datetime' => '%d/%m/%Y %H:%M',
            'time' => '%H:%M',
            'timesec' => '%H:%M:%S',
            'weekStartsMonday' => 1,
            'inputFieldLabel' => '...',
        );
        public $markerFunc = array(
            'getGlobalMarker',
            'getLanguage',
            'getPiVars',
            'getTCALanguage',
        );

        /**
         * Constructeur de la class
         *
         * @param	object		$caller: plugin appellant
         * @param	array		$prefixArray: pour chaque type de préfix on spécifie le texte a ajouter (ex: ll => label_)
         * @param	array		$dateArray: pour chaque type de date on spécifie le format, permet aussi désactiver l'utilisation du calendrier
         * @return	void
         */
        public function __construct(&$caller = object, $prefixArray = array(), $dateArray = array(), $wrap = '') {
                $this->caller = $caller; // caller object
                $this->siteURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL'); // Current site url
                if ($caller->contentOL) {
                        $this->contentOL = $caller->contentOL;
                } // Content overlay for extension
                $this->localcObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
                if (is_array($prefixArray) && count($prefixArray) > 0) {
                        foreach ($prefixArray as $prefix => $value) {
                                $this->prefix[$prefix] = $value;
                        }
                }
                if (is_array($dateArray) && count($dateArray) > 0) {
                        foreach ($dateArray as $date => $format) {
                                $this->dateFormat[$date] = $format;
                        }
                }
                if ($wrap != '') {
                        $this->wrap = $wrap;
                }
                //on appel le hook de fin de traitement
                $hookConf = array();
                Api::getHook('init', $hookConf, $this->caller);
        }

        /**
         * Cette fonction initialise le flexform du plugin
         *
         * @param	object		$flex: Flexform qu'on souhaite utiliser si différent du flexform de tt_content
         * @return	void
         */
        public function initFlexform($flex = '') {
                if (!$this->flexInitialised) {
                        if ($flex == '') {
                                $this->caller->pi_initPIflexForm();
                        }
                        // Assign the flexform data to a local variable for easier access
                        $piFlexForm = $flex == '' ? $this->caller->cObj->data['pi_flexform'] : GeneralUtility::xml2array($flex);
                        if (is_array($piFlexForm['data'])) {
                                foreach ($piFlexForm['data'] as $sheet => $data) {
                                        foreach ($data as $value) {
                                                foreach (\array_keys($value) as $key) {
                                                        $this->caller->conf['flexform.'][$sheet . '.'][$key] = $this->caller->pi_getFFvalue($piFlexForm, $key, $sheet);
                                                }
                                        }
                                }
                                if ($flex == '') {
                                        $this->flexInitialised = TRUE;
                                } // on ne déclare initialisé que le flex de tt_content
                        }
                }
        }

        /**
         * Fonction récupérant le template dans le flexform, puis en TS si pas de flexform, puis par valeur par defaut si pas de TS.
         *
         * @param	array		$type: liste des type de conf possible séparé par des virgules (flex : array(uploadFolder, sheet, key) du flexform, TS: path TS, default: chaine directe)
         * 								eg: array('flex' => array('uploadFolder' => 'uploads/tx_extkey/', 'sheet' => 'sDEF', 'key' => 'template'),
         *                                                                        'TS' => 'path.to.template',
         *                                                                        'default' => 'EXT:ext_path/dir/template.html')
         * @return	template récupéré
         */
        public function getTemplate($type) {
                $template = '';
                if (count($type) > 0) {
                        if ($this->flexInitialised && $type['flex'] && is_array($type['flex'])) {
                                $template = $this->localcObj->fileResource($type['flex']['uploadFolder'] . $this->caller->conf['flexform.'][$type['flex']['sheet'] . '.'][$type['flex']['key']]);
                        }
                        if ($template == '') {
                                if ($type['TS'] && $type['TS'] != '') {
                                        $pathTS = GeneralUtility::trimExplode('.', $type['TS']);
                                        $lastkey = $pathTS[(count($pathTS) - 1)];
                                        $template = $this->caller->conf;
                                        foreach ($pathTS as $path) {
                                                if ($path != $lastkey) {
                                                        $path.= '.';
                                                } // tant qu'on est pas au dernier élément on ajoute un . afin d'obtenir le bon chemin TS
                                                if ($template[$path]) {
                                                        $template = $template[$path];
                                                } else {
                                                        $template = '';
                                                        break;
                                                }
                                        }
                                }
                                if ($template == '' && $type['default'] && $type['default'] != '') {
                                        $template = $type['default'];
                                }
                                // une fois les tests fini on récupère le template
                                if ($template != '') {
                                        $template = $this->localcObj->fileResource($template);
                                }
                        }
                }
                return $template;
        }

        /**
         * Fonction générant les marker global du template et les insérant dans le code
         *
         * @param	string		$content: Contenu à traiter
         * @return	Contenu généré
         */
        public function getGlobalMarker($content) {
                $globalMarker = array();
                // getting globalcObj
                if (isset($this->caller->conf['globalCobj.']) && count($this->caller->conf['globalCobj.'])) {
                        foreach ($this->caller->conf['globalCobj.'] as $key => $name) {
                                if (!strpos($key, '.')) {
                                        $globalMarker[$this->prefix['global'] . $key] = $this->localcObj->cObjGetSingle($name, $this->caller->conf['globalCobj.'][$key . '.']);
                                }
                        }
                }

                //substitute des markers
                if (count($globalMarker) > 0) {
                        $content = $this->localcObj->substituteMarkerArray($content, $globalMarker, $this->wrap, TRUE);
                }

                // on renvoie le template préparé
                return $content;
        }

        /**
         * Fonction générant les marker de langues db et les insérant dans le code
         * 
         * @param	string		$content: Contenu à traiter
         * @return	Contenu généré
         */
        public function getLanguage($content) {
                $llMarker = array();
                $llConf = &$this->caller->conf['ll.'];
                //on rempli le tableau des locallangs DB
                if (isset($this->caller->LOCAL_LANG['default'])) {
                        foreach ($this->caller->LOCAL_LANG['default'] as $key => $label) {
                                $marker = str_replace('.', '_', $this->prefix['ll'] . $key);
                                $llMarker[$marker] = isset($llConf[$key . '.']) ? $this->localcObj->stdWrap($this->caller->pi_getLL($key), $llConf[$key . '.']) : $this->caller->pi_getLL($key);
                        }
                }

                //substitute des locallangs
                $content = $this->localcObj->substituteMarkerArray($content, $llMarker, $this->wrap, TRUE);

                // on renvoie le template préparé
                return $content;
        }

        /**
         * Fonction générant les marker de piVars et les insérant dans le code
         *
         * @param	string		$content: Contenu à traiter
         * @return	Contenu généré
         */
        public function getPiVars($content) {
		$pivarsMarker = $excludeFields = array();

                // cas des multiple values
                if ($this->caller->conf['multipleFields'] && $this->caller->conf['multipleFields'] != '') {
                        foreach (t3lib_div::trimExplode(',', $this->caller->conf['multipleFields']) as $excludeField) {
                                $term = '';
                                list($field, $type) = t3lib_div::trimExplode(':', $excludeField, FALSE, 2);
                                $excludeFields[] = $field;
                                switch ($type) {
                                        case 'select':
                                                $term = 'selected';
                                                break;
                                        case 'radio':
                                        case 'check':
                                                $term = 'checked';
                                                break;
                                }
                                if ($term != '' && isset($this->caller->piVars[$field])) {
                                        if (is_array($this->caller->piVars[$field])) {
                                                foreach ($this->caller->piVars[$field] as $value)
                                                        $pivarsMarker[$this->prefix['check'].$field.'_'.$value] = ' '.$term.'="'.$term.'"';
                                        } else {
                                                $pivarsMarker[$this->prefix['check'].$field.'_'.$this->caller->piVars[$field]] = ' '.$term.'="'.$term.'"';
                                        }
                                }

                        }
                }

                // mise en place des piVars
		foreach ($this->caller->piVars as $field => $value) {
                        if (!in_array($field, $excludeFields)) {
                                $pivarsMarker[$this->prefix['pivars'] . $field] = $value;
                        }
                }

                //substitute des piVars
                $content = $this->localcObj->substituteMarkerArray($content, $pivarsMarker, $this->wrap, TRUE);

                // on renvoie le template préparé
                return $content;
        }

        /**
         * Fonction générant les marker de langues de la tca et les insérant dans le code
         * 
         * @param	string		$content: Contenu à traiter
         * @param	string		$table: nom de la table dans laquelle récupérer les languages
         * @param	bool		$tableInMarker: Doit on ajouter le nom de la table dans le marker
         * @return	Contenu généré
         */
        public function getTCALanguage($content, $table = '', $tableInMarker = FALSE) {
                if ($table == '') {
                        return $content;
                }
                $tcaMarker = array();
                $tcaConf = &$this->caller->conf['tcall.'][$table . '.'];
                //on rempli le tableau des locallangs TCA
                foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $config) {
                        if (isset($config['label'])) {
                                $mark = $this->prefix['tca'] . ($tableInMarker ? $table . '_' : '') . $field;
                                $label = $GLOBALS['TSFE']->sL($config['label']);
                                $tcaMarker[$mark] = isset($tcaConf[$field . '.']) ? $this->localcObj->stdWrap($label, $tcaConf[$field . '.']) : $label;
                        }
                }

                //substitute des locallangs
                $content = $this->localcObj->substituteMarkerArray($content, $tcaMarker, $this->wrap, TRUE);

                // on renvoie le template préparé
                return $content;
        }

        /**
         * Fonction insérant les marker de langue finaux et nettoyage le code des balises non géré (insert le marker prefixID ainsi que form_action)
         * (reviens a appeller successivement : getLanguage, getPiVars, getTCALanguage et cleanTemplate)
         *
         * @param	string		$content: Contenu à traiter
         * @param	array		$tableArray: Liste des tables dont le language doit provenir de la TCA. Les tables on pour valeur TRUE ou FALSE si on veut utiliser le nom dans le marker
         *                                      ou non [eg. array('fe_users' => TRUE, 'tt_content' => FALSE)]
         * @param	array		$current: tableau des paramettre à conserver dans l'url d'un form action
         * @return	Contenu généré
         */
        public function insertLanguagesAndClean($content, $tableArray = array(), $current = array()) {
                foreach ($this->markerFunc as $funcName) {
                        if (method_exists($this, $funcName)) {
                                if ($funcName == 'getTCALanguage') {
                                        if (count($tableArray) > 0) {
                                                foreach ($tableArray as $table => $tableInMarker) {
                                                        $content = $this->$funcName($content, $table, $tableInMarker);
                                                }
                                        }
                                } else {
                                        $content = $this->$funcName($content);
                                }
                        }
                }
                $content = $this->localcObj->substituteMarker($content, '###PREFIXID###', $this->caller->prefixId);
                $content = $this->localcObj->substituteMarker($content, '###FORM_ACTION###', $this->localcObj->currentPageUrl());
                $content = $this->localcObj->substituteMarker($content, '###FORM_ACTION_CURRENT###', $this->localcObj->currentPageUrl($current));
                $content = Api::cleanTemplate($content);
                if ($this->caller->conf['wrapInBaseClass'] && $this->caller->conf['wrapInBaseClass'] == 1) {
                        $content = $this->caller->pi_wrapInBaseClass($content);
                }
                if ($this->caller->conf['contentStdWrap.']) {
                        $content = $this->localcObj->stdWrap($content, $this->caller->conf['contentStdWrap.']);
                }
                return $content;
        }

        /**
         * Fonction supprimant ou laissant active un subpart donné
         * 
         * @param	string		$content: Contenu à traiter
         * @param	string		$subpart: le nom du subpart à traiter
         * @param	bool		$keep: TRUE on conserve, FALSE on supprime le subpart
         * @return	Contenu généré
         */
        public function subpartContent($content, $subpart, $keep = TRUE) {
                return Api::subpartContent($content, $subpart, $keep);
        }

        /**
         * Fonction testant et retournant une URL pour typo3
         *
         * @param	string		$url: L'URL à traiter
         * @param	array		$insertSiteUrl: Si vrai, ajoute l'adresse de base du site à l'URL
         * @return	L'URL généré
         */
        public function getUrl($url, $insertSiteUrl = FALSE) {
                $ElementBrowser = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser');
                $curUrlInfo = $ElementBrowser->parseCurUrl($this->siteURL . '?id=' . $url, $this->siteURL);
                if ($curUrlInfo['pageid'] == 0 && $url) { // pageid == 0 means that this is not an internal (page) link
                        if (@file_exists(PATH_site . rawurldecode($url))) { // check if this is a link to a file
                                if (GeneralUtility::isFirstPartOfStr($url, PATH_site)) {
                                        $currentLinkParts[0] = substr($url, strlen(PATH_site));
                                }
                                $curUrlInfo = $ElementBrowser->parseCurUrl($this->siteURL . $url, $this->siteURL);
                        } elseif (strstr($url, '@')) { // check for email link
                                if (GeneralUtility::isFirstPartOfStr($url, 'mailto:')) {
                                        $currentLinkParts[0] = substr($url, 7);
                                }
                                $curUrlInfo = $ElementBrowser->parseCurUrl('mailto:' . $url, $this->siteURL);
                        } else { // nothing of the above. this is an external link
                                if (strpos($url, '://') === FALSE) {
                                        $currentLinkParts[0] = 'http://' . $url;
                                }
                                $curUrlInfo = $ElementBrowser->parseCurUrl($currentLinkParts[0], $this->siteURL);
                        }
                } elseif (!$url) {
                        $curUrlInfo = array();
                } else {
                        $curUrlInfo = $ElementBrowser->parseCurUrl($this->siteURL . '?id=' . $url, $this->siteURL);
                }
                //parsing and returning the link
                if ($curUrlInfo['act'] == 'page') {
                        $returnUrl = ($insertSiteUrl ? $this->siteURL : '') . $this->localcObj->typoLink_URL(array('parameter' => $curUrlInfo['pageid']));
                } else {
                        $returnUrl = ($curUrlInfo['act'] != 'mail' && $curUrlInfo['act'] != 'url' && $curUrlInfo['act'] != 'spec' && $insertSiteUrl ? $this->siteURL : '') . $curUrlInfo['info'];
                }
                return $returnUrl;
        }

        /**
         * Fonction générant les markers à partir des champs du tableau passé en paramètre
         * 
         * @param	string		$table: Nom de la table
         * @param	array		$row: Ligne courrante de la table
         * @param	bool		$tableInMarker: Doit-on mettre le nom de la table dans le marker
         * @param	bool		$generateLabel: Doit-on créer le marker pour le label du champ
         * @param	bool		$getOL: Doit-on récupérer la langue
         * @return	le tableau de marker généré pour la ligne courrante
         */
        public function generateMarkersFromTableRow($table, $row, $tableInMarker = FALSE, $generateLabel = FALSE, $getOL = TRUE, $localizedAsUID = FALSE, $nbRes = 1) {
                //récup de la ligne dans la langue courante
                if ($getOL) {
                        $row = Api::getRowLanguage($table, $row, $this->contentOL);
                }
                if (!$row) {
                        return FALSE;
                }
                if ($getOL && $localizedAsUID && $row['_LOCALIZED_UID']) {
                        $row['uid'] = $row['_LOCALIZED_UID'];
                }
                // on déclare notre nouvelle ligne
                $this->localcObj->start($row, $table);

                // gestion de l'optionSplit
                if (!isset($this->optionSplitCounter['generateMarkersFromTableRow']['cObj'][$table]) && $this->caller->conf['cObj.'][$table . '.']) {
                        if ($nbRes > 1) {
                                $splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($this->caller->conf['cObj.'][$table . '.'], $nbRes);
                        } else {
                                $splitConf = array(0 => $this->caller->conf['cObj.'][$table . '.']);
                        }
                        $this->optionSplitArray['generateMarkersFromTableRow']['cObj'][$table] = $splitConf;
                        $this->optionSplitCounter['generateMarkersFromTableRow']['cObj'][$table] = -1;
                }
                if (!isset($this->optionSplitCounter['generateMarkersFromTableRow']['stdWrap'][$table]) && $this->caller->conf[$table . '.']) {
                        if ($nbRes > 1) {
                                $splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($this->caller->conf[$table . '.'], $nbRes);
                        } else {
                                $splitConf = array(0 => $this->caller->conf[$table . '.']);
                        }
                        $this->optionSplitArray['generateMarkersFromTableRow']['stdWrap'][$table] = $splitConf;
                        $this->optionSplitCounter['generateMarkersFromTableRow']['stdWrap'][$table] = -1;
                }
                // raccourci
                //var_dump($table, $row, $tableInMarker, $generateLabel, $getOL, $localizedAsUID, $nbRes, $this->caller->conf, $this->optionSplitArray);
                $osa = &$this->optionSplitArray['generateMarkersFromTableRow'];
                $osc = &$this->optionSplitCounter['generateMarkersFromTableRow'];
                // on incrémente le compteur et vérifie qu'on ne dépasse pas le tableau (dans le cas  ou l'on utilise pas l'optionSplit
                if ($osa['cObj'][$table]) {
                        $osc['cObj'][$table] ++;
                        if ($osc['cObj'][$table] >= count($osa['cObj'][$table])) {
                                $osc['cObj'][$table] = 0;
                        }
                }
                if ($osa['stdWrap'][$table]) {
                        $osc['stdWrap'][$table] ++;
                        if ($osc['stdWrap'][$table] >= count($osa['stdWrap'][$table])) {
                                $osc['stdWrap'][$table] = 0;
                        }
                }

                $markerArray = array();
                //pour chaque champs on génère un marker
                foreach ($row as $field => $value) {
                        $this->localcObj->setCurrentVal($value);
                        if ($generateLabel) { // génération du marker de lang
                                $markerArray[$this->prefix['ll'] . ($tableInMarker ? $table . '_' : '') . $field] = $this->caller->pi_getLL($table . '.' . $field);
                        }
                        if ($osa['cObj'][$table][$osc['cObj'][$table]][$field]) { // génération du cObj si necessaire
                                $value = $this->localcObj->cObjGetSingle($osa['cObj'][$table][$osc['cObj'][$table]][$field], $osa['cObj'][$table][$osc['cObj'][$table]][$field . '.']);
                        }
                        // traitement de la valeur par stdWrap ou envoie de la valeur telle quelle
                        $markerArray[$this->prefix['field'] . ($tableInMarker ? $table . '_' : '') . $field] = isset($osa['stdWrap'][$table][$osc['stdWrap'][$table]][$field . '.']) ? $this->localcObj->stdWrap($value, $osa['stdWrap'][$table][$osc['stdWrap'][$table]][$field . '.']) : $value;
                }

                // getting localcObj
                if (isset($this->caller->conf['localcObj.']) && count($this->caller->conf['localcObj.'])) {
                        foreach ($this->caller->conf['localcObj.'] as $key => $name) {
                                if (!strpos($key, '.')) {
                                        $markerArray[$this->prefix['local'] . $key] = $this->localcObj->cObjGetSingle($name, $this->caller->conf['localcObj.'][$key . '.']);
                                }
                        }
                }

                //renvoie du tableau de valeur
                return $markerArray;
        }

        /**
         * Fonction interne utilisé par generateMarkersFromTableRow (peut être utilisé séparément)
         * Récupère la ligne courrante dans la langue du FO
         * 
         * @param	string		$table: nom de la table dans laquelle on récupère l'enregistrement
         * @param	array		$row: enregistrement à traiter
         * @return	La ligne courrante traduite si necessaire (ou vide)
         */
        public function getRowLanguage($table, $row) {
                return Api::getRowLanguage($table, $row, $this->contentOL);
        }

        /**
         * Fonction générant la double liste d'enregistrement sous forme de select
         * 
         * @param	string		$table: Nom de la table courante
         * @param	string		$field: Nom du champ MM à traiter
         * @param	string		$Ftable: Nom de la table destination du MM (ou FT)
         * @param	string		$label: Nom du champ de label dans la table MM
         * @param	bool		$MM: TRUE si table MM FALSE si foreign table
         * @param	string		$ajax: chaine du marker a ajouter pour gérer de l'ajax (ou eventuellement du js)
         * @param	string		$pid: Pid dans laquelle récupérer les enregistrements
         * @param	array		$row: Ligne courrante de la table
         * @param	bool		$getOL: Doit-on récupérer la langue
         * @return	le SelectorBox construit
         */
        public function drawDoubleSelectBox($table, $field, $Ftable, $label, $MM = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $row = '', $getOL = TRUE) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                return $FieldsRender->drawDoubleSelectBox($table, $field, $Ftable, $label, $MM, $ftWhere, $ajax, $pid, $row, $getOL);
        }

        /**
         * Fonction générant la liste simple d'enregistrement sous forme de select
         * 
         * @param	string		$table: Nom de la table courante
         * @param	string		$field: Nom du champ MM à traiter
         * @param	string		$Ftable: Nom de la table destination du MM (ou FT)
         * @param	string		$label: Nom du champ de label dans la table MM
         * @param	bool		$pushEmpty: Doit-on mettre une option vide en début de selectbox
         * @param	string		$ajax: chaine du marker a ajouter pour gérer de l'ajax (ou eventuellement du js)
         * @param	string		$pid: Pid dans laquelle récupérer les enregistrements
         * @param	bool		$getOL: Doit-on récupérer la langue
         * @return	le SelectorBox construit
         */
        public function drawSimpleSelectBox($table, $field, $Ftable, $label, $pushEmpty = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $getOL = TRUE) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                return $FieldsRender->drawSimpleSelectBox($table, $field, $Ftable, $label, $pushEmpty, $ftWhere, $ajax, $pid, $getOL);
        }

        /**
         * Permet de construire le RTE
         *
         * @param	array		$markerArray: Tableau de marker a surcharger
         * @param	string		$table: Nom de la table
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: Valeur du champ RTE
         * @param	array		$tableInMarker: le nom de la table doit-il figurer dans le marker
         * @return  void
         */
        public function drawRTE(&$markerArray, $table, $field, $value = '', $tableInMarker = FALSE) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                $FieldsRender->drawRTE($markerArray, $table, $field, $value, $tableInMarker);
        }

        /**
         * Retourne le RTE pour la sauvegarde en BD
         *
         * @param	string		$table: Nom de la table
         * @param	array		$row: array de l'enregistrement courant
         * @param	string		$field: Nom du champ de la table
         * @return  void
         */
        public function getRTE($table, &$row, $field) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                $FieldsRender->getRTE($table, $row, $field);
        }

        /**
         * Fonction générant les markers de formulaire à partir des champs de la table passé en paramètre
         *
         * @param	string		$table: Nom de la table courante
         * @param	array		$row: Ligne courante à utilisé (lors d'edition par exemple)
         * @param	bool		$tableInMarker: Doit-on mettre le nom de la table dans le marker
         * @return	le tableau de marker généré
         */
        public function generateInputMarkersFromTable($table, $row = array(), $tableInMarker = FALSE) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                return $FieldsRender->generateInputMarkersFromTable($table, $row, $tableInMarker);
        }

        /**
         * Fonction générant les markers de champ obligatoire en fonction de la TCA
         *
         * @param	string		$table: Nom de la table courante
         * @param	array		$label: label à utiliser pour indiquer que le champ est obligatoire
         * @param	bool		$tableInMarker: Doit-on mettre le nom de la table dans le marker
         * @return	le tableau de marker généré
         */
        public function getRequiredMarkerForTable($table, $label = 1, $tableInMarker = FALSE) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                return $FieldsRender->getRequiredMarkerForTable($table, $label, $tableInMarker);
        }

        /**
         * Fonction testant les champs d'une table afin de savoir si la valeur est correcte
         * 
         * @param	string		$table: Nom de la table courante
         * @param	array		$row: Ligne courante à tester
         * @param	array		$evaluatedRow: Ligne courante évalué au valeur de la TCA
         * @return	FALSE si pas d'erreur, array des champs en erreur à l'inverse
         */
        public function testFieldForTable($table, &$row, &$evaluatedRow) {
                $FieldsRender = GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this->caller, $this->prefix, $this->dateFormat, $this->wrap);
                return $FieldsRender->testFieldForTable($table, $row, $evaluatedRow);
        }

        /**
         * Fonction appelant l'extension pagebrowse pour générer un page browser
         *
         * @param	integer		$nbPage: Nombre de page du PB
         * @param	array		$pointerName: le nom du pointer de page
         * @param	string		$templateFile: le chemin du fichier de template à utiliser
         * @param	string		$extraQueryString: La chaine additionnalParams des liens
         * @return	le Page Browser construit
         */
        public function getListGetPageBrowser($nbPage, $pointerName = 'pointer', $templateFile = '', $extraQueryString = '') {
                if (!ExtensionManagementUtility::isLoaded('pagebrowse')) {
                        return 'pagebrowse needed!';
                }
                $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];
                unset($conf['pageParameterName'], $conf['numberOfPages']);
                $conf+= array(
                    'pageParameterName' => $this->caller->prefixId . '|' . $pointerName,
                    'numberOfPages' => $nbPage,
                    '_LOCAL_LANG.' => array($GLOBALS['TSFE']->lang . '.' => array(
                            'text_first' => $this->caller->pi_getLL('text_first'),
                            'text_prev' => $this->caller->pi_getLL('text_prev'),
                            'text_next' => $this->caller->pi_getLL('text_next'),
                            'text_last' => $this->caller->pi_getLL('text_last'),
                        )
                    )
                );
                if ($templateFile != '') {
                        $conf['templateFile'] = $templateFile;
                }
                if ($extraQueryString != '') {
                        $conf['extraQueryString'] = $extraQueryString;
                }
                $this->localcObj->start(array(), '');
                return $this->localcObj->cObjGetSingle('USER', $conf);
        }

        /**
         * Fonction instanciant le moteur xajax et déclanchant l'appel des procédures
         * 
         * @param	array		$functions: tableau des fonctions à instancier dans le moteur ajax array('functionPHP', etc...)
         * @return	l'objet ajax
         */
        public function initXajax($functions = array()) {
                $xajax = GeneralUtility::makeInstance('CMD\\CmdApi\\Xajax', $this->caller);
                return $xajax->initXajax($functions);
        }

        /**
         * Fonction préparant l'appel de la fonction ajax avec pour paramettre le nom du formulaire a récupérer
         * 
         * @param	string		$funcName: nom de la fonction ajax JS a appeler
         * @param	string		$method: Nom de l'évenement à utiliser pour déclancher l'appel ajax
         * @param	string		$formName: Nom du formulaire pour lequel on doit récupérer les valeurs, si NULL prefixId de l'extension appelant
         * @param	string		$otherParams: parametre supplémentaire à passer à la fonction ajax (eg. ', test1, test2')
         * @return	le script à insérer dans un onclick, onchange etc...
         */
        public function getXajaxFunction($funcName, $method, $formName = '', $otherParams = '') {
                $xajax = GeneralUtility::makeInstance('CMD\\CmdApi\\Xajax', $this->caller);
                return $xajax->getXajaxFunction($funcName, $method, $formName, $otherParams);
        }

        /**
         * Fonction instanciant et renvoyant la réponse ajax
         * attention: delai est en secondes
         *
         * @param	array		$response: tableau des reponses à préparer et à renvoyer au navigateur array(0 => array('type' => 'xxx', 'id' => 'xxx', 'method' => 'xxx', 'msg' => 'xxx'), etc...)
         * @return	le xml ajax à retourner au navigateur
         */
        public function setXajaxAssign($response = array()) {
                return Xajax::setXajaxAssign($response);
        }

        /**
         * Fonction sécurisant les variable pour le SQL
         *
         * @param	string / array		$fields: Nom du champ de la table
         * @param	string			$table: Nom de la table sur laquelle baser la recherche
         * @param	string / array		$value: valeur que doit avoir la recherche
         * @param	string			$pos: position du caractère universel de recherche
         * @param       bool                    $reverseSearch: permet de traiter les valeurs comme un tableau et non les champs
         * @param       bool                    $escapeString: La valeur doit-elle être échappé par TYPO3
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        public function quoteForLike($fields, $table, $value, $pos = 'end', $reverseSearch = FALSE, $escapeString = TRUE) {
                return Query::quoteForLike($fields, $table, $value, $pos, $reverseSearch, $escapeString);
        }

        /**
         * Fonction sécurisant et formattant une chaine d'entier pour le SQL (comparaison avec IN)
         * 
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: list des entiers
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        public function cleanIntWhere($field, $value) {
                return Query::cleanIntWhere($field, $value);
        }

        /**
         * Fonction sécurisant et formattant une chaine d'entier pour le SQL (comparaison avec un FIND_IN_SET pour chaque valeur)
         *
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: list des entiers
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        public function cleanIntFind($field, $value) {
                return Query::cleanIntFind($field, $value);
        }

}
