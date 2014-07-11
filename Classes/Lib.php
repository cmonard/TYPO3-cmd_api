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
 * Librairie de fonction commune aux extensions
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * methode d'appel:
 * 	$cmdLib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CMD\\CmdApi\\Lib', $this[[[, $prefixArray], $dateArray], $wrap]);
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   80: class Lib
 *  147:        public function __construct(&$caller, $prefixArray = array(), $dateArray = array(), wrap = '')
 *  182:        public function initFlexform($flex = '')
 *  208:        public function getTemplate($type)
 *  245:        public function getGlobalMarker($content)
 *  267:        public function getLanguage($content)
 *  290:        public function getPiVars($content)
 *  311:        public function getTCALanguage($content, $table = '', $tableInMarker = FALSE)
 *  341:        public function insertLanguagesAndClean($content, $tableArray = array())
 *  367:        public function subpartContent($content, $subpart, $keep = TRUE)
 *  381:        public function getUrl($url, $insertSiteUrl = FALSE)
 *  424:        public function generateMarkersFromTableRow($table, $row, $tableInMarker = FALSE, $generateLabel = FALSE, $getOL = TRUE, $localizedAsUID = FALSE, $nbRes = 1)
 *  498:        protected function getRowLanguage($table, $row)
 *  520:        public function drawDoubleSelectBox($table, $field, $Ftable, $label, $MM = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $row = '', $getOL = TRUE)
 *  848:        public function drawSimpleSelectBox($table, $field, $Ftable, $label, $pushEmpty = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $getOL = TRUE)
 *  895:        public function drawRTE(&$markerArray, $table, $field, $value = '', $tableInMarker = FALSE) (need rtehtmlarea)
 *  925:        public function getRTE($table, $row, $field) (need rtehtmlarea)
 *  940:        public function generateInputMarkersFromTable($table, $pid = 0, $row = array(), $tableInMarker = FALSE)
 * 1115:        public function getRequiredMarkerForTable($table, $label = 1, $tableInMarker = FALSE)
 * 1134:        public function testFieldForTable($table, &$row, &$evaluatedRow)
 * 1319:        public function getListGetPageBrowser($nbPage, $pointerName = 'pointer') (need pagebrowser)
 * 1350:        protected function getHook($hookName, &$hookConf)
 * 1376:        protected function userFunc($funcName, $funcConf)
 * 1387:        public function initXajax($functions = array()) (need xajax)
 * 1420:        public function getXajaxFunction($funcName, $method, $formName = '', $otherParams = '') (need xajax)
 * 1436:        public function setXajaxAssign($response = array()) (need xajax)
 * 1488:        public function quoteForLike($fields, $table, $value, $pos = 'end', $reverseSearch = FALSE, $escapeString = TRUE)
 * 1533:        public function cleanIntWhere($field, $value)
 * 1544:        public function cleanIntFind($field, $value)
 *
 * TOTAL FUNCTIONS: 28
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Lib {

        // Variable interne
        private $caller = object;
        private $siteURL;
        private $contentOL = 1;
        private $flexInitialised = FALSE;
        public $localcObj;
        private $optionSplitArray = array();
        private $optionSplitCounter = array();
        private $wrap = '###|###'; // wrap pour les marqueurs
        private $prefix = array(
            'll' => 'label_', // proviens du locallang
            'tca' => 'tca_', // proviens de la TCA de la table
            'input' => 'input_', // nom du champ sur lequel appliquer le form input
            'pivars' => 'pivars_', // les pivars
            'field' => 'field_', // valeur du form input
            'required' => 'required_', // proviens de la TCA de la table, indique les champs marqué required
            'local' => 'localCobj_', // marker personnalisé spécifique à chaque ligne
            'global' => 'globalCobj_', // marker personnalisé global de fin de traitement
        );
        private $dateFormat = array(
            'active' => FALSE,
            'date' => '%d/%m/%Y',
            'datetime' => '%d/%m/%Y %H:%M',
            'time' => '%H:%M',
            'timesec' => '%H:%M:%S',
            'weekStartsMonday' => 1,
            'inputFieldLabel' => '...',
        );
        private $xajax = FALSE;
        //variable du RTE
        public $RTEObj;
        public $docLarge = 0;
        public $RTEcounter = 0;
        public $formName;
        // Initial JavaScript to be printed before the form
        // (should be in head, but cannot due to IE6 timing bug)
        public $additionalJS_initial = '';
        // Additional JavaScript to be printed before the form
        // (works in Mozilla/Firefox when included in head, but not in IE6)
        public $additionalJS_pre = array();
        // Additional JavaScript to be printed after the form
        public $additionalJS_post = array();
        // Additional JavaScript to be executed on submit
        public $additionalJS_submit = array();
        public $PA = array(
            'itemFormElName' => '',
            'itemFormElValue' => '',
        );
        public $specConf = array(
            'rte_transform' => array(
                'parameters' => array('mode' => 'ts_css')
            )
        );
        public $thisConfig = array();
        public $RTEtypeVal = 'text';
        public $thePidValue;

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
                if ($caller->contentOL)
                        $this->contentOL = $caller->contentOL; // Content overlay for extension
                $this->localcObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
                if (is_array($prefixArray) && count($prefixArray) > 0)
                        foreach ($prefixArray as $prefix => $value)
                                $this->prefix[$prefix] = $value;
                if (is_array($dateArray) && count($dateArray) > 0)
                        foreach ($dateArray as $date => $format)
                                $this->dateFormat[$date] = $format;
                if ($wrap != '')
                        $this->wrap = $wrap;
                if (ExtensionManagementUtility::isLoaded('rlmp_dateselectlib')) {
                        require_once(ExtensionManagementUtility::extPath('rlmp_dateselectlib') . 'class.tx_rlmpdateselectlib.php');
                        tx_rlmpdateselectlib::includeLib();
                        $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_rlmpdateselectlib.']['calConf.']['weekStartsMonday'] = $this->dateFormat['weekStartsMonday'];
                        $this->dateFormat['active'] = TRUE;
                }
                if (ExtensionManagementUtility::isLoaded('rtehtmlarea')) {
                        $this->pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
                        $this->postvars = GeneralUtility::_POST($this->caller->prefixId);
                        $this->RTEObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\Controller\\FrontendRteController');
                }
                //on appel le hook de fin de traitement
                $this->getHook('init');
        }

        /**
         * Cette fonction initialise le flexform du plugin
         *
         * @param	object		$flex: Flexform qu'on souhaite utiliser si différent du flexform de tt_content
         * @return	void
         */
        public function initFlexform($flex = '') {
                if (!$this->flexInitialised) {
                        if ($flex == '')
                                $this->caller->pi_initPIflexForm();
                        // Assign the flexform data to a local variable for easier access
                        $piFlexForm = $flex == '' ? $this->caller->cObj->data['pi_flexform'] : GeneralUtility::xml2array($flex);
                        if (is_array($piFlexForm['data'])) {
                                foreach ($piFlexForm['data'] as $sheet => $data)
                                        foreach ($data as $lang => $value)
                                                foreach ($value as $key => $val)
                                                        $this->caller->conf['flexform.'][$sheet . '.'][$key] = $this->caller->pi_getFFvalue($piFlexForm, $key, $sheet);
                                if ($flex == '')
                                        $this->flexInitialised = TRUE; // on ne déclare initialisé que le flex de tt_content
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
                        if ($this->flexInitialised && $type['flex'] && is_array($type['flex']))
                                $template = $this->localcObj->fileResource($type['flex']['uploadFolder'] . $this->caller->conf['flexform.'][$type['flex']['sheet'] . '.'][$type['flex']['key']]);
                        if ($template == '') {
                                if ($type['TS'] && $type['TS'] != '') {
                                        $pathTS = GeneralUtility::trimExplode('.', $type['TS']);
                                        $lastkey = $pathTS[(count($pathTS) - 1)];
                                        $template = $this->caller->conf;
                                        foreach ($pathTS as $path) {
                                                if ($path != $lastkey)
                                                        $path.= '.'; // tant qu'on est pas au dernier élément on ajoute un . afin d'obtenir le bon chemin TS
                                                if ($template[$path])
                                                        $template = $template[$path];
                                                else {
                                                        $template = '';
                                                        break;
                                                }
                                        }
                                }
                                if ($template == '' && $type['default'] && $type['default'] != '')
                                        $template = $type['default'];
                                // une fois les tests fini on récupère le template
                                if ($template != '')
                                        $template = $this->localcObj->fileResource($template);
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
                if (isset($this->caller->conf['globalCobj.']) && count($this->caller->conf['globalCobj.']))
                        foreach ($this->caller->conf['globalCobj.'] as $key => $name)
                                if (!strpos($key, '.'))
                                        $globalMarker[$this->prefix['global'] . $key] = $this->localcObj->cObjGetSingle($name, $this->caller->conf['globalCobj.'][$key . '.']);

                //substitute des markers
                if (count($globalMarker) > 0)
                        $content = $this->localcObj->substituteMarkerArray($content, $globalMarker, $this->wrap, TRUE);

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
                if (isset($this->caller->LOCAL_LANG['default']))
                        foreach ($this->caller->LOCAL_LANG['default'] as $key => $label) {
                                $marker = str_replace('.', '_', $this->prefix['ll'] . $key);
                                $llMarker[$marker] = isset($llConf[$key . '.']) ? $this->localcObj->stdWrap($this->caller->pi_getLL($key), $llConf[$key . '.']) : $this->caller->pi_getLL($key);
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
                $pivarsMarker = array();
                // mise en place des piVars
                foreach ($this->caller->piVars as $field => $value)
                        $pivarsMarker[$this->prefix['pivars'] . $field] = $value;

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
                if ($table == '')
                        return $content;
                $tcaMarker = array();
                $tcaConf = &$this->caller->conf['tcall.'][$table . '.'];
                //on rempli le tableau des locallangs TCA
                foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $config)
                        if (isset($config['label'])) {
                                $mark = $this->prefix['tca'] . ($tableInMarker ? $table . '_' : '') . $field;
                                $label = $GLOBALS['TSFE']->sL($config['label']);
                                $tcaMarker[$mark] = isset($tcaConf[$field . '.']) ? $this->localcObj->stdWrap($label, $tcaConf[$field . '.']) : $label;
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
                $content = $this->getGlobalMarker($content);
                $content = $this->getLanguage($content);
                $content = $this->getPiVars($content);
                if (count($tableArray) > 0)
                        foreach ($tableArray as $table => $tableInMarker)
                                $content = $this->getTCALanguage($content, $table, $tableInMarker);
                $content = $this->localcObj->substituteMarker($content, '###PREFIXID###', $this->caller->prefixId);
                $content = $this->localcObj->substituteMarker($content, '###FORM_ACTION###', $this->localcObj->currentPageUrl());
                $content = $this->localcObj->substituteMarker($content, '###FORM_ACTION_CURRENT###', $this->localcObj->currentPageUrl($current));
                $content = Api::cleanTemplate($content);
                if ($this->caller->conf['wrapInBaseClass'] && $this->caller->conf['wrapInBaseClass'] == 1)
                        $content = $this->caller->pi_wrapInBaseClass($content);
                if ($this->caller->conf['contentStdWrap.'])
                        $content = $this->localcObj->stdWrap($content, $this->caller->conf['contentStdWrap.']);
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
                if ($keep)
                        return $this->localcObj->substituteSubpart($content, $subpart, $this->localcObj->getSubpart($content, $subpart)); // on valide le subpart
                else
                        return $this->localcObj->substituteSubpart($content, $subpart, ''); // on supprime le subparts
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
                if ($curUrlInfo['act'] == 'page')
                        $returnUrl = ($insertSiteUrl ? $this->siteURL : '') . $this->localcObj->typoLink_URL(array('parameter' => $curUrlInfo['pageid']));
                else
                        $returnUrl = ($curUrlInfo['act'] != 'mail' && $curUrlInfo['act'] != 'url' && $curUrlInfo['act'] != 'spec' && $insertSiteUrl ? $this->siteURL : '') . $curUrlInfo['info'];
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
                if ($getOL)
                        $row = $this->getRowLanguage($table, $row);
                if (!$row)
                        return FALSE;
                if ($getOL && $localizedAsUID && $row['_LOCALIZED_UID'])
                        $row['uid'] = $row['_LOCALIZED_UID'];
                // on déclare notre nouvelle ligne
                $this->localcObj->start($row, $table);

                // gestion de l'optionSplit
                if (!isset($this->optionSplitCounter['generateMarkersFromTableRow']['cObj'][$table]) && $this->caller->conf['cObj.'][$table . '.']) {
                        if ($nbRes > 1)
                                $splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($this->caller->conf['cObj.'][$table . '.'], $nbRes);
                        else
                                $splitConf = array(0 => $this->caller->conf['cObj.'][$table . '.']);
                        $this->optionSplitArray['generateMarkersFromTableRow']['cObj'][$table] = $splitConf;
                        $this->optionSplitCounter['generateMarkersFromTableRow']['cObj'][$table] = -1;
                }
                if (!isset($this->optionSplitCounter['generateMarkersFromTableRow']['stdWrap'][$table]) && $this->caller->conf[$table . '.']) {
                        if ($nbRes > 1)
                                $splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($this->caller->conf[$table . '.'], $nbRes);
                        else
                                $splitConf = array(0 => $this->caller->conf[$table . '.']);
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
                        if ($osc['cObj'][$table] >= count($osa['cObj'][$table]))
                                $osc['cObj'][$table] = 0;
                }
                if ($osa['stdWrap'][$table]) {
                        $osc['stdWrap'][$table] ++;
                        if ($osc['stdWrap'][$table] >= count($osa['stdWrap'][$table]))
                                $osc['stdWrap'][$table] = 0;
                }

                $markerArray = array();
                //pour chaque champs on génère un marker
                foreach ($row as $field => $value) {
                        $this->localcObj->setCurrentVal($value);
                        if ($generateLabel) // génération du marker de lang
                                $markerArray[$this->prefix['ll'] . ($tableInMarker ? $table . '_' : '') . $field] = $this->caller->pi_getLL($table . '.' . $field);
                        if ($osa['cObj'][$table][$osc['cObj'][$table]][$field]) // génération du cObj si necessaire
                                $value = $this->localcObj->cObjGetSingle($osa['cObj'][$table][$osc['cObj'][$table]][$field], $osa['cObj'][$table][$osc['cObj'][$table]][$field . '.']);
                        // traitement de la valeur par stdWrap ou envoie de la valeur telle quelle
                        $markerArray[$this->prefix['field'] . ($tableInMarker ? $table . '_' : '') . $field] = isset($osa['stdWrap'][$table][$osc['stdWrap'][$table]][$field . '.']) ? $this->localcObj->stdWrap($value, $osa['stdWrap'][$table][$osc['stdWrap'][$table]][$field . '.']) : $value;
                }

                // getting localcObj
                if (isset($this->caller->conf['localcObj.']) && count($this->caller->conf['localcObj.']))
                        foreach ($this->caller->conf['localcObj.'] as $key => $name)
                                if (!strpos($key, '.'))
                                        $markerArray[$this->prefix['local'] . $key] = $this->localcObj->cObjGetSingle($name, $this->caller->conf['localcObj.'][$key . '.']);

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
        protected function getRowLanguage($table, $row) {
                if ($table == 'pages')
                        $row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
                elseif ($this->contentOL != '')
                        $row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($table, $row, $GLOBALS['TSFE']->sys_language_content, $this->contentOL);
                return $row;
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
                $dsb = ExtensionManagementUtility::siteRelPath('cmd_api') . 'res/dsb';
                $formName = $this->caller->prefixId;
                $prefixID = $formName . '[';
                $cmd_api = <<<EOS
// ***************
// Used to connect the db/file browser with this document and the formfields on it!
// ***************
var browserWin="";

function setFormValueOpenBrowser(mode,params) {	//
	var url = "browser.php?mode="+mode+"&bparams="+params;
	browserWin = window.open(url,"Typo3WinBrowser","height=350,width="+(mode=="db"?650:600)+",status=0,menubar=0,resizable=1,scrollbars=1");
	browserWin.focus();
}

function setFormValueFromBrowseWin(fName,value,label,exclusiveValues)	{	//
	var formObj = setFormValue_getFObj(fName)
	if (formObj && value!="--div--")	{
		fObj = formObj[fName+"_list"];
		var len = fObj.length;
			// Clear elements if exclusive values are found
		if (exclusiveValues)	{
			var m = new RegExp("(^|,)"+value+"($|,)");
			if (exclusiveValues.match(m))	{
					// the new value is exclusive
				for (a=len-1;a>=0;a--)	fObj[a] = null;
				len = 0;
			} else if (len == 1)	{
				m = new RegExp("(^|,)"+fObj.options[0].value+"($|,)");
				if (exclusiveValues.match(m))	{
						// the old value is exclusive
					fObj[0] = null;
					len = 0;
				}
			}
		}
			// Inserting element
		var setOK = 1;
		if (!formObj[fName+"_mul"] || formObj[fName+"_mul"].value==0)	{
			for (a=0;a<len;a++)	{
				if (fObj.options[a].value==value)	{
					setOK = 0;
				}
			}
		}
		if (setOK)	{
			fObj.length++;
			fObj.options[len].value = value;
			fObj.options[len].text = unescape(label);

				// Traversing list and set the hidden-field
			//setHiddenFromList(fObj,formObj[fName]);
			setHiddenFromList(fObj,formObj['$prefixID'+fName+']']);
		}
	}
}

function setHiddenFromList(fObjSel,fObjHid)	{	//
	l=fObjSel.length;
	fObjHid.value="";
	for (a=0;a<l;a++)	{
		fObjHid.value+=fObjSel.options[a].value+",";
	}
}

function setFormValueManipulate(fName,type)	{	//
	var formObj = setFormValue_getFObj(fName)
	if (formObj)	{
		var localArray_V = new Array();
		var localArray_L = new Array();
		var localArray_S = new Array();
		var fObjSel = formObj[fName+"_list"];
		var l=fObjSel.length;
		var c=0;
		if (type=="Remove" || type=="Top" || type=="Bottom")	{
			if (type=="Top")	{
				for (a=0;a<l;a++)	{
					if (fObjSel.options[a].selected==1)	{
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=1;
						c++;
					}
				}
			}
			for (a=0;a<l;a++)	{
				if (fObjSel.options[a].selected!=1)	{
					localArray_V[c]=fObjSel.options[a].value;
					localArray_L[c]=fObjSel.options[a].text;
					localArray_S[c]=0;
					c++;
				}
			}
			if (type=="Bottom")	{
				for (a=0;a<l;a++)	{
					if (fObjSel.options[a].selected==1)	{
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=1;
						c++;
					}
				}
			}
		}
		if (type=="Down")	{
			var tC = 0;
			var tA = new Array();

			for (a=0;a<l;a++)	{
				if (fObjSel.options[a].selected!=1)	{
						// Add non-selected element:
					localArray_V[c]=fObjSel.options[a].value;
					localArray_L[c]=fObjSel.options[a].text;
					localArray_S[c]=0;
					c++;

						// Transfer any accumulated and reset:
					if (tA.length > 0)	{
						for (aa=0;aa<tA.length;aa++)	{
							localArray_V[c]=fObjSel.options[tA[aa]].value;
							localArray_L[c]=fObjSel.options[tA[aa]].text;
							localArray_S[c]=1;
							c++;
						}

						var tC = 0;
						var tA = new Array();
					}
				} else {
					tA[tC] = a;
					tC++;
				}
			}
				// Transfer any remaining:
			if (tA.length > 0)	{
				for (aa=0;aa<tA.length;aa++)	{
					localArray_V[c]=fObjSel.options[tA[aa]].value;
					localArray_L[c]=fObjSel.options[tA[aa]].text;
					localArray_S[c]=1;
					c++;
				}
			}
		}
		if (type=="Up")	{
			var tC = 0;
			var tA = new Array();
			var c = l-1;

			for (a=l-1;a>=0;a--)	{
				if (fObjSel.options[a].selected!=1)	{
						// Add non-selected element:
					localArray_V[c]=fObjSel.options[a].value;
					localArray_L[c]=fObjSel.options[a].text;
					localArray_S[c]=0;
					c--;

						// Transfer any accumulated and reset:
					if (tA.length > 0)	{
						for (aa=0;aa<tA.length;aa++)	{
							localArray_V[c]=fObjSel.options[tA[aa]].value;
							localArray_L[c]=fObjSel.options[tA[aa]].text;
							localArray_S[c]=1;
							c--;
						}

						var tC = 0;
						var tA = new Array();
					}
				} else {
					tA[tC] = a;
					tC++;
				}
			}
				// Transfer any remaining:
			if (tA.length > 0)	{
				for (aa=0;aa<tA.length;aa++)	{
					localArray_V[c]=fObjSel.options[tA[aa]].value;
					localArray_L[c]=fObjSel.options[tA[aa]].text;
					localArray_S[c]=1;
					c--;
				}
			}
			c=l;	// Restore length value in "c"
		}

			// Transfer items in temporary storage to list object:
		fObjSel.length = c;
		for (a=0;a<c;a++)	{
			fObjSel.options[a].value = localArray_V[a];
			fObjSel.options[a].text = localArray_L[a];
			fObjSel.options[a].selected = localArray_S[a];
		}
		//setHiddenFromList(fObjSel,formObj[fName]);
		setHiddenFromList(fObjSel,formObj['$prefixID'+fName+']']);
	}
}

function setFormValue_getFObj(fName)	{	//
	var formObj = document.$formName;
	if (formObj)	{
		//if (formObj[fName] && formObj[fName+"_list"] && formObj[fName+"_list"].type=="select-multiple")	{
		if (formObj['$prefixID'+fName+']'] && formObj[fName+"_list"] && formObj[fName+"_list"].type=="select-multiple")	{
			return formObj;
		} else {
			alert("Formfields missing:\\n fName: "+formObj['$prefixID'+fName+']']+"\\n fName_list:"+formObj[fName+"_list"]+"\\n type:"+formObj[fName+"_list"].type+"\\n fName:"+fName);
		}
	}
	return "";
}
// END: dbFileCon parts.		
EOS;
                // $GLOBALS['TSFE']->additionalHeaderData['cmd_api'].= '</script>';
                $pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
                $pageRenderer->addJsInlineCode('cmd_api-dsb', $cmd_api);
                $selectedOptions = $selectedOptionsList = '';
                if (is_array($row)) {
                        if ($MM && $row['uid']) { //il faut aller chercher les MM déjà sélectionné
                                $mm = $this->localcObj->exec_mm_query_uidList($Ftable . '.*', $row['uid'], $table . '_' . $field . '_mm', $Ftable);
                                if ($GLOBALS['TYPO3_DB']->sql_num_rows($mm) > 0)
                                        while ($mm_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mm)) {
                                                if ($getOL)
                                                        $mm_row = $this->getRowLanguage($Ftable, $mm_row);
                                                $selectedOptions.= '<option value="' . $mm_row['uid'] . '">' . $mm_row[$label] . '</option>' . "\n";
                                                $selectedOptionsList.= $selectedOptionsList == '' ? $mm_row['uid'] : ',' . $mm_row['uid'];
                                        }
                        } elseif ($row[$field]) {
                                $ft = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . ' AND uid IN (' . $row[$field] . ')');
                                if ($GLOBALS['TYPO3_DB']->sql_num_rows($ft) > 0)
                                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ft)) {
                                                if ($getOL)
                                                        $row = $this->getRowLanguage($Ftable, $row);
                                                $selectedOptions.= '<option value="' . $row['uid'] . '">' . $row[$label] . '</option>' . "\n";
                                                $selectedOptionsList.= $selectedOptionsList == '' ? $row['uid'] : ',' . $row['uid'];
                                        }
                        }
                }
                //ensuite on va chercher les mm possibles
                if ($pid != 0)
                        $where = ' and pid=' . $pid;
                else
                        $where = '';
                // appel d'une userFunc si défini
                if ($this->caller->conf['cmd_api.']['drawDoubleSelectBox_userFunc.'][$table . '.'][$field]) {
                        $funcConf = array();
                        $funcConf['table'] = $table;
                        $funcConf['field'] = $field;
                        $funcConf['Ftable'] = $Ftable;
                        $funcConf['label'] = $label;
                        $funcConf['MM'] = $MM;
                        $funcConf['pid'] = $pid;
                        $funcConf['row'] = $row;
                        $funcConf['getOL'] = $getOL;
                        $funcConf['where'] = $where;
                        $where = $this->userFunc($this->caller->conf['cmd_api.']['drawDoubleSelectBox_userFunc.'][$table . '.'][$field], $funcConf);
                }
                //exécution de la requette et poursuite du résultat
                $req = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . $where . $ftWhere);
                $availableOptions = '<option value="" />';
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($req) > 0)
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($req)) {
                                if ($getOL)
                                        $row = $this->getRowLanguage($Ftable, $row);
                                $availableOptions.= '<option value="' . $row['uid'] . '">' . $row[$label] . '</option>' . "\n";
                        }
                //enfin on prépare l'affichage
                $field_list = $field . '_list';
                $field_sel = $field . '_sel';
                if ($ajax != '')
                        $ajax_sel = ' ###AJAX_' . strtoupper($field) . '_SEL###';
                else
                        $ajax_sel = '';
                $return = <<<LINE
