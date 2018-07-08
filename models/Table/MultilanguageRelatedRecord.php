<?php

class Table_MultilanguageRelatedRecord extends Omeka_Db_Table
{
    /**
     * Get all the records related to another record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @return array List of record ids.
     */
    public function findRelatedRecords($recordType, $recordId)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordId);
        if (empty($recordIds)) {
            return array();
        }
        sort($recordIds);
        $recordIdsString = implode(',', $recordIds);
        $select = $this->getSelect()
            ->where($this->getTableAlias() . ".record_id IN ($recordIdsString)")
            ->orWhere($this->getTableAlias() . ".related_id IN ($recordIdsString)");
        return $this->fetchObjects($select);
    }

    /**
     * Get all the record ids related to another record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @return array List of record ids.
     */
    public function findRelatedRecordIds($recordType, $recordId)
    {
        $recordId = (int) $recordId;

        /*
         // TODO Write the query that merge the three queries.
         $options = array(
             'record_type' => $recordType,
             'record_id' => $recordId,
         );
         $select = $this->getSelectForFindBy($options)
             ->reset(Zend_Db_Select::COLUMNS)
             ->from(array(), array('related_id'))
             ->where('multilanguage_related_records.related_id != ?', $recordId);
         $relatedIds = $this->getDb()->fetchCol($select);

         $options = array(
             'record_type' => $recordType,
             'related_id' => $recordId,
         );
         $select = $this->getSelectForFindBy($options)
             ->reset(Zend_Db_Select::COLUMNS)
             ->from(array(), array('record_id'))
             ->where('multilanguage_related_records.record_id != ?', $recordId);
         $recordIds = $this->getDb()->fetchCol($select);

         // Add indirect related ids when there are more than one relation.
         $recordIds = array_unique(array_merge($relatedIds, $recordIds));
         // TODO Query to get all indirect ids.
         $recordIds = $this->getDb()->fetchCol($select);
         */

        $db = $this->_db;
        $table = $db->MultilanguageRelatedRecord;
        $sql = "
SELECT DISTINCT(IF(record_id = ?, related_id, record_id)) AS related_record_id
FROM `$table`
WHERE record_type = ?
AND record_id = ? OR related_id = ?
ORDER BY related_record_id;
";
        $recordIds = $db->fetchCol($sql, array($recordId, $recordType, $recordId, $recordId));
        if (empty($recordIds)) {
            return array();
        }

        // Add indirect related ids when there are more than one relation.
        $recordIds[] = $recordId;
        $recordIdsString = implode(',', $recordIds);
        $sql = "
SELECT DISTINCT(IF(record_id IN ($recordIdsString), related_id, record_id)) AS related_record_id
FROM `$table`
WHERE record_type = ?
AND record_id IN ($recordIdsString) OR related_id IN ($recordIdsString)
ORDER BY related_record_id;
";
        $result = $db->fetchCol($sql, array($recordType));

        $result = array_unique(array_merge($recordIds, $result));
        sort($result);

        $recordIdKey = array_search($recordId, $result);
        if ($recordIdKey !== false) {
            unset($result[$recordIdKey]);
        }

        return array_values($result);
    }
}
