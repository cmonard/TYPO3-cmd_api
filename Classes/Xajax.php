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
 * Librairie de fonctions specifique XAJAX
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * methode d'appel:
 * 	$cmdXajax = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CMD\\CmdApi\\Xajax', $this);
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   55: class Xajax
 *   66:        public function __construct(&$caller)
 *   82:        public function initXajax($functions = array())
 *  111:        public function getXajaxFunction($funcName, $method, $formName = '', $otherParams = '')
 *  129:        static public function setXajaxAssign($response = array())
 *
 * TOTAL FUNCTIONS: 4
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Xajax {

        // Variable interne
        protected $caller = object;

        /**
         * Constructeur de la class
         *
         * @param	object		$caller: plugin appellant
         * @return	void
         */
        public function __construct(&$caller = object) {
                $this->caller = $caller; // caller object
		// instanciation du moteur ajax
                if (!ExtensionManagementUtility::isLoaded('xajax')) {
                        return 'xajax needed!';
                } elseif (!$this->xajax) {
                        require_once(ExtensionManagementUtility::extPath('xajax') . 'class.tx_xajax.php');
                }
        }

        /**
         * Fonction instanciant le moteur xajax et déclanchant l'appel des procédures
         *
         * @param	array		$functions: tableau des fonctions à instancier dans le moteur ajax array('functionPHP', etc...)
         * @return	l'objet ajax
         */
        public function initXajax($functions = array()) {
                $xajax = GeneralUtility::makeInstance('tx_xajax');
                $xajax->cleanBufferOn(); // clear any white space ;)
                $xajax->decodeUTF8InputOn();
                $xajax->setWrapperPrefix($this->caller->prefixId);
		// Register the names of the PHP functions you want to be able to call through xajax
		// $xajax->registerFunction(array('functionNameInJavascript', &$object, 'methodName'));
                if (count($functions) > 0) {
                        foreach ($functions as $func) {
                                $xajax->registerFunction(array($func, &$this->caller, $func));
                        }
                }
                // basic test but enable to have more than 1 xajax plugin on a page
                if (\in_array($_POST['xajax'], $functions)) {
                        $xajax->processRequests();
                }
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
                if ($formName == '') {
                        $formName = $this->caller->prefixId;
                }
                if (!in_array(strtolower($method), $authorized)) {
                        $method = 'onclick';
                }
                return $method . '="' . $this->caller->prefixId . $funcName . '(xajax.getFormValues(\'' . $formName . '\')' . $otherParams . '); return false;"';
        }

        /**
         * Fonction instanciant et renvoyant la réponse ajax
         * attention: delai est en secondes
         *
         * @param	array		$response: tableau des reponses à préparer et à renvoyer au navigateur array(0 => array('type' => 'xxx', 'id' => 'xxx', 'method' => 'xxx', 'msg' => 'xxx'), etc...)
         * @return	le xml ajax à retourner au navigateur
         */
        static public function setXajaxAssign($response = array()) {
                if (!ExtensionManagementUtility::isLoaded('xajax')) {
                        return 'xajax needed!';
                } else {
                        require_once(ExtensionManagementUtility::extPath('xajax') . 'class.tx_xajax.php');
                }
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
                        ) {
                                continue;
                        }
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
}