<table>
	<tr>
		<td>
			<select size="5" multiple="multiple" name="$field_list" style="width:200px;"$ajax_sel><br/>
				$selectedOptions
			</select>
		</td>
		<td>
			<a href="#" onclick="setFormValueManipulate('$field','Top'); return false;">
				<img src="$dsb/group_totop.gif" width="14" height="14" border="0" alt="Déplacer vers le haut les éléments sélectionnés" title="Déplacer vers le haut les éléments sélectionnés" />
			</a>
			<br />
			<a href="#" onclick="setFormValueManipulate('$field','Up'); return false;">
				<img src="$dsb/up.gif" width="14" height="14" border="0"  alt="Move selected items upwards" title="Move selected items upwards" />
			</a>
			<br />
			<a href="#" onclick="setFormValueManipulate('$field','Down'); return false;">
				<img src="$dsb/down.gif" width="14" height="14" border="0"  alt="Move selected items downwards" title="Move selected items downwards" />
			</a>
			<br />
			<a href="#" onclick="setFormValueManipulate('$field','Bottom'); return false;">
				<img src="$dsb/group_tobottom.gif" width="14" height="14" border="0"  alt="Move selected items to bottom" title="Move selected items to bottom" />
			</a>
			<br />
			<a href="#" onclick="setFormValueManipulate('$field','Remove'); return false;">
				<img src="$dsb/group_clear.png" width="18" height="20" border="0"  alt="Supprimer les éléments sélectionnés" title="Supprimer les éléments sélectionnés" />
			</a>
		</td>
		<td>
			<select name="$field_sel" size="5" onchange="if (this.options[this.selectedIndex].value!='') setFormValueFromBrowseWin('$field',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,'-1,-2'); " style="width:200px;"$ajax>
				$availableOptions
			</select>
			<input type="hidden" name="###PREFIXID###[$field]" value="$selectedOptionsList"/>
		</td>
	</tr>
