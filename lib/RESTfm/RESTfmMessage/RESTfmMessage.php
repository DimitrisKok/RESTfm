<?php
/**
 * RESTfm - FileMaker RESTful Web Service
 *
 * @copyright
 *  Copyright (c) 2011-2016 Goya Pty Ltd.
 *
 * @license
 *  Licensed under The MIT License. For full copyright and license information,
 *  please see the LICENSE file distributed with this package.
 *  Redistributions of files must retain the above copyright notice.
 *
 * @link
 *  http://restfm.com
 *
 * @author
 *  Gavin Stewart
 */

/**
 * RESTfmMessage
 *
 * This message interface provides access to the request/response data sent
 * between formats (web input/output) and backends (database input/output).
 *
 * In general:
 *   Request: import format -> RESTfmMessage -> backend
 *   Response: backend -> RESTfmMessage -> export format
 *
 * In practice, responses are created from raised exceptions as well.
 *
 * Not every request will create a RESTfmMessage as some requests contain
 * no actual data.
 *
 * Every response will contain data and so will create a RESTfmMessage.
 */
class RESTfmMessage implements RESTfmMessageInterface {

    /**
     * A message object for passing request/response data between formats
     *  (web input/output) and backends (database input/output).
     */
    public function __construct () {}

    // -- Sections -- //

    protected $_info = array();         /// @var array of key/value pairs.
    protected $_metaFields = array();   /// @var array of RESTfmMessageRowInterface
    protected $_multistatus = array();  /// @var array of RESTfmMessageMultistatusInterface
    protected $_navs = array();         /// @var array of RESTfmMessageRowInterface
    protected $_records = array();      /// @var array of RESTfmMessageRecordInterface

    /**
     * @var array of known section names.
     */
    protected $_knownSections = array('meta', 'data', 'info',
                                      'metaField', 'multistatus', 'nav');

    /**
     * @var associative array of recordId -> record index
     *  for identifying $_records[] by recordId.
     */
    protected $_recordIdMap = array();

    // --- Access methods for managing data in rows. --- //

    /**
     * Add or update a key/value pair to 'info' section.
     *
     * @param string $key
     * @param string $val
     */
    public function addInfo ($key, $val) {
        $this->_info[$key] = $val;
    }

    /**
     * @return array of key/value pairs.
     */
    public function getInfo () {
        return $this->_info;
    }

    /**
     * Add a message row object to 'metaField' section.
     *
     * @param RESTfmMessageRowInterface $metaField
     */
    public function addMetaField (RESTfmMessageRowInterface $metaField) {
        $this->_metaFields[] = $metaField;
    }

    /**
     * @return array of RESTfmMessageRowInterface.
     */
    public function getMetaFields () {
        return $this->_metaFields;
    }

    /**
     * Add a message multistatus object to 'multistatus' section.
     *
     * @param RESTfmMessageMultistatusInterface $multistatus
     */
    public function addMultistatus (RESTfmMessageMultistatusInterface $multistatus) {
        $this->_multistatus[] = $multistatus;
    }

    /**
     * @return array of RESTfmMessageMultistatusInterface.
     */
    public function getMultistatus () {
        return $this->_multistatus;
    }

    /**
     * Add a message row object to 'nav' section.
     *
     * @param RESTfmMessageRowInterface $nav
     */
    public function addNav (RESTfmMessageRowInterface $nav) {
        $this->_navs[] = $nav;
    }

    /**
     * @return array of RESTfmMessageRowInterface.
     */
    public function getNavs () {
        return $this->_navs;
    }

    /**
     * Add a message record object that contains data for 'data' and 'meta'
     * sections.
     *
     * @param RESTfmMessageRecordInterface $record
     */
    public function addRecord (RESTfmMessageRecordInterface $record) {
        $this->_records[] = $record;

        $recordId = $record->getRecordId();
        if ($recordId !== NULL) {
            // TODO profile this operation
            $recordIndex = count($this->_records) - 1;
            $this->_recordIdMap[$recordId] = $recordIndex;
        }
    }

