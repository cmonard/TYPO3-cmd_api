<?php

namespace CMD\CmdApi\DBList;

class beAlternateSorting {

        public function makeQueryArray_post(&$queryParts, $pObj, $table, $id, $addWhere, $fieldList, $_params) {
                if (isset($pObj->modTSconfig['properties']['alternateSortingField.']) && isset($pObj->modTSconfig['properties']['alternateSortingField.'][$table]))
                        $queryParts['ORDERBY'] = $pObj->modTSconfig['properties']['alternateSortingField.'][$table];
        }

}

?>