</table>
LINE;

                //on renvoie le code HTML
                return $return;
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
                // on va chercher les mm possibles
                if ($pid != 0)
                        $where = ' and pid=' . $pid;
                else
                        $where = '';
                // appel d'une userFunc si défini
                if ($this->caller->conf['cmd_api.']['drawSimpleSelectBox_userFunc.'][$table . '.'][$field]) {
                        $funcConf = array();
                        $funcConf['table'] = $table;
                        $funcConf['field'] = $field;
                        $funcConf['Ftable'] = $Ftable;
                        $funcConf['label'] = $label;
                        $funcConf['pid'] = $pid;
                        $funcConf['getOL'] = $getOL;
                        $funcConf['where'] = $where;
                        $where = $this->userFunc($this->caller->conf['cmd_api.']['drawSimpleSelectBox_userFunc.'][$table . '.'][$field], $funcConf);
                }
                //exécution de la requette et poursuite du résultat
                $req = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . $where . $ftWhere);
                $availableOptions = '';
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($req) > 0)
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($req)) {
                                if ($getOL)
                                        $row = $this->getRowLanguage($Ftable, $row);
                                $value = '###' . strtoupper($this->prefix['field'] . ($tableInMarker ? $table . '_' : '') . $field) . '_' . $row['uid'] . '###';
                                $availableOptions.= '<option value="' . $row['uid'] . '"' . $value . '>' . $row[$label] . '</option>' . "\n";
                        }
                // enfin on prépare l'affichage
                $return = '<select name="###PREFIXID###[' . $field . ']" id="' . $table . '_' . $field . '"' . $ajax . '>' . ($pushEmpty ? '<option value="" />' : '');
                $return.= $availableOptions;
                $return.= '</select>';

                //on renvoie le code HTML
                return $return;
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
                if ($this->RTEObj->isAvailable()) {
                        $this->RTEcounter++;
                        $this->table = $table;
                        $this->field = $field;
                        $this->formName = $this->caller->prefixId;
                        $this->PA['itemFormElName'] = '###PREFIXID###[' . $field . ']';
                        $this->PA['itemFormElValue'] = $value;
                        $this->thePidValue = $GLOBALS['TSFE']->id;
                        $this->thisConfig = $this->pageTSConfig['RTE.']['config.'][$table . '.'][$field . '.'] ? $this->pageTSConfig['RTE.']['config.'][$table . '.'][$field . '.'] : '';
                        $RTEItem = $this->RTEObj->drawRTE($this, $table, $field, array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
                        $markerArray['###ADDITIONALJS_PRE###'] = $this->additionalJS_initial . '
				<script type="text/javascript">' . implode(chr(10), $this->additionalJS_pre) . '
				</script>';
                        $markerArray['###ADDITIONALJS_POST###'] = '
				<script type="text/javascript">' . implode(chr(10), $this->additionalJS_post) . '
				</script>';
                        $markerArray['###ADDITIONALJS_SUBMIT###'] = implode(';', $this->additionalJS_submit);
                        $markerArray['###' . strtoupper($this->prefix['input'] . ($tableInMarker ? $table . '_' : '') . $field) . '###'] = $RTEItem;
                }
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
                if ($this->RTEObj->isAvailable()) {
                        $this->thisConfig = $this->pageTSConfig['RTE.']['config.'][$table . '.'][$field . '.'] ? $this->pageTSConfig['RTE.']['config.'][$table . '.'][$field . '.'] : $this->pageTSConfig['RTE.']['default.']['FE.'];
                        $row[$field] = $this->RTEObj->transformContent('db', $row[$field], $table, $field, $row, $this->specConf, $this->thisConfig, '', $this->thePidValue);
                }
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
                $columns = &$GLOBALS['TCA'][$table]['columns'];
                $tableM = ($tableInMarker ? strtoupper($table) . '_' : '');
                $inputPrefix = strtoupper($this->prefix['input']) . $tableM;
                $valuePrefix = strtoupper($this->prefix['field']) . $tableM;

                $markerArray = array();
                //on appel le hook de debut de traitement
                $param = array('table' => $table, 'row' => &$row, 'markerArray' => &$markerArray);
                $this->getHook('before_generateInputMarkersFromTable', $param);
                // on parcours les colonnes de la TCA
                foreach ($columns as $field => &$config) {
                        $id = $table . '_' . $field;
                        $ajax = $config['cmd_lib']['ajax'] ? ' ###AJAX_' . strtoupper($field) . '###' : '';
                        $html = '';
                        $readonly = FALSE;
                        switch ($config['config']['type']) {
                                case 'none': // just displayable
                                        $readonly = TRUE;
                                case 'input':
                                        $type = 'text';
                                        $cal = '';
                                        if ($config['config']['eval']) {
                                                $evals = GeneralUtility::trimExplode(',', $columns[$field]['config']['eval']);
                                                foreach ($evals as $eval) {
                                                        if ($eval == 'password')
                                                                $type = 'password';
                                                        if ($this->dateFormat['active'] && ExtensionManagementUtility::isLoaded('rlmp_dateselectlib'))
                                                                if ($eval == 'date' || $eval == 'datetime') {
                                                                        $cal = '<input type="reset" class="' . $field . '-calendar" value=" ' . $this->dateFormat['inputFieldLabel'] . ' " onclick="return tx_rlmpdateselectlib_showCalendar(' . "'" . $id . "'" . ', ' . "'" . ($this->dateFormat[$eval] ? $this->dateFormat[$eval] : '%y-%m-%d') . "'" . ');">';
                                                                        if (isset($row[$field])) {
                                                                                if (is_numeric($row[$field]))
                                                                                        $row[$field] = $row[$field] != 0 ? strftime($this->dateFormat[$eval], $row[$field]) : '';
                                                                                else
                                                                                        $row[$field] = $row[$field];
                                                                        }
                                                                        if ($config['cmd_lib']['dateCheckEmpty'] && $config['cmd_lib']['dateCheckEmpty'] == 1) { // ajout de la case à cocher vidant le champ de date
                                                                                if (!$GLOBALS['TSFE']->additionalHeaderData[$this->caller->prefixId . '_dateCheckEmpty'])
                                                                                        $GLOBALS['TSFE']->additionalHeaderData[$this->caller->prefixId . '_dateCheckEmpty'] = '<script type="text/javascript">function toggleCheckEmpty(id,datebutton,check) {formInput=document.getElementById(id); formDate=document.getElementById(datebutton); if(check.checked==false) {formInput.disabled=false; formDate.disabled=false;} else {formInput.value=""; formInput.disabled=true; formDate.disabled=true;}}</script>';
                                                                                $cal.= '<input type="checkbox" name="' . $field . '_empty" id="check_' . $field . '_empty" onclick="toggleCheckEmpty(\'' . $id . '\', \'datebutton_' . $field . '\', this);" /> <label for="check_' . $field . '_empty">###LABEL_CHECKEMPTY_' . strtoupper($field) . '###</label>';
                                                                        }
                                                                }
                                                }
                                        }
                                        $html = '<input type="' . $type . '" name="###PREFIXID###[' . $field . ']" id="' . $id . '" value="###' . $valuePrefix . strtoupper($field) . '###" ' . ($readonly ? ' readonly' : '') . $ajax . ' />' . $cal;
                                        break;
                                case 'text':
                                        if (isset($config['config']['wizards']['RTE']) && ExtensionManagementUtility::isLoaded('rtehtmlarea')) {
                                                if (isset($row[$field]))
                                                        $this->drawRTE($markerArray, $table, $field, $row[$field]);
                                                else
                                                        $this->drawRTE($markerArray, $table, $field);
                                        } else {
                                                $cols = $config['config']['cols'] ? $config['config']['cols'] : 30;
                                                $rows = $config['config']['rows'] ? $config['config']['rows'] : 5;
                                                $html = '<textarea name="###PREFIXID###[' . $field . ']" id="' . $id . '" cols="' . $cols . '" rows="' . $rows . '"' . $ajax . '>###' . $valuePrefix . strtoupper($field) . '###</textarea>';
                                        }
                                        break;
                                case 'check':
                                        if ($config['config']['items']) {
                                                // TODO
                                        } else
                                                $html = '<input type="checkbox" name="###PREFIXID###[' . $field . ']" id="' . $id . '" value="1"###' . $valuePrefix . strtoupper($field) . '###' . $ajax . ' />';
                                        break;
                                case 'radio':
                                        // TODO
                                        break;
                                case 'select':
                                        $size = $config['config']['size'];
                                        $maxitems = $config['config']['maxitems'];
                                        $ftWhere = $config['config']['foreign_table_where'] ? ' ' . $config['config']['foreign_table_where'] : '';
                                        if ($config['config']['foreign_table']) {
                                                $ft = $config['config']['foreign_table'];
                                                $fl = $GLOBALS['TCA'][$ft]['ctrl']['label'];
                                                if ($maxitems > 1)
                                                        $html = $this->drawDoubleSelectBox($table, $field, $ft, $fl, ($config['config']['MM'] ? TRUE : FALSE), $ftWhere, $ajax, $row['pid'], $row, TRUE);
                                                else
                                                        $html = $this->drawSimpleSelectBox($table, $field, $ft, $fl, (is_array($config['config']['items']) ? TRUE : FALSE), $ftWhere, $ajax, $row['pid'], TRUE);
                                        } else { // liste d'items
                                                $html = '<select name="###PREFIXID###[' . $field . ']" id="' . $id . '"' . $ajax . '>';
                                                foreach ($config['config']['items'] as $valeur)
                                                        $html.= '<option value="' . $valeur[1] . '"###' . $valuePrefix . strtoupper($field) . '_' . strtoupper($valeur[1]) . '###>' . $GLOBALS['TSFE']->sL($valeur[0]) . '</option>';
                                                $html.= '</select>';
                                        }
                                        // Cas particulier, si c'est une liste d'item, on les marques actif
                                        if (isset($row[$field])) {
                                                foreach (GeneralUtility::trimExplode(',', $row[$field]) as $value)
                                                        $row[$field . '_' . $value] = ' selected="selected"';
                                                unset($row[$field]);
                                        }
                                        break;
                                case 'group':
                                        $size = $config['config']['size'];
                                        $max_size = $config['config']['max_size'];
                                        $maxitems = $config['config']['maxitems'];
                                        $allowed = $config['config']['allowed'];
                                        $disallowed = $config['config']['disallowed'];
                                        switch ($config['config']['internal_type']) {
                                                case 'file':
                                                        $uploadfolder = $config['config']['uploadfolder'];
                                                        $auth = array();
                                                        if ($max_size)
                                                                $auth[] = '###LABEL_FILE_SIZE### ' . $max_size;
                                                        if ($allowed != '')
                                                                $auth[] = '###LABEL_ALLOWED### ' . $allowed;
                                                        elseif ($disallowed != '')
                                                                $auth[] = '###LABEL_DISALLOWED### ' . $disallowed;
                                                        $html = '<input type="text" name="###PREFIXID###[' . $field . ']" readonly value="###' . $valuePrefix . strtoupper($field) . '###" />';
                                                        $html.= '<span class="param-' . $field . '">' . implode(', ', $auth) . '</span>';
                                                        $html.= '<br />';
                                                        // TODO - apply delete input foreach uploaded file
                                                        if ($config['config']['MM']) {
                                                                // TODO
                                                        } else {
                                                                // TODO - handle multiple upload
                                                                if ($row[$field] != '')
                                                                        $disable = ' disabled';
                                                                else
                                                                        $disable = '';
                                                                $html.= '<input type="file" name="###PREFIXID###[' . $field . '_up]"' . $disable . $ajax . ' />';
                                                        }
                                                        break;
                                                case 'db':
                                                        // Not handled in FRONT
                                                        break;
                                                case 'folder':
                                                        // Not handled in FRONT
                                                        break;
                                        }
                                        break;
                                case 'user':
                                        $PA = array('table' => $table, 'field' => $field);
                                        $html = $this->userFunc($config['config']['userFunc'], $PA);
                                        break;
                                case 'flex':
                                        // TODO - render a flexform
                                        break;
                                case 'inline':
                                        // TODO - call construction of other table - AJAXable
                                        break;
                        }
                        //on appel le hook de milieu de traitement
                        $param2 = array_merge($param, array('html' => $html));
                        if ($this->getHook('middle_generateInputMarkersFromTable', $param2))
                                $html = $param2['html'];
                        if ($html != '') {
                                // $valueMarker
                                $markerArray['###' . $inputPrefix . strtoupper($field) . '###'] = $html;
                        }
                }
                $markerArray['###CMDAPI_HIDDEN###'] = '<input type="hidden" name="###PREFIXID###[uid]" value="###' . $valuePrefix . 'UID###" />';
                $markerArray['###CMDAPI_HIDDEN###'].= '<input type="hidden" name="###PREFIXID###[pid]" value="' . $row['pid'] . '" />';

                //on appel le hook de fin de traitement
                $this->getHook('after_generateInputMarkersFromTable', $param);

                // On génère les marker des valeurs de la ligne courante
                if (count($row) > 0) {
                        $valueMarker = $this->generateMarkersFromTableRow($table, $row, $tableInMarker, FALSE, FALSE);
                        foreach ($markerArray as $marker => $value) // ugly but haven't time to boost that
                                $markerArray[$marker] = $this->localcObj->substituteMarkerArray($markerArray[$marker], $valueMarker, $this->wrap, TRUE);
                }

                // on renvoie le tableau de résultat
                return $markerArray;
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
                $columns = &$GLOBALS['TCA'][$table]['columns'];

                $markers = array();
                foreach ($columns as $field => $config)
                        if ((isset($config['config']['eval']) && in_array('required', GeneralUtility::trimExplode(',', $config['config']['eval']))) || ($config['config']['minitems'] && $config['config']['minitems'] > 0))
                                $markers[$this->prefix['required'] . ($tableInMarker ? $table . '_' : '') . $field] = $label;

                return $markers;
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
                $columns = &$GLOBALS['TCA'][$table]['columns'];

                $error = $evaluatedRow = array();
                foreach ($row as $field => $value) {
                        //on appel le hook de debut de traitement
                        $param = array('field' => $field, 'value' => &$value);
                        $this->getHook('before_TestFieldForTable', $param);
                        $evaluatedRow[$field] = $value;
                        if ($columns[$field])
                                switch ($columns[$field]['config']['type']) {
                                        case 'check': if ($evaluatedRow[$field] == '')
                                                        $evaluatedRow[$field] = 0;
                                                break;
                                        case 'text': if ($columns[$field]['config']['wizards']['RTE'])
                                                        $this->getRTE($table, $evaluatedRow, $field);
                                                $conditions = GeneralUtility::trimExplode(',', $columns[$field]['config']['eval']);
                                                if (in_array('required', $conditions) && $evaluatedRow[$field] == '')
                                                        $error[$field] = 'required';
                                                break;
                                        case 'input':
                                                $conditions = GeneralUtility::trimExplode(',', $columns[$field]['config']['eval']);
                                                $range = $columns[$field]['config']['range'];
                                                $is_in = $columns[$field]['config']['is_in'];
                                                $max = $columns[$field]['config']['max'];
                                                foreach ($conditions as $condition) {
                                                        switch ($condition) {
                                                                case 'required': if (!isset($row[$field]) || trim($evaluatedRow[$field]) == '')
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'required';
                                                                        break;
                                                                case 'trim': $evaluatedRow[$field] = trim($evaluatedRow[$field]);
                                                                        break;
                                                                case 'date': if ($evaluatedRow[$field] != '') {
                                                                                $date = strptime($evaluatedRow[$field], $this->dateFormat[$condition]);
                                                                                $evaluatedRow[$field] = mktime(0, 0, 0, 1 + $date['tm_mon'], $date['tm_mday'], 1900 + $date['tm_year']);
                                                                        }
                                                                        break;
                                                                case 'datetime': if ($evaluatedRow[$field] != '') {
                                                                                $date = strptime($evaluatedRow[$field], $this->dateFormat[$condition]);
                                                                                $evaluatedRow[$field] = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec'], 1 + $date['tm_mon'], $date['tm_mday'], 1900 + $date['tm_year']);
                                                                        }
                                                                        break;
                                                                case 'time': if ($evaluatedRow[$field] != '') {
                                                                                $date = strptime($evaluatedRow[$field], $this->dateFormat[$condition]);
                                                                                $evaluatedRow[$field] = mktime($date['tm_hour'], $date['tm_min']);
                                                                        }
                                                                        break;
                                                                case 'timesec': if ($evaluatedRow[$field] != '') {
                                                                                $date = strptime($evaluatedRow[$field], $this->dateFormat[$condition]);
                                                                                $evaluatedRow[$field] = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec']);
                                                                        }
                                                                        break;
                                                                case 'year': if ($evaluatedRow[$field] < 1970 || $evaluatedRow[$field] > 2038)
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'year';
                                                                        break;
                                                                case 'int': $evaluatedRow[$field] = intval($evaluatedRow[$field]);
                                                                        if (is_array($range) && ($range['lower'] > $evaluatedRow[$field] || $range['upper'] < $evaluatedRow[$field]))
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'range';
                                                                        break;
                                                                case 'upper': $evaluatedRow[$field] = strtoupper($evaluatedRow[$field]);
                                                                        break;
                                                                case 'lower': $evaluatedRow[$field] = strtolower($evaluatedRow[$field]);
                                                                        break;
                                                                case 'alpha':
                                                                        // TODO
                                                                        break;
                                                                case 'num':
                                                                        // TODO
                                                                        break;
                                                                case 'alphanum':
                                                                        // TODO
                                                                        break;
                                                                case 'alphanum_x':
                                                                        // TODO
                                                                        break;
                                                                case 'nospace': $evaluatedRow[$field] = str_replace(' ', '', $evaluatedRow[$field]);
                                                                        break;
                                                                case 'md5': $evaluatedRow[$field] = md5($evaluatedRow[$field]);
                                                                        break;
                                                                case 'is_in':
                                                                        // TODO
                                                                        break;
                                                                case 'double2': $evaluatedRow[$field] = str_replace(' ', '', $evaluatedRow[$field]);
                                                                        $evaluatedRow[$field] = str_replace(',', '.', $evaluatedRow[$field]);
                                                                        $evaluatedRow[$field] = number_format($evaluatedRow[$field], 2, '.', '');
                                                                        break;
                                                                case 'unique':
                                                                        if (is_array($GLOBALS['TSFE']->sys_page->getRecordsByField($table, $field, $evaluatedRow[$field], ' AND pid>=0')))
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'unique';
                                                                        break;
                                                                case 'uniqueInPid':
                                                                        if (is_array($GLOBALS['TSFE']->sys_page->getRecordsByField($table, $field, $evaluatedRow[$field], ' AND pid>=' . $row['pid'])))
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'uniqueInPid';
                                                                        break;
                                                                default:
                                                                        if (substr($condition, 2) == 'tx_') {
                                                                                // TODO
                                                                        }
                                                        }
                                                }
                                                // TODO
                                                break;
                                        case 'select':
                                                $minitems = $columns[$field]['config']['minitems'];
                                                $maxitems = $columns[$field]['config']['maxitems'];
                                                $multiple = $columns[$field]['config']['multiple'];
                                                $MM = $columns[$field]['config']['MM'] ? TRUE : FALSE;
                                                if ($value != '') {
                                                        $vals = GeneralUtility::trimExplode(',', $value);
                                                        if (count($vals) > $maxitems)
                                                                $error[$field] = 'max';
                                                        elseif ($MM)
                                                                $evaluatedRow[$field] = $vals;
                                                } elseif ($minitems > 0)
                                                        $error[$field] = 'required';
                                                break;
                                        case 'group':
                                                $minitems = $columns[$field]['config']['minitems'];
                                                $maxitems = $columns[$field]['config']['maxitems'];
                                                $multiple = $columns[$field]['config']['multiple'];
                                                switch ($columns[$field]['config']['internal_type']) {
                                                        case 'file':
                                                                $fileFunc = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
                                                                // on fait des test sur le fichier
                                                                if ($_FILES[$this->caller->prefixId]['name'][$field . '_up'] != '') { // si on a un upload réalisé et que le nom est autorisé
                                                                        $allowedExtArray = GeneralUtility::trimExplode(',', $columns[$field]['config']['allowed'], 1);
                                                                        if (count($allowedExtArray) == 0 || in_array('*', $allowedExtArray)) // si on autorise tout ou que rien n'est spécifié on regardera les extensions refusé
                                                                                $disallowedExtArray = GeneralUtility::trimExplode(',', $columns[$field]['config']['disallowed'], 1);
                                                                        // on récupère les infos du fichier
                                                                        $fi = pathinfo($_FILES[$this->caller->prefixId]['name'][$field . '_up']);
                                                                        $max_size = $columns[$field]['config']['max_size'];
                                                                        if (GeneralUtility::verifyFilenameAgainstDenyPattern($fi['name']) && ($max_size * 1024) >= $_FILES[$this->caller->prefixId]['size'][$field . '_up']) { // s'il a la bonne taille et un bon nom
                                                                                if ((isset($disallowedExtArray) && in_array(strtolower($fi['extension']), $disallowedExtArray)) ||
                                                                                        (isset($allowedExtArray) && count($allowedExtArray) > 0 && !in_array('*', $allowedExtArray) && !in_array(strtolower($fi['extension']), $allowedExtArray)))
                                                                                        $error[$field] = ($error[$field] == '' ? '' : ', ') . 'fileext'; // que l'extension est accepté
                                                                                else {
                                                                                        $uploadfolder = $columns[$field]['config']['uploadfolder'];
                                                                                        $tmpFilename = ($GLOBALS['TSFE']->loginUser) ? ($GLOBALS['TSFE']->fe_user->user['username'] . '_') : '';
                                                                                        $tmpFilename.= basename($_FILES[$this->caller->prefixId]['name'][$field . '_up'], '.' . $fi['extension']);
                                                                                        $tmpFilename.= '_' . GeneralUtility::shortmd5(uniqid($_FILES[$this->caller->prefixId]['name'][$field . '_up']));
                                                                                        $tmpFilename.= '.' . $fi['extension'];
                                                                                        $destFile = $fileFunc->getUniqueName($fileFunc->cleanFileName($tmpFilename), PATH_site . $uploadfolder . '/');
                                                                                        GeneralUtility::upload_copy_move($_FILES[$this->caller->prefixId]['tmp_name'][$field . '_up'], $destFile);
                                                                                        $fi2 = pathinfo($destFile);
                                                                                        $evaluatedRow[$field] = $fi2['basename'];
                                                                                        $row[$field] = $fi2['basename'];
                                                                                }
                                                                        } else
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'fileup';
                                                                }
                                                                break;
                                                }
                                                // TODO
                                                break;
                                        case 'user':
                                                // TODO - nothing to do?
                                                break;
                                        case 'flex':
                                                // TODO
                                                break;
                                        case 'inline':
                                                $minitems = $columns[$field]['config']['minitems'];
                                                $maxitems = $columns[$field]['config']['maxitems'];
                                                // TODO
                                                break;
                                }
                        //on appel le hook de fin de traitement
                        $param = array('field' => $field, 'value' => &$evaluatedRow[$field]);
                        $this->getHook('after_TestFieldForTable', $param);
                }
                if (count($error) > 0)
                        return $error;
                else
                        return FALSE;
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
                if (!ExtensionManagementUtility::isLoaded('pagebrowse'))
                        return 'pagebrowse needed!';
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
                if ($templateFile != '')
                        $conf['templateFile'] = $templateFile;
                if ($extraQueryString != '')
                        $conf['extraQueryString'] = $extraQueryString;
                $this->localcObj->start(array(), '');
                return $this->localcObj->cObjGetSingle('USER', $conf);
        }

        /**
         * Fonction appelant le(s) hook(s)
         * 
         * @param	string		$hookName: Nom du hook à déclancher
         * @param	array		$hookConf: tableau de conf à passer aux hook
         * @return	TRUE if hooks found FALSE overwise
         */
        protected function getHook($hookName, &$hookConf = array()) {
                // new recommanded coding guideline
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cmd_api'][$hookName])) {
                        $hookConf['parentObj'] = &$this->caller;
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cmd_api'][$hookName] as $key => $classRef) {
                                $_procObj = GeneralUtility::getUserObj($classRef);
                                $_procObj->$hookName($hookConf, $this);
                        }
                        return TRUE;
                }
                // old method still available for compat. issu
                if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['cmd_api/class.tx_cmdapi_lib.php'][$hookName])) {
                        foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['cmd_api/class.tx_cmdapi_lib.php'][$hookName] as $funcRef)
                                $this->userFunc($funcRef, $hookConf);
                        return TRUE;
                }
                return FALSE;
        }

        /**
         * Fonction appelant une userfunc avec le tableau de paramettre passé à la fonction
         * 
         * @param	string		$funcName: Nom de la fonction
         * @param	array		$funcConf: tableau de conf à passer à la fonction
         * @return	le resultat de l'userFunc
         */
        protected function userFunc($funcName, &$funcConf) {
                $funcConf['parentObj'] = &$this->caller;
                return GeneralUtility::callUserFunction($funcName, $funcConf, $this);
        }

        /**
         * Fonction instanciant le moteur xajax et déclanchant l'appel des procédures
         * 
         * @param	array		$functions: tableau des fonctions à instancier dans le moteur ajax array('functionPHP', etc...)
         * @return	l'objet ajax
         */
        public function initXajax($functions = array()) {
		// instanciation du moteur ajax
                if (!ExtensionManagementUtility::isLoaded('xajax'))
                        return 'xajax needed!';
                elseif (!$this->xajax) {
                        require_once(ExtensionManagementUtility::extPath('xajax') . 'class.tx_xajax.php');
                        $this->xajax = TRUE;
                }
                $xajax = GeneralUtility::makeInstance('tx_xajax');
                $xajax->cleanBufferOn(); // clear any white space ;)
                $xajax->decodeUTF8InputOn();
                $xajax->setWrapperPrefix($this->caller->prefixId);
		// Register the names of the PHP functions you want to be able to call through xajax
		// $xajax->registerFunction(array('functionNameInJavascript', &$object, 'methodName'));
                if (count($functions) > 0)
                        foreach ($functions as $func)
                                $xajax->registerFunction(array($func, &$this->caller, $func));
		// basic test but enable to have more than 1 xajax plugin on a page
                if (in_array($_POST['xajax'], $functions))
                        $xajax->processRequests();
                $GLOBALS['TSFE']->additionalHeaderData[$this->caller->prefixId . '_xajax'] = $xajax->getJavascript(ExtensionManagementUtility::siteRelPath('xajax'));
                return $xajax;
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
                $authorized = array('onblur', 'onchange', 'onclick', 'ondblclick', 'onfocus', 'onselect', 'onsubmit'); // tableau des valeurs autorisé d'évènement
                if ($formName == '')
                        $formName = $this->caller->prefixId;
                if (!in_array(strtolower($method), $authorized))
                        $method = 'onclick';
                return $method . '="' . $this->caller->prefixId . $funcName . '(xajax.getFormValues(\'' . $formName . '\')' . $otherParams . '); return FALSE;"';
        }

        /**
         * Fonction instanciant et renvoyant la réponse ajax
         * attention: delai est en secondes
         *
         * @param	array		$response: tableau des reponses à préparer et à renvoyer au navigateur array(0 => array('type' => 'xxx', 'id' => 'xxx', 'method' => 'xxx', 'msg' => 'xxx'), etc...)
         * @return	le xml ajax à retourner au navigateur
         */
        public function setXajaxAssign($response = array()) {
                $objResponse = GeneralUtility::makeInstance('tx_xajax_response');
                foreach ($response as $assign) {
                        if (
                                ($assign['type'] == 'alert' && empty($assign['msg'])) ||
                                ($assign['type'] == 'script' && empty($assign['msg'])) ||
                                ($assign['type'] == 'clear' && empty($assign['id'])) ||
                                ($assign['type'] == 'prepend' && (empty($assign['id']) || empty($assign['msg']))) ||
                                ($assign['type'] == 'assign' && (empty($assign['id']) || empty($assign['msg']))) ||
                                ($assign['type'] == 'append' && (empty($assign['id']) || empty($assign['msg']))) ||
                                ($assign['type'] == 'replace' && (empty($assign['id']) || empty($assign['search']) || empty($assign['msg']))) ||
                                ($assign['type'] == 'remove' && empty($assign['id'])) ||
                                ($assign['type'] == 'redirect' && empty($assign['msg']))
                        )
                                continue;
                        switch ($assign['type']) {
                                case 'alertXML': $objResponse->alert($objResponse->getOutput());
                                        break;
                                case 'alert': $objResponse->alert($assign['msg']);
                                        break;
                                case 'script': $objResponse->script($assign['msg']);
                                        break;
                                case 'clear': $objResponse->clear($assign['id'], ($assign['method'] ? $assign['method'] : 'innerHTML'));
                                        break;
                                case 'prepend': $objResponse->prepend($assign['id'], ($assign['method'] ? $assign['method'] : 'innerHTML'), $assign['msg']);
                                        break;
                                case 'assign': $objResponse->assign($assign['id'], ($assign['method'] ? $assign['method'] : 'innerHTML'), $assign['msg']);
                                        break;
                                case 'append': $objResponse->append($assign['id'], ($assign['method'] ? $assign['method'] : 'innerHTML'), $assign['msg']);
                                        break;
                                case 'replace': $objResponse->replace($assign['id'], ($assign['method'] ? $assign['method'] : 'innerHTML'), $assign['search'], $assign['msg']);
                                        break;
                                case 'remove': $objResponse->remove($assign['id']);
                                        break;
                                case 'redirect': $objResponse->redirect($assign['msg'], ($assign['delai'] ? $assign['delai'] : 0));
                                        break;
                        }
                }
                return $objResponse->getXML(); //return the XML response
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
                switch ($pos) {
                        case 'none':
                                $format = '###FIELD### LIKE \'###VALUE###\'';
                                break;
                        case 'begin':
                                $format = '###FIELD### LIKE \'%###VALUE###\'';
                                break;
                        case 'both':
                                $format = '###FIELD### LIKE \'%###VALUE###%\'';
                                break;
                        case 'end':
                                $format = '###FIELD### LIKE \'###VALUE###%\'';
                                break;
                }

                $iter = array();
                if ($reverseSearch) {
                        if (!is_array($value))
                                $value = GeneralUtility::trimExplode(' ', $value, TRUE);
                        foreach ((array) $value as $val) {
                                $finalValue = $GLOBALS['TYPO3_DB']->quoteStr($val, $table);
                                if ($escapeString)
                                        $finalValue = $GLOBALS['TYPO3_DB']->escapeStrForLike($finalValue, $table);
                                $iter[] = $this->localcObj->substituteMarkerarray($format, array('field' => $table . '.' . $fields, 'value' => $finalValue), '###|###', TRUE);
                        }
                } else {
                        $finalValue = $GLOBALS['TYPO3_DB']->quoteStr($value, $table);
                        if ($escapeString)
                                $finalValue = $GLOBALS['TYPO3_DB']->escapeStrForLike($finalValue, $table);
                        foreach ((array) $fields as $field)
                                $iter[] = $this->localcObj->substituteMarkerarray($format, array('field' => $table . '.' . $field, 'value' => $finalValue), '###|###', TRUE);
                }
                $string = ' AND (' . implode(' OR ', $iter) . ')';

                return $string;
        }

        /**
         * Fonction sécurisant et formattant une chaine d'entier pour le SQL (comparaison avec IN)
         * 
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: list des entiers
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        public function cleanIntWhere($field, $value) {
                return ' AND ' . $field . ' IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($value) . ')';
        }

        /**
         * Fonction sécurisant et formattant une chaine d'entier pour le SQL (comparaison avec un FIND_IN_SET pour chaque valeur)
         *
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: list des entiers
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        public function cleanIntFind($field, $value) {
                $intList = $GLOBALS['TYPO3_DB']->cleanIntList($value);
                if ($intList == 0)
                        return '';
                else {
                        $values = GeneralUtility::intExplode(',', $intList);
                        $iter = array();
                        foreach ($values as $val)
                                $iter[] = 'FIND_IN_SET (' . $val . ', ' . $field . ')';
                        return ' AND (' . implode(' OR ', $iter) . ')';
                }
        }

}

?>