    /**
     * @return array of RESTfmMessageRecordInterface.
     */
    public function getRecords () {
        return $this->_records;
    }

    /**
     * Return a single record identified by $recordId
     *
     * @param string $recordId
     *
     * @return RESTfmMessageRecordInterface or NULL if $recordId does not exist.
     */
    public function getRecordByRecordId ($recordId) {
        if (isset($this->_recordIdMap[$recordId])) {
            return $this->_records[$this->_recordIdMap[$recordId]];
        }
    }

    // --- Access methods for managing data in sections. --- //

    /**
     * @return array of strings of available section names.
     *      Section names are: meta, data, info, metaField, multistatus, nav
     */
    public function getSectionNames () {
        $availableSections = array();

        // Sort as 'meta', 'data', 'info', <any other>.
        if (!empty($this->_records)) {
            $availableSections[] = 'meta';
            $availableSections[] = 'data';
        }
        if (!empty($this->_info)) { $availableSections[] = 'info'; }
        if (!empty($this->_metaFields)) { $availableSections[] = 'metaField'; }
        if (!empty($this->_multistatus)) { $availableSections[] = 'multistatus'; }
        if (!empty($this->_navs)) { $availableSections[] = 'nav'; }

        return $availableSections;
    }

    /**
     * @param string $sectionName
     *
     * @return RESTfmMessageSectionInterface
     */
    public function getSection ($sectionName) {
        switch ($sectionName) {
            case 'meta':
                $section = new RESTfmMessageSection($sectionName, 2);
                $sectionRows = &$section->_getRowsReference();
                foreach ($this->_records as $record) {
                    $sectionRows[] = &$record->_getMetaReference();
                }
                return $section;
                break;

            case 'data':
                $section = new RESTfmMessageSection($sectionName, 2);
                $sectionRows = &$section->_getRowsReference();
                foreach ($this->_records as $record) {
                    $sectionRows[] = &$record->_getDataReference();
                }
                return $section;
                break;

            case 'info':
                $section = new RESTfmMessageSection($sectionName, 1);
                $sectionRows = &$section->_getRowsReference();
                $sectionRows[] = &$this->_info;
                return $section;
                break;

            case 'metaField':
                $section = new RESTfmMessageSection($sectionName, 2);
                $sectionRows = &$section->_getRowsReference();
                foreach ($this->_metaFields as $row) {
                    $sectionRows[] = &$row->_getDataReference();
                }
                return $section;
                break;

            case 'multistatus':
                $section = new RESTfmMessageSection($sectionName, 2);
                $sectionRows = &$section->_getRowsReference();
                foreach ($this->_multistatus as $row) {
                    $sectionRows[] = &$row->_getMultistatusReference();
                }
                return $section;
                break;

            case 'nav':
                $section = new RESTfmMessageSection($sectionName, 2);
                $sectionRows = &$section->_getRowsReference();
                foreach ($this->_navs as $row) {
                    $sectionRows[] = &$row->_getDataReference();
                }
                return $section;
                break;
        }
    }

