<?php

namespace CMD\CmdApi;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Christophe Monard <contact@cmonard.fr>
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
 * Librairie de fonctions specifique rendu de champs
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * methode d'appel:
 * 	$cmdFieldsRender = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CMD\\CmdApi\\FieldsRender', $this[[[, $prefixArray], $dateArray], $wrap]);
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   59: class Lib
 *  116:        public function __construct(&$caller, $prefixArray = array(), $dateArray = array(), wrap = '')
 *  162:        public function drawDoubleSelectBox($table, $field, $Ftable, $label, $MM = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $row = '', $getOL = TRUE)
 *  499:        public function drawSimpleSelectBox($table, $field, $Ftable, $label, $pushEmpty = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $getOL = TRUE)
 *  549:        public function drawRTE(&$markerArray, $table, $field, $value = '', $tableInMarker = FALSE) (need rtehtmlarea)
 *  579:        public function getRTE($table, $row, $field) (need rtehtmlarea)
 *  594:        public function generateInputMarkersFromTable($table, $pid = 0, $row = array(), $tableInMarker = FALSE)
 *  784:        public function getRequiredMarkerForTable($table, $label = 1, $tableInMarker = FALSE)
 *  805:        public function testFieldForTable($table, &$row, &$evaluatedRow)
 *
 * TOTAL FUNCTIONS: 8
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class FieldsRender {

        // Variable interne
        protected $caller = object;
        protected $contentOL = 1;
        public $localcObj;
        protected $wrap = '###|###'; // wrap pour les marqueurs
        protected $prefix = array(
            'input' => 'input_', // nom du champ sur lequel appliquer le form input
            'field' => 'field_', // valeur du form input
            'required' => 'required_', // proviens de la TCA de la table, indique les champs marqué required
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
                $dsb = ExtensionManagementUtility::siteRelPath('cmd_api') . 'Resources/Public/Icons/Dsb';
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
                                if ($GLOBALS['TYPO3_DB']->sql_num_rows($mm) > 0) {
                                        while ($mm_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mm)) {
                                                if ($getOL) {
                                                        $mm_row = Api::getRowLanguage($Ftable, $mm_row, $this->contentOL);
                                                }
                                                $selectedOptions.= '<option value="' . $mm_row['uid'] . '">' . $mm_row[$label] . '</option>' . "\n";
                                                $selectedOptionsList.= $selectedOptionsList == '' ? $mm_row['uid'] : ',' . $mm_row['uid'];
                                        }
                                }
                        } elseif ($row[$field]) {
                                $ft = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . ' AND uid IN (' . $row[$field] . ')');
                                if ($GLOBALS['TYPO3_DB']->sql_num_rows($ft) > 0) {
                                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ft)) {
                                                if ($getOL) {
                                                        $row = Api::getRowLanguage($Ftable, $row, $this->contentOL);
                                                }
                                                $selectedOptions.= '<option value="' . $row['uid'] . '">' . $row[$label] . '</option>' . "\n";
                                                $selectedOptionsList.= $selectedOptionsList == '' ? $row['uid'] : ',' . $row['uid'];
                                        }
                                }
                        }
                }
                //ensuite on va chercher les mm possibles
                if ($pid != 0) {
                        $where = ' and pid=' . $pid;
                } else {
                        $where = '';
                }
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
                        $where = Api::userFunc($this->caller->conf['cmd_api.']['drawDoubleSelectBox_userFunc.'][$table . '.'][$field], $funcConf, $this->caller);
                }
                //exécution de la requette et poursuite du résultat
                $req = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . $where . $ftWhere);
                $availableOptions = '<option value="" />';
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($req) > 0) {
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($req)) {
                                if ($getOL) {
                                        $row = Api::getRowLanguage($Ftable, $row, $this->contentOL);
                                }
                                $availableOptions.= '<option value="' . $row['uid'] . '">' . $row[$label] . '</option>' . "\n";
                        }
                }
                //enfin on prépare l'affichage
                $field_list = $field . '_list';
                $field_sel = $field . '_sel';
                if ($ajax != '') {
                        $ajax_sel = ' ###AJAX_' . strtoupper($field) . '_SEL###';
                } else {
                        $ajax_sel = '';
                }
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
         * @param       bool            $tableInMarker: Add table name in marker name
         * @param	bool		$pushEmpty: Doit-on mettre une option vide en début de selectbox
         * @param	string		$ajax: chaine du marker a ajouter pour gérer de l'ajax (ou eventuellement du js)
         * @param	string		$pid: Pid dans laquelle récupérer les enregistrements
         * @param	bool		$getOL: Doit-on récupérer la langue
         * @return	le SelectorBox construit
         */
        public function drawSimpleSelectBox($table, $field, $Ftable, $label, $tableInMarker = FALSE, $pushEmpty = FALSE, $ftWhere = '', $ajax = '', $pid = 0, $getOL = TRUE) {
                // on va chercher les mm possibles
                if ($pid != 0) {
                        $where = ' and pid=' . $pid;
                } else {
                        $where = '';
                }
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
                        $where = Api::userFunc($this->caller->conf['cmd_api.']['drawSimpleSelectBox_userFunc.'][$table . '.'][$field], $funcConf, $this->caller);
                }
                //exécution de la requette et poursuite du résultat
                $req = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $Ftable, '1' . $this->localcObj->enableFields($Ftable) . $where . $ftWhere);
                $availableOptions = '';
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($req) > 0) {
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($req)) {
                                if ($getOL) {
                                        $row = Api::getRowLanguage($Ftable, $row, $this->contentOL);
                                }
                                $value = '###' . strtoupper($this->prefix['field'] . ($tableInMarker ? $table . '_' : '') . $field) . '_' . $row['uid'] . '###';
                                $availableOptions.= '<option value="' . $row['uid'] . '"' . $value . '>' . $row[$label] . '</option>' . "\n";
                        }
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
                Api::getHook('before_generateInputMarkersFromTable', $param, $this->caller);
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
                                                        if ($eval == 'password') {
                                                                $type = 'password';
                                                        }
                                                        if ($this->dateFormat['active'] && ExtensionManagementUtility::isLoaded('rlmp_dateselectlib')) {
                                                                if ($eval == 'date' || $eval == 'datetime') {
                                                                        $cal = '<input type="reset" class="' . $field . '-calendar" value=" ' . $this->dateFormat['inputFieldLabel'] . ' " onclick="return tx_rlmpdateselectlib_showCalendar(' . "'" . $id . "'" . ', ' . "'" . ($this->dateFormat[$eval] ? $this->dateFormat[$eval] : '%y-%m-%d') . "'" . ');">';
                                                                        if (isset($row[$field])) {
                                                                                if (is_numeric($row[$field])) {
                                                                                        $row[$field] = $row[$field] != 0 ? strftime($this->dateFormat[$eval], $row[$field]) : '';
                                                                                } else {
                                                                                        $row[$field] = $row[$field];
                                                                                }
                                                                        }
                                                                        if ($config['cmd_lib']['dateCheckEmpty'] && $config['cmd_lib']['dateCheckEmpty'] == 1) { // ajout de la case à cocher vidant le champ de date
                                                                                if (!$GLOBALS['TSFE']->additionalHeaderData[$this->caller->prefixId . '_dateCheckEmpty']) {
                                                                                        $GLOBALS['TSFE']->additionalHeaderData[$this->caller->prefixId . '_dateCheckEmpty'] = '<script type="text/javascript">function toggleCheckEmpty(id,datebutton,check) {formInput=document.getElementById(id); formDate=document.getElementById(datebutton); if(check.checked==false) {formInput.disabled=false; formDate.disabled=false;} else {formInput.value=""; formInput.disabled=true; formDate.disabled=true;}}</script>';
                                                                                }
                                                                                $cal.= '<input type="checkbox" name="' . $field . '_empty" id="check_' . $field . '_empty" onclick="toggleCheckEmpty(\'' . $id . '\', \'datebutton_' . $field . '\', this);" /> <label for="check_' . $field . '_empty">###LABEL_CHECKEMPTY_' . strtoupper($field) . '###</label>';
                                                                        }
                                                                }
                                                        }
                                                }
                                        }
                                        $html = '<input type="' . $type . '" name="###PREFIXID###[' . $field . ']" id="' . $id . '" value="###' . $valuePrefix . strtoupper($field) . '###" ' . ($readonly ? ' readonly' : '') . $ajax . ' />' . $cal;
                                        break;
                                case 'text':
                                        if (isset($config['config']['wizards']['RTE']) && ExtensionManagementUtility::isLoaded('rtehtmlarea')) {
                                                if (isset($row[$field])) {
                                                        $this->drawRTE($markerArray, $table, $field, $row[$field]);
                                                } else {
                                                        $this->drawRTE($markerArray, $table, $field);
                                                }
                                        } else {
                                                $cols = $config['config']['cols'] ? $config['config']['cols'] : 30;
                                                $rows = $config['config']['rows'] ? $config['config']['rows'] : 5;
                                                $html = '<textarea name="###PREFIXID###[' . $field . ']" id="' . $id . '" cols="' . $cols . '" rows="' . $rows . '"' . $ajax . '>###' . $valuePrefix . strtoupper($field) . '###</textarea>';
                                        }
                                        break;
                                case 'check':
                                        if ($config['config']['items']) {
                                                // TODO
                                        } else {
                                                $html = '<input type="checkbox" name="###PREFIXID###[' . $field . ']" id="' . $id . '" value="1"###' . $valuePrefix . strtoupper($field) . '###' . $ajax . ' />';
                                        }
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
                                                if ($maxitems > 1) {
                                                        $html = $this->drawDoubleSelectBox($table, $field, $ft, $fl, ($config['config']['MM'] ? TRUE : FALSE), $ftWhere, $ajax, $row['pid'], $row, TRUE);
                                                } else {
                                                        $html = $this->drawSimpleSelectBox($table, $field, $ft, $fl, (is_array($config['config']['items']) ? TRUE : FALSE), $ftWhere, $ajax, $row['pid'], TRUE);
                                                }
                                        } else { // liste d'items
                                                $html = '<select name="###PREFIXID###[' . $field . ']" id="' . $id . '"' . $ajax . '>';
                                                foreach ($config['config']['items'] as $valeur) {
                                                        $html.= '<option value="' . $valeur[1] . '"###' . $valuePrefix . strtoupper($field) . '_' . strtoupper($valeur[1]) . '###>' . $GLOBALS['TSFE']->sL($valeur[0]) . '</option>';
                                                }
                                                $html.= '</select>';
                                        }
                                        // Cas particulier, si c'est une liste d'item, on les marques actif
                                        if (isset($row[$field])) {
                                                foreach (GeneralUtility::trimExplode(',', $row[$field]) as $value) {
                                                        $row[$field . '_' . $value] = ' selected="selected"';
                                                }
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
                                                        if ($max_size) {
                                                                $auth[] = '###LABEL_FILE_SIZE### ' . $max_size;
                                                        }
                                                        if ($allowed != '') {
                                                                $auth[] = '###LABEL_ALLOWED### ' . $allowed;
                                                        } elseif ($disallowed != '') {
                                                                $auth[] = '###LABEL_DISALLOWED### ' . $disallowed;
                                                        }
                                                        $html = '<input type="text" name="###PREFIXID###[' . $field . ']" readonly value="###' . $valuePrefix . strtoupper($field) . '###" />';
                                                        $html.= '<span class="param-' . $field . '">' . implode(', ', $auth) . '</span>';
                                                        $html.= '<br />';
                                                        // TODO - apply delete input foreach uploaded file
                                                        if ($config['config']['MM']) {
                                                                // TODO
                                                        } else {
                                                                // TODO - handle multiple upload
                                                                if ($row[$field] != '') {
                                                                        $disable = ' disabled';
                                                                } else {
                                                                        $disable = '';
                                                                }
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
                                        $html = Api::userFunc($config['config']['userFunc'], $PA, $this->caller);
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
                        if (Api::getHook('middle_generateInputMarkersFromTable', $param2, $this->caller)) {
                                $html = $param2['html'];
                        }
                        if ($html != '') {
                                // $valueMarker
                                $markerArray['###' . $inputPrefix . strtoupper($field) . '###'] = $html;
                        }
                }
                $markerArray['###CMDAPI_HIDDEN###'] = '<input type="hidden" name="###PREFIXID###[uid]" value="###' . $valuePrefix . 'UID###" />';
                $markerArray['###CMDAPI_HIDDEN###'].= '<input type="hidden" name="###PREFIXID###[pid]" value="' . $row['pid'] . '" />';

                //on appel le hook de fin de traitement
                Api::getHook('after_generateInputMarkersFromTable', $param, $this->caller);

                // On génère les marker des valeurs de la ligne courante
                if (count($row) > 0) {
                        $lib = GeneralUtility::makeInstance('CMD\\CmdApi\\Lib', $this);
                        $valueMarker = $lib->generateMarkersFromTableRow($table, $row, $tableInMarker, FALSE, FALSE);
                        foreach ($markerArray as $marker => $value) { // ugly but haven't time to boost that
                                $markerArray[$marker] = $this->localcObj->substituteMarkerArray($markerArray[$marker], $valueMarker, $this->wrap, TRUE);
                        }
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
                foreach ($columns as $field => $config) {
                        if ((isset($config['config']['eval']) && in_array('required', GeneralUtility::trimExplode(',', $config['config']['eval']))) || ($config['config']['minitems'] && $config['config']['minitems'] > 0)) {
                                $markers[$this->prefix['required'] . ($tableInMarker ? $table . '_' : '') . $field] = $label;
                        }
                }

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
                        Api::getHook('before_TestFieldForTable', $param, $this->caller);
                        $evaluatedRow[$field] = $value;
                        if ($columns[$field]) {
                                switch ($columns[$field]['config']['type']) {
                                        case 'check': if ($evaluatedRow[$field] == '') {
                                                        $evaluatedRow[$field] = 0;
                                                }
                                                break;
                                        case 'text': if ($columns[$field]['config']['wizards']['RTE']) {
                                                        $this->getRTE($table, $evaluatedRow, $field);
                                                }
                                                $conditions = GeneralUtility::trimExplode(',', $columns[$field]['config']['eval']);
                                                if (in_array('required', $conditions) && $evaluatedRow[$field] == '') {
                                                        $error[$field] = 'required';
                                                }
                                                break;
                                        case 'input':
                                                $conditions = GeneralUtility::trimExplode(',', $columns[$field]['config']['eval']);
                                                $range = $columns[$field]['config']['range'];
                                                $is_in = $columns[$field]['config']['is_in'];
                                                $max = $columns[$field]['config']['max'];
                                                foreach ($conditions as $condition) {
                                                        switch ($condition) {
                                                                case 'required': if (!isset($row[$field]) || trim($evaluatedRow[$field]) == '') {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'required';
                                                                        }
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
                                                                case 'year': if ($evaluatedRow[$field] < 1970 || $evaluatedRow[$field] > 2038) {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'year';
                                                                        }
                                                                        break;
                                                                case 'int': $evaluatedRow[$field] = intval($evaluatedRow[$field]);
                                                                        if (is_array($range) && ($range['lower'] > $evaluatedRow[$field] || $range['upper'] < $evaluatedRow[$field])) {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'range';
                                                                        }
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
                                                                        if (is_array($GLOBALS['TSFE']->sys_page->getRecordsByField($table, $field, $evaluatedRow[$field], ' AND pid>=0'))) {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'unique';
                                                                        }
                                                                        break;
                                                                case 'uniqueInPid':
                                                                        if (is_array($GLOBALS['TSFE']->sys_page->getRecordsByField($table, $field, $evaluatedRow[$field], ' AND pid>=' . $row['pid']))) {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'uniqueInPid';
                                                                        }
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
                                                        if (count($vals) > $maxitems) {
                                                                $error[$field] = 'max';
                                                        } elseif ($MM) {
                                                                $evaluatedRow[$field] = $vals;
                                                        }
                                                } elseif ($minitems > 0) {
                                                        $error[$field] = 'required';
                                                }
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
                                                                        if (count($allowedExtArray) == 0 || in_array('*', $allowedExtArray)) { // si on autorise tout ou que rien n'est spécifié on regardera les extensions refusé
                                                                                $disallowedExtArray = GeneralUtility::trimExplode(',', $columns[$field]['config']['disallowed'], 1);
                                                                        }
                                                                        // on récupère les infos du fichier
                                                                        $fi = pathinfo($_FILES[$this->caller->prefixId]['name'][$field . '_up']);
                                                                        $max_size = $columns[$field]['config']['max_size'];
                                                                        if (GeneralUtility::verifyFilenameAgainstDenyPattern($fi['name']) && ($max_size * 1024) >= $_FILES[$this->caller->prefixId]['size'][$field . '_up']) { // s'il a la bonne taille et un bon nom
                                                                                if ((isset($disallowedExtArray) && in_array(strtolower($fi['extension']), $disallowedExtArray)) ||
                                                                                        (isset($allowedExtArray) && count($allowedExtArray) > 0 && !in_array('*', $allowedExtArray) && !in_array(strtolower($fi['extension']), $allowedExtArray))) {
                                                                                        $error[$field] = ($error[$field] == '' ? '' : ', ') . 'fileext';
                                                                                } // que l'extension est accepté
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
                                                                        } else {
                                                                                $error[$field] = ($error[$field] == '' ? '' : ', ') . 'fileup';
                                                                        }
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
                        }
                        //on appel le hook de fin de traitement
                        $param = array('field' => $field, 'value' => &$evaluatedRow[$field]);
                        Api::getHook('after_TestFieldForTable', $param, $this->caller);
                }
                if (count($error) > 0) {
                        return $error;
                } else {
                        return FALSE;
                }
        }

}
