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
 * Direct call:
 *      use of namespaces: use CMD\CmdApi\Api; Api::*function_name*
 * 	or: \CMD\CmdApi\Api::*function_name*
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   64: class Api
 *   72:        static public function cleanTemplate($content)
 *   89:        static public function stripAccentsAndSpaces_doTransform($content, $spaceReplacer = '', $transform = '', $trueUTF8 = TRUE)
 *  123:        static public function getCheckboxValues($table, $field, $row)
 *  142:        static public function getFieldsFromTable($table, $exclude = array('l10n_parent', 'l10n_diffsource'))
 *  166:        static public function sendMail($param = array())
 *  236:        static public function getTS($rootPid = 1, $ext = '')
 *  286:        static public function initEID($param, &$connected = '', &$BE_USER = '', $getTSFE = FALSE)
 *  329:        static public function getCache($key, $identifier, $expTime = 0)
 *  344:        static public function setCache($key, $identifier, $data)
 *  358:        static public function autoConnect($userRow)
 *  374:        static public function manageCookie($key, $setAndSave = FALSE, $data = '', $type = 'user', $userObject = NULL)
 *  415:        static public function addPItoST43for6x($key, $namespace, $prefix = '', $type = 'list_type', $cached = 0)
 *
 * TOTAL FUNCTIONS: 12
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Api {

        /**
         * Fonction nettoyant le code des markers non utilisé
         * 
         * @param	string		$content: Contenu à traiter
         * @return	Contenu généré
         */
        static public function cleanTemplate($content) {
                // on nettoie le template des marquers inutilisé
                $content = preg_replace('/###.+?###/is', '', $content);

                // on renvoie le template préparé
                return $content;
        }

        /**
         * Fonction enlevant accent et espace et faisant une transformation en minuscule, majuscule ou capitalise
         * 
         * @param	string		$content: Contenu à traiter
         * @param	string		$spaceReplacer: le caractère de substitution pour les espaces
         * @param	string		$transform: type de transformation ('', 'lower', 'upper', 'capitalise', 'first')
         * @param	bool		$trueUTF8: set to FALSE if db is different from utf-8 and forceCharset = utf-8
         * @return	Contenu généré
         */
        static public function stripAccentsAndSpaces_doTransform($content, $spaceReplacer = '', $transform = '', $trueUTF8 = TRUE) {
                $accent = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
                if ($trueUTF8 && $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] && $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] == 'utf-8') {
                        $content = utf8_decode($content);
                        $accent = utf8_decode($accent);
                }
                $content = strtr($content, $accent, 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn'); // do not handle utf-8 ??
                switch ($transform) {
                        case 'lower':
                                $content = strtolower($content);
                                break;
                        case 'upper':
                                $content = strtoupper($content);
                                break;
                        case 'capitalise':
                                $content = ucwords($content);
                                break;
                        case 'first':
                                $content = ucfirst($content);
                                break;
                        default: break;
                }
                $content = str_replace(' ', $spaceReplacer, $content);
                return $content;
        }

        /**
         * Fonction générant la liste des éléments cochés d'un champ de type check sous forme de tableau associatif: uid => libellé
         *
         * @param	string		$table: Nom de la table
         * @param	string		$field: Nom du champ a traiter de la table
         * @param	array		$row: Ligne courrante de la table
         * @return	le tableau de valeurs pour la ligne courrante ou FALSE en cas d'erreur
         */
        static public function getCheckboxValues($table, $field, $row) {
                $checkConfig = &$GLOBALS['TCA'][$table]['columns'][$field]['config'];
                if ($checkConfig['type'] && $checkConfig['type'] == 'check' && ($nbChecks = count($checkConfig['items'])) > 0) {
                        $checks = array();
                        for ($x = 0; $x < $nbChecks; $x++)
                                if ($row[$field] & pow(2, $x))
                                        $checks[$row['uid']][$x] = $GLOBALS['TSFE']->sL($checkConfig['items'][$x][0]);
                } else
                        $checks = FALSE;
                return $checks;
        }

        /**
         * Fonction renvoyant un tableau des champs de la table données en parametre
         *
         * @param	string		$table: Nom de la table pour laquelle on souhaite récupérer les champs
         * @param	array		$exclude: liste des champs à exclure de la récupération TCA
         * @return	tableau des champs de la table
         */
        static public function getFieldsFromTable($table, $exclude = array('l10n_parent', 'l10n_diffsource')) {
                $columns = &$GLOBALS['TCA'][$table]['columns'];
                $fieldList = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']); // liste des champs de la table

                $return = array();
                foreach ($fieldList as $field)
                        if (!in_array($field, $exclude))
                                $return[] = $field;
                return $return;
        }

        /**
         * Mailing function
         * 
         * @param	array		$param: Array of configuration of the mail
         * 					// sender format : array('email' => validEmail[, 'name' => name])
         * 					// to, cc, bcc, from, reply format : array(name => validEmail[, validEmail[, ...]])
         *      				// return_path, read_receipt : string validEmail
         * 					// priority : integer 1 => 'Highest', 2 => 'High', 3 => 'Normal', 4 => 'Low', 5 => 'Lowest'
         * 					// subject : string
         * 					// body : array('text' => string[, 'format' => 'text/html'])
         * 					// embed, files : array(newFileName => filePathAndName[, filePathAndName[, ...]]) - for embed newFileName will not be used
         * @return	FALSE when missing parameters, otherwise array of result (sent, fail)
         */
        static public function sendMail($param = array()) {
                if (count($param > 0) && isset($param['to']) && isset($param['body'])) {
                        $mail = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
                        // To
                        foreach ((array) $param['to'] as $to_name => $to_email)
                                $mail->addTo($to_email, (!is_numeric($to_name) ? $to_name : NULL));
                        // CC
                        if (isset($param['cc']))
                                foreach ((array) $param['cc'] as $cc_name => $cc_email)
                                        $mail->addCc($cc_email, (!is_numeric($cc_name) ? $cc_name : NULL));
                        // BCC
                        if (isset($param['bcc']))
                                foreach ((array) $param['bcc'] as $bcc_name => $bcc_email)
                                        $mail->addBcc($bcc_email, (!is_numeric($bcc_name) ? $bcc_name : NULL));
                        // Sender
                        if (isset($param['sender']) && $param['sender']['email'])
                                $mail->setSender($param['sender']['email'], (isset($param['sender']['name']) ? $param['sender']['name'] : NULL));
                        // From
                        if (isset($param['from']))
                                foreach ((array) $param['from'] as $from_name => $from_email)
                                        $mail->addFrom($from_email, (!is_numeric($from_name) ? $from_name : NULL));
                        // Reply To
                        if (isset($param['reply']))
                                foreach ((array) $param['reply'] as $reply_name => $reply_email)
                                        $mail->addReplyTo($reply_email, (!is_numeric($reply_name) ? $reply_name : NULL));
                        // Return Path
                        if (isset($param['return_path']))
                                $mail->setReturnPath($param['return_path']);
                        // Priority
                        if (isset($param['priority']))
                                $mail->setPriority(intval($param['priority']));
                        // readReceiptTo
                        if (isset($param['read_receipt']))
                                $mail->setReadReceiptTo($param['read_receipt']);
                        // Subject
                        if (isset($param['subject']))
                                $mail->setSubject($param['subject']);
                        // Bodytext
                        $mail->setBody($param['body']['text'], (isset($param['body']['format']) ? $param['body']['format'] : 'text/html'));
                        if (!isset($param['body']['format']) || $param['body']['format'] == 'text/html')
                                $mail->addPart(strip_tags($param['body']['text']), 'text/plain');
                        // Embed files
                        if ($param['embed'])
                                foreach ((array) $param['embed'] as $file)
                                        $mail->embed(\Swift_Attachment::fromPath($file));
                        // Files
                        if ($param['files'])
                                foreach ((array) $param['files'] as $name => $file)
                                        if (is_numeric($name))
                                                $mail->attach(\Swift_Attachment::fromPath($file));
                                        else
                                                $mail->attach(\Swift_Attachment::fromPath($file)->setFilename($name));
                        // Send the mail !
                        $sent = $mail->send();
                        // Failled send
                        $fail = $mail->getFailedRecipients();
                        // Return the array of result
                        return array('sent' => $sent, 'fail' => $fail);
                } else
                        return FALSE;
        }

        /**
         * Fonction générant et renvoyant le code typoscript d'une page données (utile pour un cron ou un eID)
         * 
         * @param	int			$rootPid: pid de la page dont on doit récupérer le typoscript (1 par default)
         * @param	array		$ext: si spécifié ne retourne que le typoscript de cette extension
         * @param	bool		$useCache: spécifie si on doit utiliser le cache de typo3
         * @return	le tableau de typoscript généré
         */
        static public function getTS($rootPid = 1, $ext = '', $useCache = TRUE) {
                //gestion du cache typo3
                if ($useCache) {
                        $key = $rootPid . '-' . ($ext != '' ? $ext : 'all'); // clé a utilisé pour le cache
                        $cached = self::getCache($key, 'getTS');
                        if ($cached)
                                return unserialize($cached);
                }

                //déclaration des objets necessaire
                $sysPageObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
                $monTSObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');

                //génération de la config
                $sysPageObj->init(TRUE);
                $sysPageObj->getPage($rootPid);
                $rootLine = $sysPageObj->getRootLine($rootPid);   // ID de la page que l'on désire traiter
                $monTSObj->init();
                $monTSObj->runThroughTemplates($rootLine);
                $monTSObj->generateConfig();

                //Récup du setup
                $setup = $monTSObj->setup;

                //destruction des variables et objets
                unset($monTSObj);
                unset($rootLine);
                unset($sysPageObj);

                //si on a spécifié une clef plugin et qu'elle existe
                if ($ext != '' && isset($setup['plugin.'][$ext . '.']))
                        $setup = $setup['plugin.'][$ext . '.'];

                //mise en cache si necessaire
                if ($useCache)
                        self::setCache($key, 'getTS', $setup);

                //Envoie de la conf TS
                return $setup;
        }

        /**
         * Fonction Initialisant les différente partie necessaire de l'eID
         *
         * @param	array		$param: Tableau des instanciations à effectuer bool(lang, db, fe_user, be_user, tca), string(setLang), mixed(tca_ext)
         * @param	object		$connected: objet du fe_user connecté à récupérer à l'appel de la fonction
         * @param	object		$BE_USER: objet du be_user connecté à récupérer à l'appel de la fonction
         * @param	bool		$getTSFE: should TSFE get returned
         * @return	mixed : TRUE or TSFE object
         */
        static public function initEID($param, &$connected = '', &$BE_USER = '') {
                if ($param['lang'])
                        EidUtility::initLanguage(($param['setLang'] ? $param['setLang'] : 'default'));
                if ($param['fe_user'])
                        $connected = EidUtility::initFeUser();
                if ($param['be_user']) {
                        $BE_USER = '';
                        if ($_COOKIE['be_typo_user']) { // If the backend cookie is set, we proceed and checks if a backend user is logged in.
                                // the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
                                $BE_USER = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication'); // New backend user object
                                $BE_USER->OS = TYPO3_OS;
                                $BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
                                $BE_USER->start();  // Object is initialized
                                $BE_USER->unpack_uc('');
                                if ($BE_USER->user['uid']) {
                                        $BE_USER->fetchGroupData();
                                        $TSFE->beUserLogin = 1;
                                }
                                // Now we need to do some additional checks for IP/SSL
                                if (!$BE_USER->checkLockToIP() || !$BE_USER->checkBackendAccessSettingsFromInitPhp()) {
                                        // Unset the user initialization.
                                        $BE_USER = '';
                                        $TSFE->beUserLogin = 0;
                                }
                        }
                }
                if ($param['tca'])
                        EidUtility::initTCA();
                if ($param['tca_ext'])
                        foreach ((array) $param['tca_ext'] as $ext)
                                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($ext))
                                        EidUtility::initExtensionTCA($ext);
                return TRUE;
        }

        /**
         * Récupère l'entrée en cache
         *
         * @param	string		$key clé du cache
         * @param	string		$identifier identifiant unique (nom de fonction par exemple)
         * @param	integer		$expTime maximum lifetime of the cache
         * @return string l'entrée en cache sérialisé
         */
        static public function getCache($key, $identifier, $expTime = 0) {
                if ($GLOBALS['TSFE']->no_cache)
                        return;
                $cacheHash = md5('tx_cmdapi-' . $identifier . $key);
                return \TYPO3\CMS\Frontend\Page\PageRepository::getHash($cacheHash, intval($expTime));
        }

        /**
         * Stores data in cache
         *
         * @param string $key cache key
         * @param string $identifier unique identifier
         * @param array $data your data to store in cache
         * @return	void
         */
        static public function setCache($key, $identifier, $data) {
                if ($GLOBALS['TSFE']->no_cache)
                        return;
                $cacheIdentifier = 'tx_cmdapi-' . $identifier;
                $cacheHash = md5($cacheIdentifier . $key);
                \TYPO3\CMS\Frontend\Page\PageRepository::storeHash($cacheHash, serialize($data), $cacheIdentifier);
        }

        /**
         * Autoconnect front end user in TYPO3
         *
         * @param array $userRow user row from fe_users table
         * @return	void
         */
        static public function autoConnect($userRow) {
                $GLOBALS['TSFE']->fe_user->createUserSession($userRow);
                $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
        }

        /**
         * Manage Cookie or Session information
         *
         * @param string $key the key to set or recover
         * @param bool $setAndSave If TRUE, data is set and saved, otherwise data is recovered form user
         * @param mixte $data the data to set
         * @param string $type user: stored in uc of the user record, otherwise data is stored for session
         * @param object $userObject the user object to work on (get from climode or TSFE if not provided)
         * @return mixte The information if required
         */
        static public function manageCookie($key, $setAndSave = FALSE, $data = '', $type = 'user', $userObject = NULL) {
                $key = 'cmd-' . $key;
                if (!is_object($userObject)) // Get user object is not provided to the function. Depending of TYPO3_cliMode
                        $userObject = (TYPO3_cliMode ? EidUtility::initFeUser() : $GLOBALS['TSFE']->fe_user);
                // Get or Set information
                if ($type == 'user' && $GLOBALS['TSFE']->loginUser) { // persistant
                        if ($setAndSave) {
                                $userObject->setKey($type, $key, $data);
                                $userObject->storeSessionData();
                        } else
                                return $userObject->getKey($type, $key);
                } else { // non persistant
                        if ($setAndSave)
                                $userObject->setAndSaveSessionData($key, $data);
                        else
                                return $userObject->getSessionData($key);
                }
        }

        /**
         * Add PlugIn to Static Template #43, namespace version of TYPO3 original function
         *
         * When adding a frontend plugin you will have to add both an entry to the TCA definition of tt_content table AND to the TypoScript template which must initiate the rendering.
         * Since the static template with uid 43 is the "content.default" and practically always used for rendering the content elements it's very useful to have this function automatically adding the necessary TypoScript for calling your plugin. It will also work for the extension "css_styled_content"
         * $type determines the type of frontend plugin:
         * + list_type (default) - the good old "Insert plugin" entry
         * + menu_type - a "Menu/Sitemap" entry
         * + CType - a new content element type
         * + header_layout - an additional header type (added to the selection of layout1-5)
         * + includeLib - just includes the library for manual use somewhere in TypoScript.
         * (Remember that your $type definition should correspond to the column/items array in $GLOBALS['TCA'][tt_content] where you added the selector item for the element! See addPlugin() function)
         * FOR USE IN ext_localconf.php FILES
         *
         * @param string $key The extension key
         * @param string $namespace The PHP-namespace. naming convention (eg. CMD\\CmdApi\\Api)
         * @param string $prefix Is used as a - yes, suffix - of the class name (fx. "_pi1")
         * @param string $type See description above
         * @param integer $cached If $cached is set as USER content object (cObject) is created - otherwise a USER_INT object is created.
         *
         * @return void
         */
        static public function addPItoST43for6x($key, $namespace, $prefix = '', $type = 'list_type', $cached = 0) {
                $cN = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($key);
                // General plugin
                $pluginContent = trim('
plugin.' . $cN . $prefix . ' = USER' . ($cached ? '' : '_INT') . '
plugin.' . $cN . $prefix . ' {
        userFunc = ' . $namespace . '->main
}');
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $pluginContent);
                // After ST43
                switch ($type) {
                        case 'list_type':
                                $addLine = 'tt_content.list.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'menu_type':
                                $addLine = 'tt_content.menu.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'CType':
                                $addLine = trim('
 tt_content.' . $key . $prefix . ' = COA
 tt_content.' . $key . $prefix . ' {
         10 = < lib.stdheader
         20 = < plugin.' . $cN . $prefix . '
 }
 ');
                                break;
                        case 'header_layout':
                                $addLine = 'lib.stdheader.10.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'includeLib':
                                $addLine = 'page.1000 = < plugin.' . $cN . $prefix;
                                break;
                        default:
                                $addLine = '';
                }
                if ($addLine) {
                        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $addLine . '
', 43);
                }
        }

}

?>