    /**
     * @param string $sectionName section name.
     * @param array of section data.
     *  With section data in the form of:
     *    1 dimensional:
     *    array('key' => 'val', ...)
     *   OR
     *    2 dimensional:
     *    array(
     *      array('key' => 'val', ...),
     *      ...
     *    ))
     */
    public function setSection ($sectionName, $sectionData) {
        switch ($sectionName) {
            case 'meta':
                $index = 0;
                foreach ($sectionData as $rowIndex => $row) {
                    if (isset($this->_records[$index])) {
                        $record = $this->_records[$index];
                    } else {
                        $record = new RESTfmMessageRecord();
                        $this->addRecord($record);
                    }
                    foreach ($row as $key => $val) {
                        switch ($key) {
                            case 'href':
                                $record->setHref($val);
                                break;
                            case 'recordID':
                                $record->setRecordId($val);
                                $this->_recordIdMap[$val] = $index;
                                break;
                        }
                    }
                    $index++;
                }
                break;

            case 'data':
                $index = 0;
                foreach ($sectionData as $rowIndex => $row) {
                    if (isset($this->_records[$index])) {
                        $record = $this->_records[$index];
                    } else {
                        $record = new RESTfmMessageRecord();
                        $this->addRecord($record);
                    }
                    $record->setData($row);
                    $index++;
                }
                break;

            case 'info':
                foreach ($sectionData as $key => $val) {
                    $this->_info[$key] = $val;
                }
                break;

            case 'metaField':
                foreach ($sectionData as $rowIndex => $row) {
                    $metaField = new RESTfmMessageRow();
                    $metaField->setData($row);
                    $this->addMetaField($metaField);
                }
                break;

            case 'multistatus':
                foreach ($sectionData as $rowIndex => $row) {
                    $multistatus = new RESTfmMessageMultistatus();
                    foreach ($row as $key => $val) {
                        switch ($key) {
                            // 'index' is depricated for 'recordID' for
                            // consistency on bulk POST/CREATE operations.
                            //case 'index':
                            //    $multistatus->setIndex($val);
                            //    break;
                            case 'Status':
                                $multistatus->setStatus($val);
                                break;
                            case 'Reason':
                                $multistatus->setReason($val);
                                break;
                            case 'recordID':
                                $multistatus->setRecordId($val);
                                break;
                        }
                    }
                    $this->addMultistatus($multistatus);
                }
                break;

            case 'nav':
                foreach ($sectionData as $rowIndex => $row) {
                    $nav = new RESTfmMessageRow();
                    $nav->setData($row);
                    $this->addNav($nav);
                }
                break;
        }
    }

    /**
     * Export all sections as a single associative array.
     *
     * @return array of all sections and data.
     *  With section(s) in the mixed form(s) of:
     *    1 dimensional:
     *    array('sectionNameX' => array('key' => 'val', ...))
     *    2 dimensional:
     *    array('sectionNameY' => array(
     *                              array('key' => 'val', ...),
     *                              ...
     *                           ))
     */
    public function exportArray () {
        $export = array();

        foreach ($this->getSectionNames() as $sectionName) {
            $sectionData = array();
            $section = $this->getSection($sectionName);
            if ($section->getDimensions() == 1) {
                $sectionRows = &$section->_getRowsreference();
                $sectionData = &$sectionRows[0];
            } elseif ($section->getDimensions() == 2) {
                $sectionData = &$section->_getRowsreference();
            }
            //$export[] = array($sectionName => $sectionData);
            $export[$sectionName] = $sectionData;
        }

        return $export;
    }

    /**
     * Import sections and associated data from the provided array.
     *
     * @param associative array $array of section(s) and data.
     *  With section(s) in the mixed form(s) of:
     *    1 dimensional:
     *    array('sectionNameX' => array('key' => 'val', ...))
     *    2 dimensional:
     *    array('sectionNameY' => array(
     *                              array('key' => 'val', ...),
     *                              ...
     *                           ))
     */
    public function importArray ($array) {
        foreach ($array as $sectionName => $sectionData) {
            $this->setSection($sectionName, $sectionData);
        }
    }

    /**
     * Make a human readable string of all sections and data.
     *
     * @return string
     */
    public function __toString () {
        $s = '';
        foreach ($this->getSectionNames() as $sectionName) {
            $s .= $sectionName . ":\n";

            $section = $this->getSection($sectionName);
            if ($section->getDimensions() == 1) {
                $sectionRows = &$section->_getRowsreference();
                $sectionData = &$sectionRows[0];
                foreach ($sectionData as $key => $value) {
                    $s .= '  ' . $key . '="' . addslashes($value) . '"' . "\n";
                }
            } elseif ($section->getDimensions() == 2) {
                $sectionData = &$section->_getRowsreference();
                foreach ($sectionData as $index => $row) {
                    $s .= '  ' . $index . ":\n";
                    foreach ($row as $key => $value) {
                        $s .= '    ' . $key . '="' . addslashes($value) . '"' . "\n";
                    }
                }
            }

            $s .= "\n";
        }
        return $s;
    }

};