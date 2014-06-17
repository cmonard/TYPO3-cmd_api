<?php

namespace CMD\CmdApi\Cbl;

/* * ***********************************************************
 *  Copyright notice
 *
 *  (c) 2006-2013 Christophe Monard (contact@cmonard.fr)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
/**
 * Include file extending recordList which extended t3lib_recordList
 * Used specifically for the Web>List module (db_list.php)
 *
 * Revised for TYPO3 6.2.0 March/2014 by Christophe Monard
 * XHTML compliant
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   76: class LocalRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
 *   92:        public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0)
 *  211:        private function makecustomButtonList($table, $uid, $language = 0)
 *
 * TOTAL FUNCTIONS: 2
 *
 *  246: class Localrecordlist_getTable implements \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface
 *  248:        public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject)
 *
 * TOTAL FUNCTIONS: 1
 *
 *  267: class Localrecordlist_actions implements \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface
 *  269:        public function makeClip($table, $row, $cells, &$parentObject)
 *  273:        public function makeControl($table, $row, $cells, &$parentObject)
 *  277:        public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject)
 *  283:        public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class for rendering of Web>List module
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 * @package TYPO3
 * @subpackage Recordlist
 */
class LocalRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {

        /**
         * Rendering a single row for the list
         *
         * @param string $table Table name
         * @param array $row Current record
         * @param integer $cc Counter, counting for each time an element is rendered (used for alternating colors)
         * @param string $titleCol Table field (column) where header value is found
         * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
         * @param integer $indent Indent from left.
         * @return string Table row for the element
         * @access private
         * @see getTable()
         * @todo Define visibility
         */
	public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0) {
		$iOut = '';
		// If in search mode, make sure the preview will show the correct page
		if (strlen($this->searchString)) {
			$id_orig = $this->id;
			$this->id = $row['pid'];
		}
		if (is_array($row)) {
			// Add special classes for first and last row
			$rowSpecial = '';
			if ($cc == 1 && $indent == 0) {
				$rowSpecial .= ' firstcol';
			}
			if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
				$rowSpecial .= ' lastcol';
			}
			// Background color, if any:
			if ($this->alternateBgColors) {
				$row_bgColor = $cc % 2 ? ' class="db_list_normal' . $rowSpecial . '"' : ' class="db_list_alt' . $rowSpecial . '"';
			} else {
				$row_bgColor = ' class="db_list_normal' . $rowSpecial . '"';
			}
			// Overriding with versions background color if any:
			$row_bgColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $row_bgColor;
			// Incr. counter.
			$this->counter++;
			// The icon with link
			$alttext = BackendUtility::getRecordIconAltText($row, $table);
			$iconImg = IconUtility::getSpriteIconForRecord($table, $row, array('title' => htmlspecialchars($alttext), 'style' => $indent ? ' margin-left: ' . $indent . 'px;' : ''));
			$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
			// Preparing and getting the data-array
			$theData = array();
			foreach ($this->fieldArray as $fCol) {
				if ($fCol == $titleCol) {
					$recTitle = BackendUtility::getRecordTitle($table, $row, FALSE, TRUE);
					// If the record is edit-locked	by another user, we will show a little warning sign:
					if ($lockInfo = BackendUtility::isRecordLocked($table, $row['uid'])) {
						$warning = '<a href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . '); return false;" title="' . htmlspecialchars($lockInfo['msg']) . '">' . IconUtility::getSpriteIcon('status-warning-in-use') . '</a>';
					}
					$theData[$fCol] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
					// Render thumbnails, if:
					// - a thumbnail column exists
					// - there is content in it
					// - the thumbnail column is visible for the current type
					$type = 0;
					if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
						$typeColumn = $GLOBALS['TCA'][$table]['ctrl']['type'];
						$type = $row[$typeColumn];
					}
					// If current type doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
					if (!isset($GLOBALS['TCA'][$table]['types'][$type])) {
						$type = isset($GLOBALS['TCA'][$table]['types'][0]) ? 0 : 1;
					}
					$visibleColumns = $GLOBALS['TCA'][$table]['types'][$type]['showitem'];

					if ($this->thumbs &&
						trim($row[$thumbsCol]) &&
						preg_match('/(^|(.*(;|,)?))' . $thumbsCol . '(((;|,).*)|$)/', $visibleColumns) === 1
					) {
						$theData[$fCol] .= '<br />' . $this->thumbCode($row, $table, $thumbsCol);
					}
					$localizationMarkerClass = '';
					if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0 && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0) {
						// It's a translated record with a language parent
						$localizationMarkerClass = ' localization';
					}
				} elseif ($fCol == 'pid') {
					$theData[$fCol] = $row[$fCol];
				} elseif ($fCol == '_PATH_') {
					$theData[$fCol] = $this->recPath($row['pid']);
				} elseif ($fCol == '_REF_') {
					$theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
                                } elseif ($fCol == '_CUSTOMBUTTONLIST_') { // Custom button list
                                        $theData[$fCol] = $this->makecustomButtonList($table, $row['uid'], ($row['sys_language_uid'] ? $row['sys_language_uid'] : 0));
				} elseif ($fCol == '_CONTROL_') {
					$theData[$fCol] = $this->makeControl($table, $row);
				} elseif ($fCol == '_AFTERCONTROL_' || $fCol == '_AFTERREF_') {
					$theData[$fCol] = '&nbsp;';
				} elseif ($fCol == '_CLIPBOARD_') {
					$theData[$fCol] = $this->makeClip($table, $row);
				} elseif ($fCol == '_LOCALIZATION_') {
					list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
					$theData[$fCol] = $lC1;
					$theData[$fCol . 'b'] = $lC2;
				} elseif ($fCol == '_LOCALIZATION_b') {

				} else {
					$tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
					$theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
					if ($this->csvOutput) {
						$row[$fCol] = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
					}
				}
			}
			// Reset the ID if it was overwritten
			if (strlen($this->searchString)) {
				$this->id = $id_orig;
			}
			// Add row to CSV list:
			if ($this->csvOutput) {
				$this->addToCSV($row, $table);
			}
			// Add classes to table cells
			$this->addElement_tdCssClass[$titleCol] = 'col-title' . $localizationMarkerClass;
			if (!$this->dontShowClipControlPanels) {
				$this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
				$this->addElement_tdCssClass['_AFTERCONTROL_'] = 'col-control-space';
				$this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
			}
			$this->addElement_tdCssClass['_PATH_'] = 'col-path';
			$this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
			$this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
			// Create element in table cells:
			$iOut .= $this->addelement(1, $theIcon, $theData, $row_bgColor);
			// Finally, return table row element:
			return $iOut;
		}
	}

        private function makecustomButtonList($table, $uid, $language = 0) {
                $BE_L = $GLOBALS['LANG']->lang;
                //variable de fonctionnement
                $cbl = &$GLOBALS['SOBE']->modTSconfig['properties']['customButtonList.'];
                $cells = array();
                $L = ($language != 0) ? '&L=' . $language : '';  // prise en compte de la langue
                // on va parcourir la liste des boutons pour les ajouter
                if (count($cbl) > 0)
                        foreach ($cbl as $button)
                                if ($button['table'] == $table) {
                                        // Onclick params
                                        $onclickId = $button['id'] ? $button['id'] : ($button['altURL'] ? '' : $this->id);
                                        $onclickRootline = BackendUtility::BEgetRootLine($this->id);
                                        $onclickAlturl = $button['altURL'] ? $button['altURL'] . $uid : '';
                                        $onclickAdditionalParams = ($button['additionalParams'] != '' ? str_replace('###uid###', $uid, $button['additionalParams']) : '') . $L;
                                        $onclick = BackendUtility::viewOnClick($onclickId, '', $onclickRootline, '', $onclickAlturl, $onclickAdditionalParams);
                                        // Img params
                                        $img = '../' . ($button['image'] ? $button['image'] : 'typo3conf/ext/cmd_api/res/cbl/' . ($button['icon'] ? $button['icon'] : 'view_icon.gif'));
                                        // the Cell
                                        $cells[] = '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' .
                                                '<img' . IconUtility::skinImg($this->backPath, $img, 'width="14" height="14"') . ' title="' . $button['title.'][$BE_L] . '" alt="" />' .
                                                '</a>';
                                }

                // on affiche les boutons
                if (count($cells) > 0) {
                        // Compile items into a DIV-element:
                        return '<!-- CONTROL PANEL: ' . $table . ':' . $uid . ' -->
					<div class="typo3-clipCtrl">' . implode('', $cells) . '</div>';
                } else
                        return '';
        }

}

