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
 * Librairie de fonctions specifique aux requetes
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * Direct call:
 *      use of namespaces: use CMD\CmdApi\Api; Api::*function_name*
 * 	or: \CMD\CmdApi\Api::*function_name*
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   54: class Query
 *   67:        static public function quoteForLike($fields, $table, $value, $pos = 'end', $reverseSearch = FALSE, $escapeString = TRUE)
 *  117:        static public function cleanIntWhere($field, $value)
 *  128:        static public function cleanIntFind($field, $value)
 *
 * TOTAL FUNCTIONS: 3
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class permettant l'appel au fonctions
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Query {

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
        static public function quoteForLike($fields, $table, $value, $pos = 'end', $reverseSearch = FALSE, $escapeString = TRUE) {
                $localcObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
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
                        if (!is_array($value)) {
                                $value = GeneralUtility::trimExplode(' ', $value, TRUE);
                        }
                        foreach ((array) $value as $val) {
                                $finalValue = $GLOBALS['TYPO3_DB']->quoteStr($val, $table);
                                if ($escapeString) {
                                        $finalValue = $GLOBALS['TYPO3_DB']->escapeStrForLike($finalValue, $table);
                                }
                                $iter[] = $localcObj->substituteMarkerarray($format, array('field' => $table . '.' . $fields, 'value' => $finalValue), '###|###', TRUE);
                        }
                } else {
                        $finalValue = $GLOBALS['TYPO3_DB']->quoteStr($value, $table);
                        if ($escapeString) {
                                $finalValue = $GLOBALS['TYPO3_DB']->escapeStrForLike($finalValue, $table);
                        }
                        foreach ((array) $fields as $field) {
                                $iter[] = $localcObj->substituteMarkerarray($format, array('field' => $table . '.' . $field, 'value' => $finalValue), '###|###', TRUE);
                        }
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
        static public function cleanIntWhere($field, $value) {
                return ' AND ' . $field . ' IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($value) . ')';
        }

        /**
         * Fonction sécurisant et formattant une chaine d'entier pour le SQL (comparaison avec un FIND_IN_SET pour chaque valeur)
         *
         * @param	string		$field: Nom du champ de la table
         * @param	string		$value: list des entiers
         * @return	la chaine de recherche formaté précédé de "AND"
         */
        static public function cleanIntFind($field, $value) {
                $intList = $GLOBALS['TYPO3_DB']->cleanIntList($value);
                if ($intList == 0) {
                        return '';
                } else {
                        $values = GeneralUtility::intExplode(',', $intList);
                        $iter = array();
                        foreach ($values as $val) {
                                $iter[] = 'FIND_IN_SET (' . $val . ', ' . $field . ')';
                        }
                        return ' AND (' . implode(' OR ', $iter) . ')';
                }
        }

}