class Localrecordlist_getTable implements \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface {

        public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject) {
                // Custom button list positionnable
                if ($GLOBALS['SOBE']->modTSconfig['properties']['customButtonList']) {
                        $tmpArray = array();
                        if ($pos = $GLOBALS['SOBE']->modTSconfig['properties']['customButtonListPos']) {
                                $tmpArray[0] = array_slice($parentObject->fieldArray, 0, $pos);
                                $tmpArray[2] = array_slice($parentObject->fieldArray, $pos);
                        } else
                                $tmpArray[0] = $parentObject->fieldArray;
                        $tmpArray[1] = array(0 => '_CUSTOMBUTTONLIST_');
                        ksort($tmpArray);
                        $parentObject->fieldArray = array_merge($tmpArray[0], $tmpArray[1]);
                        if ($tmpArray[2])
                                $parentObject->fieldArray = array_merge($parentObject->fieldArray, $tmpArray[2]);
                }
        }

}

class Localrecordlist_actions implements \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface {

        public function makeClip($table, $row, $cells, &$parentObject) {
                return $cells;
        }

        public function makeControl($table, $row, $cells, &$parentObject) {
                return $cells;
        }

        public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject) {
                if ($headerColumns['_CUSTOMBUTTONLIST_'])
                        $headerColumns['_CUSTOMBUTTONLIST_'] = '';
                return $headerColumns;
        }

        public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject) {
                return $cells;
        }

}

?>