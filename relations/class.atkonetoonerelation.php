<?php
/**
 * This file is part of the ATK distribution on GitHub.
 * Detailed copyright and licensing information can be found
 * in the doc/COPYRIGHT and doc/LICENSE files which should be
 * included in the distribution.
 *
 * @package atk
 * @subpackage relations
 *
 * @copyright (c)2000-2004 Ivo Jansch
 * @license http://www.achievo.org/atk/licensing ATK Open Source License
 *
 * @version $Revision: 6320 $
 * $Id$
 */
/**
 * flags specific for atkOneToOneRelation
 */
/**
 * Override the default no add flag
 */
define("AF_ONETOONE_ADD", AF_SPECIFIC_1);

/**
 * Enable error notifications / triggers
 */
define("AF_ONETOONE_ERROR", AF_SPECIFIC_2);

/**
 * Invisibly integrate a onetoonerelation as if the fields where part of the current node.  
 * If the relation is integrated, no divider is drawn, and the section heading is suppressed.
 * (Integration does not affect the way data is stored or manipulated, only how it is displayed.)
 */
define("AF_ONETOONE_INTEGRATE", AF_SPECIFIC_3);

/**
 * Use lazy loading instead of query addition.
 */
define("AF_ONETOONE_LAZY", AF_SPECIFIC_4);

/**
 * Respects tab/sections that have been assigned to this attribute instead of using the
 * tabs assigned for the attributes in the destination node. This flag is only useful in
 * integration mode.
 */
define("AF_ONETOONE_RESPECT_TABS", AF_SPECIFIC_5);

/**
 * @internal Include the base class.
 */
Atk_Tools::userelation("atkrelation");

/**
 * Implementation of one-to-one relationships.
 *
 * An atkOneToOneRelation defines a relation between two tables where there
 * is one record in the first table that belongs to one record in the
 * second table.
 *
 * When editing a one-to-one relation, the form for the destination record
 * is embedded in the form of the master record. When using the flag
 * AF_ONETOONE_INTEGRATE, this is done transparantly so the user does not
 * even notice that the data he's editing comes from 2 separate tables.
 *
 * @author Ivo Jansch <ivo@achievo.org>
 * @package atk
 * @subpackage relations
 *
 */
class Atk_OneToOneRelation extends Atk_Relation
{
    /**
     * The name of the referential key attribute in the target node.
     * @access private
     * @var String
     */
    var $m_refKey = "";

    /**
     * Default Constructor
     *
     * The atkOneToOneRelation supports two configurations:
     * - Master mode: The current node is considered the master, and the
     *                referential key pointing to the master record is in the
     *                destination node.
     * - Slave mode: The current node is considered the child, and the
     *               referential key pointing to the master record is in the
     *               current node.
     * The mode to use is detected automatically based on the value of the
     * $refKey parameter.
     *
     * <b>Example:</b>
     * <code>
     * $this->add(new atkOneToOneRelation("child", "mymod.childnode", "parent_id"));
     * </code>
     *
     * @param String $name The unique name of the attribute. In slave mode,
     *                     this corresponds to the foreign key field in the
     *                     database table.  (The name is also used as the section 
     *                     heading.)
     * @param String $destination the destination node (in module.nodename
     *                            notation)
     * @param String $refKey In master mode, this specifies the foreign key
     *                       field from the destination node that points to
     *                       the master record. In slave mode, this parameter
     *                       should be empty.
     * @param int $flags Attribute flags that influence this attributes'
     *                   behavior.
     */
    function __construct($name, $destination, $refKey = "", $flags = 0)
    {
        if ($flags & AF_ONETOONE_ADD != AF_ONETOONE_ADD)
            $flags |= AF_NO_ADD;
        parent::__construct($name, $destination, $flags | AF_ONETOONE_LAZY);
        $this->m_refKey = $refKey;
    }

    /**
     * Returns a displayable string for this value, to be used in HTML pages.
     *
     * The atkOneToOneRelation displays all values from the destination
     * records in "view" mode. In "list" mode, the record descriptor of the
     * target record is displayed.
     *
     * @param array $record The record that holds the value for this attribute
     * @param String $mode The display mode ("view" for viewpages, or "list"
     *                     for displaying in recordlists)
     * @return String HTML String
     */
    function display($record, $mode = "list")
    {
        if ($mode == 'view') {
            return null;
        }

        $myrecord = $record[$this->fieldName()];

        if ($this->createDestination() && is_array($myrecord)) {
            $result = $this->m_destInstance->descriptor($myrecord);
        } else {
            $result = $this->text('none');
        }

        return $result;
    }

    /**
     * Returns a piece of html code that can be used in a form to edit this
     * attribute's value.
     *
     * Because of the AF_INTEGRATE feature, the edit() method has a void
     * implementation. The actual edit code is handled by addToEditArray().
     */
    function edit()
    {
        
    }

    /**
     * Set the initial values of this attribute
     *
     * @return array Array with initial values
     */
    function initialValue()
    {
        if ($this->m_initialValue !== null)
            return parent::initialValue();

        if ($this->createDestination()) {
            return $this->m_destInstance->initial_values();
        }

        return null;
    }

    /**
     * Adds this attribute to database queries.
     *
     * Database queries (select, insert and update) are passed to this method
     * so the attribute can 'hook' itself into the query.
     *
     * Framework method. It should not be necessary to call this method
     * directly. This implementation performs a join to retrieve the
     * target records' data, unless AF_ONETOONE_LAZY is set, in which case
     * loading is delayed and performed later using the load() method.
     * For update and insert queries, this method does nothing. These are
     * handled by the store() method.
     *
     * @param Atk_Query $query The SQL query object
     * @param String $tablename The name of the table of this attribute
     * @param String $fieldaliasprefix Prefix to use in front of the alias
     *                                 in the query.
     * @param Array $rec The record that contains the value of this attribute.
     * @param int $level Recursion level if relations point to eachother, an
     *                   endless loop could occur if they keep loading
     *                   eachothers data. The $level is used to detect this
     *                   loop. If overriden in a derived class, any subcall to
     *                   an addToQuery method should pass the $level+1.
     * @param String $mode Indicates what kind of query is being processing:
     *                     This can be any action performed on a node (edit,
     *                     add, etc) Mind you that "add" and "update" are the
     *                     actions that store something in the database,
     *                     whereas the rest are probably select queries.
     */
    function addToQuery(&$query, $tablename = "", $fieldaliasprefix = "", $rec = "", $level = 0, $mode = "")
    {
        if ($this->createDestination()) {
            if ($mode != "update" && $mode != "add") {
                if ($this->hasFlag(AF_ONETOONE_LAZY)) {
                    if ($this->m_refKey == "") {
                        return parent::addToQuery($query, $tablename, $fieldaliasprefix, $rec, $level, $mode);
                    }
                }

                if ($tablename != "")
                    $tablename.=".";

                if ($this->m_refKey != "") {
                    // Foreign key is in the destination node.
                    $condition = $tablename . $this->m_ownerInstance->m_primaryKey[0] . "=" . $fieldaliasprefix . $this->fieldName() . "." . $this->m_refKey;
                } else {
                    // Foreign key is in the source node
                    $condition = $tablename . $this->fieldName() . "=" . $fieldaliasprefix . $this->fieldName() . "." . $this->m_destInstance->m_primaryKey[0];
                }

                $condition.= $this->getDestinationFilterCondition($fieldaliasprefix);
                $query->addJoin($this->m_destInstance->m_table, $fieldaliasprefix . $this->fieldName(), $condition, true, $mode);

                // we pass true as the last param to addToQuery, because we need all fields..
                $this->m_destInstance->addToQuery($query, $fieldaliasprefix . $this->fieldName(), $level + 1, true, $mode);
            }

            // When storing, we don't add to the query.. we have our own store() method..
            // With one exception. If the foreign key is in the source node, we also need to update
            // the refkey value.
            if ($this->m_refKey == "" && $mode == "add") {
                $query->addField($this->fieldName(), $rec[$this->fieldName()][$this->m_destInstance->m_primaryKey[0]], "", "", !$this->hasFlag(AF_NO_QUOTES));
            }
        }
    }

    /**
     * Retrieve detail records from the database.
     *
     * Called by the framework to load the detail records.
     *
     * @param Atk_Db $db The database used by the node.
     * @param array $record The master record
     * @param String $mode The mode for loading (admin, select, copy, etc)
     *
     * @return array Recordset containing detailrecords, or NULL if no detail
     *               records are present. Note: when $mode is edit, this
     *               method will always return NULL. This is a framework
     *               optimization because in edit pages, the records are
     *               loaded on the fly.
     */
    function load(&$db, $record, $mode)
    {
        if ($this->createDestination()) {
            if ($this->m_refKey == "") {
                // Foreign key in owner
                //$condition = $this->m_destInstance->m_primaryKey[0]."=".$record[$this->fieldName()];
                $condition = $this->m_destInstance->m_table . '.' . $this->m_destInstance->m_primaryKey[0] .
                    "='" . $record[$this->fieldName()] . "'";
            } else {
                // Foreign key in destination
                $condition = $this->m_destInstance->m_table . '.' . $this->m_refKey . "='" .
                    $this->m_ownerInstance->m_attribList[$this->m_ownerInstance->primaryKeyField()]->value2db($record) . "'";

                $destfilter = $this->getDestinationFilter();
                if (is_string($destfilter) && $destfilter != "") {
                    $condition .= " AND " . $this->m_destInstance->m_table . "." . $destfilter;
                }
            }
            $recs = $this->m_destInstance->selectDb($condition, "", "", "", "", $mode);
            return isset($recs[0]) ? $recs[0] : null;
        }
    }

    /**
     * Construct the filter statement for filters that are set for the
     * destination node (m_destinationFilter).
     * @access private
     * @param string $fieldaliasprefix
     * @return String A where clause condition.
     */
    function getDestinationFilterCondition($fieldaliasprefix = "")
    {
        $condition = "";
        if (is_array($this->m_destinationFilter)) {
            for ($i = 0, $_i = count($this->m_destinationFilter); $i < $_i; $i++) {
                $condition.=" AND " . $fieldaliasprefix . $this->m_name . "." . $this->m_destinationFilter[$i];
            }
        } elseif ($this->m_destinationFilter != "") {
            $condition.=" AND " . $fieldaliasprefix . $this->m_name . "." . $this->m_destinationFilter;
        }
        return $condition;
    }

    /**
     * The delete method is called by the framework to inform the attribute
     * that the master record is deleted.
     *
     * Note that the framework only calls the method when the
     * AF_CASCADE_DELETE flag is set. When calling this method, the detail
     * record belonging to the master record is deleted.
     *
     * @param array $record The record that is deleted.
     * @return boolean true if cleanup was successful, false otherwise.
     */
    function delete($record)
    {
        $classname = $this->m_destination;
        $cache_id = $this->m_owner . "." . $this->m_name;
        $rel = Atk_Module::atkGetNode($classname, true, $cache_id);
        Atk_Tools::atkdebug("O2O DELETE for $classname: " . $this->m_refKey . "=" . $record[$this->m_ownerInstance->primaryKeyField()]);

        if ($this->m_refKey != "") {
            // Foreign key is in the destination node
            $condition = $rel->m_table . '.' . $this->m_refKey . "=" .
                $this->m_ownerInstance->m_attribList[$this->m_ownerInstance->primaryKeyField()]->value2db($record);
        } else {
            // Foreign key is in the source node.
            $condition = $rel->m_table . '.' . $rel->m_primaryKey[0] . "=" . $record[$this->fieldName()][$this->m_ownerInstance->primaryKeyField()];
        }

        return $rel->deleteDb($condition);
    }

    /**
     * Converts the internal attribute value to one that is understood by the
     * database.
     *
     * For the regular atkAttribute, this means escaping things like
     * quotes and slashes. Derived attributes may reimplement this for their
     * own conversion.
     * This is the exact opposite of the db2value method.
     *
     * @param array $rec The record that holds this attribute's value.
     * @return String The database compatible value
     */
    function db2value($rec)
    {
        // we need to pass all values to the destination node, so it can
        // run it's db2value stuff over it..
        if ($this->hasFlag(AF_ONETOONE_LAZY) && $this->m_refKey == "") {
            return parent::db2value($rec);
        }

        if ($this->createDestination()) {
            (isset($rec[$this->fieldName()][$this->m_destInstance->primaryKeyField()]))
                        ?
                    $pkval = $rec[$this->fieldName()][$this->m_destInstance->primaryKeyField()]
                        : $pkval = NULL;
            if ($pkval != NULL && $pkval != "") { // If primary key is not filled, there was no record, so we
                // should return NULL.
                foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
                    $p_attrib = &$this->m_destInstance->m_attribList[$attribname];
                    $rec[$this->fieldName()][$attribname] = $p_attrib->db2value($rec[$this->fieldName()]);
                }
                // also set the primkey..
                $rec[$this->fieldName()]["atkprimkey"] = $this->m_destInstance->primaryKey($rec[$this->fieldName()]);
                return $rec[$this->fieldName()];
            }
        }
        return NULL;
    }

    /**
     * Initialize this destinations attribute sizes.
     */
    function fetchMeta()
    {
        if ($this->hasFlag(AF_ONETOONE_INTEGRATE)) {
            $this->createDestination();
            $this->getDestination()->setAttribSizes();
        }
    }

    /**
     * Convert values from an HTML form posting to an internal value for
     * this attribute.
     *
     * This implementation uses the destination node to fetch any field that
     * belongs to the other side of the relation.
     *
     * @param array $postvars The array with html posted values ($_POST, for
     *                        example) that holds this attribute's value.
     * @return String The internal value
     */
    function fetchValue($postvars)
    {
        // we need to pass all values to the destination node, so it can
        // run it's fetchValue stuff over it..
        if ($this->createDestination()) {
            if ($postvars[$this->fieldName()] != NULL) {
                foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
                    $p_attrib = &$this->m_destInstance->m_attribList[$attribname];
                    $postvars[$this->fieldName()][$attribname] = $p_attrib->fetchValue($postvars[$this->fieldName()]);
                }
                return $postvars[$this->fieldName()];
            }
        }
    }

    /**
     * Determine the storage type of this attribute.
     *
     * With this method, the attribute tells the framework whether it wants
     * to be stored in the main query (addToQuery) or whether the attribute
     * has its own store() implementation.
     * For the atkOneToOneRelation, the results depends on whether the
     * relation is used in master or slave mode.
     *
     * Framework method. It should not be necesary to call this method
     * directly.
     *
     * @param String $mode The type of storage ("add" or "update")
     *
     * @return int Bitmask containing information about storage requirements.
     *             POSTSTORE  when in master mode.
     *             PRESTORE|ADDTOQUERY when in slave mode.
     */
    function storageType($mode)
    {
        // Mode specific storage type.
        if (isset($this->m_storageType[$mode]) && $this->m_storageType[$mode] !== null)
            return $this->m_storageType[$mode];

        // Global storage type (key null is special!)
        else if (isset($this->m_storageType[null]) && $this->m_storageType[null] !== null)
            return $this->m_storageType[null];

        else if ($this->m_refKey != "") {
            // foreign key is in destination node, so we must store the
            // destination AFTER we stored the master record.
            return POSTSTORE;
        } else {
            // foreign key is in source node, so we must store the
            // relation node first, so we can store the foreign key
            // when we store the master record. To store the latter,
            // we must also perform an addToQuery.
            return PRESTORE | ADDTOQUERY;
        }
    }

    /**
     * Determine the load type of this attribute.
     *
     * With this method, the attribute tells the framework whether it wants
     * to be loaded in the main query (addToQuery) or whether the attribute
     * has its own load() implementation.
     * For the atkOneToOneRelation, this depends on the presence of the
     * AF_ONETOONE_LAZY flag.
     *
     * Framework method. It should not be necesary to call this method
     * directly.
     *
     * @param String $mode The type of load (view,admin,edit etc)
     *
     * @return int Bitmask containing information about load requirements.
     *             POSTLOAD|ADDTOQUERY when AF_ONETOONE_LAZY is set.
     *             ADDTOQUERY when AF_ONETOONE_LAZY is not set.
     */
    function loadType($mode)
    {
        if (isset($this->m_loadType[$mode]) && $this->m_loadType[$mode] !== null)
            return $this->m_loadType[$mode];
        else if (isset($this->m_loadType[null]) && $this->m_loadType[null] !== null)
            return $this->m_loadType[null];
        else if ($this->hasFlag(AF_ONETOONE_LAZY))
            return POSTLOAD | ADDTOQUERY;
        else
            return ADDTOQUERY;
    }

    /**
     * Store detail record in the database.
     *
     * @param Atk_Db $db The database used by the node.
     * @param array $record The master record which has the detail records
     *                      embedded.
     * @param string $mode The mode we're in ("add", "edit", "copy")
     * @return boolean true if store was successful, false otherwise.
     */
    function store(&$db, &$record, $mode)
    {
        if ($this->createDestination()) {
            $vars = &$this->_getStoreValue($record);
            if ($vars["mode"] == "edit") {
                Atk_Tools::atkdebug("Updating existing one2one record");
                // we put the vars in the postvars, because there is information
                // like atkorgkey in it that is vital.
                // but we restore the postvars after we're done updating
                $oldpost = $this->m_destInstance->m_postvars;
                $this->m_destInstance->m_postvars = $vars;
                $res = $this->m_destInstance->updateDb($vars);
                $this->m_destInstance->m_postvars = $oldpost;
                return $res;
            } elseif ($vars["mode"] == "add" || $mode == "add" || $mode == "copy") {
                if (!empty($vars["atkprimkey"]) && $mode != "copy") {
                    // destination record already exists, and we are not copying.
                    $result = true;
                } else {
                    Atk_Tools::atkdebug("atkonetoonerelation->store(): Adding new one2one record for mode $mode");
                    $this->m_destInstance->preAdd($vars);
                    $result = $this->m_destInstance->addDb($vars, true, $mode);
                }
                if ($this->m_refKey == "") {
                    // Foreign key is in source node, so we must update the record value with
                    $record[$this->fieldName()][$this->m_destInstance->m_primaryKey[0]] = $vars[$this->m_destInstance->m_primaryKey[0]];
                }
                return $result;
            } else {
                Atk_Tools::atkdebug("atkonetoonerelation->store(): Nothing to store in one2one record");
                return true;
            }
        }
    }

    /**
     * Needs update?
     *
     * @param array $record the record
     * @return boolean needs update
     */
    function needsUpdate($record)
    {
        return $this->m_forceupdate ||
            (parent::needsUpdate($record) &&
            $this->createDestination() &&
            !$this->m_destInstance->hasFlag(NF_READONLY));
    }

    /**
     * Gets the value to store for the onetoonerelation
     *
     * @param Array &$record The record to get the value from
     * @return mixed The value to store
     */
    function &_getStoreValue(&$record)
    {
        $vars = &$record[$this->fieldName()];
        if ($this->m_refKey != "") {
            // Foreign key is in destination node
            if ($this->destinationHasRelation()) {
                $vars[$this->m_refKey][$this->m_ownerInstance->primaryKeyField()] = $record[$this->m_ownerInstance->primaryKeyField()];
            } else {
                //if the a onetoonerelation has no relation on the other side the m_refKey is not an array
                // experimental, will the next line always work?
                $refattr = $this->m_destInstance->getAttribute($this->m_refKey);
                if ($refattr->m_destination) {
                    /**
                     * If we have a destination, the ref key is a non-onetoone relation!
                     * So we have to treat the record as such... this is specifically geared towards
                     * the manytoone relation and may not work for others
                     * A way should be found to make this work for whatever
                     * maybe use value2db and db2value on eachother or something?
                     */
                    $vars[$this->m_refKey][$this->m_ownerInstance->primaryKeyField()] = $this->m_ownerInstance->m_attribList[$this->m_ownerInstance->primaryKeyField()]->value2db($vars[$this->m_refKey]);
                } else {
                    $vars[$this->m_refKey] = $this->m_ownerInstance->m_attribList[$this->m_ownerInstance->primaryKeyField()]->value2db($record);
                }
            }
        } else {
            // Foreign key is in source node
            // After add, we must store the key value.
        }
        return $vars;
    }

    /**
     * Determine the type of the attribute on the other side.
     *
     * On the other side of a oneToOneRelation (in the destination node),
     * there may be a regular atkAttribute for the referential key, or an
     * atkOneToOneRelation pointing back at the source. This method discovers
     * which of the 2 cases we are dealing with.
     * @return boolean True if the attribute on the other side is a
     *                 relation, false if not.
     */
    function destinationHasRelation()
    {
        if ($this->createDestination()) {
            if (isset($this->m_refKey) && !empty($this->m_refKey)) {
                // foreign key is in the destination node.
                $attrib = $this->m_destInstance->m_attribList[$this->m_refKey];
            } else {
                // foreign key is in the source node. In this case, we must check the primary key
                // of the target node.
                $attrib = $this->m_destInstance->m_attribList[$this->m_destInstance->m_primaryKey[0]];
            }
            if (is_object($attrib) && strpos(get_class($attrib), "elation") !== false)
                return true;
        }
        return false;
    }

    /**
     * Returns a piece of html code for hiding this attribute in an HTML form,
     * while still posting its values. (<input type="hidden">)
     *
     * @param array $record The record that holds the value for this attribute
     * @param String $fieldprefix The fieldprefix to put in front of the name
     *                            of any html form element for this attribute.
     * @return String A piece of htmlcode with hidden form elements that post
     *                This attribute's value without showing it.
     */
    function hide($record = "", $fieldprefix = "")
    {
        Atk_Tools::atkdebug("hide called for " . $this->fieldName());
        if ($this->createDestination()) {
            if ($record[$this->fieldName()] != NULL) {
                $myrecord = $record[$this->fieldName()];

                if ($myrecord[$this->m_destInstance->primaryKeyField()] == NULL) {
                    // rec has no primkey yet, so we must add instead of update..
                    $mode = "add";
                } else {
                    $mode = "edit";
                    $myrecord["atkprimkey"] = $this->m_destInstance->primaryKey($myrecord);
                }
            } else {
                $mode = "add";
            }

            $output.='<input type="hidden" name="' . $fieldprefix . $this->fieldName() . '[mode]" value="' . $mode . '">';
            $forceList = Atk_Tools::decodeKeyValueSet($this->getFilter());
            $output.= $this->m_destInstance->hideform($mode, $myrecord, $forceList, $fieldprefix . $this->fieldName() . "_AE_");
            return $output;
        }
        return "";
    }

    /**
     * Adds the attribute's edit / hide HTML code to the edit array.
     *
     * This method is called by the node if it wants the data needed to create
     * an edit form. The method is an override of atkAttribute's method,
     * because in the atkOneToOneRelation, we need to implement the
     * AF_ONETOONE_INTEGRATE feature.
     *
     * This is a framework method, it should never be called directly.
     *
     * @param String $mode     		the edit mode ("add" or "edit")
     * @param array  $arr     		pointer to the edit array
     * @param array  $defaults 		pointer to the default values array
     * @param array  $error    		pointer to the error array
     * @param String $fieldprefix the fieldprefix
     */
    function addToEditArray($mode, &$arr, &$defaults, &$error, $fieldprefix)
    {
        /* hide */
        if (($mode == "edit" && $this->hasFlag(AF_HIDE_EDIT)) || ($mode == "add" && $this->hasFlag(AF_HIDE_ADD))) {
            /* when adding, there's nothing to hide... */
            if ($mode == "edit" || ($mode == "add" && !$this->isEmpty($defaults)))
                $arr["hide"][] = $this->hide($defaults, $fieldprefix, $mode);
        }

        /* edit */
        else {
            /* we first check if there is no edit override method, if there
             * is this method has the same behaviour as the atkAttribute's method
             */
            if (method_exists($this->m_ownerInstance, $this->m_name . "_edit") ||
                $this->edit($defaults, $fieldprefix, $mode) !== NULL) {
                Atk_Attribute::addToEditArray($mode, $arr, $defaults, $error, $fieldprefix);
            }

            /* how we handle 1:1 relations normally */ else {
                if (!$this->createDestination())
                    return;

                /* readonly */
                if ($this->m_destInstance->hasFlag(NF_READONLY) || ($mode == "edit" && $this->hasFlag(AF_READONLY_EDIT)) || ($mode == "add" && $this->hasFlag(AF_READONLY_ADD))) {
                    $this->createDestination();
                    $attrNames = $this->m_destInstance->getAttributeNames();
                    foreach ($attrNames as $attrName) {
                        $attr = &$this->m_destInstance->getAttribute($attrName);
                        $attr->addFlag(AF_READONLY);
                    }
                }

                /* we first check if the record doesn't already exist */
                if (isset($defaults[$this->fieldName()]) && !empty($defaults[$this->fieldName()])) {
                    /* record has no primarykey yet, so we must add instead of update */
                    $myrecord = $defaults[$this->fieldName()];

                    if (empty($myrecord[$this->m_destInstance->primaryKeyField()])) {
                        $mode = "add";
                    }
                    /* record exists! */ else {
                        $mode = "edit";
                        $myrecord["atkprimkey"] = $this->m_destInstance->primaryKey($myrecord);
                    }
                }

                /* record does not exist */ else {
                    $mode = "add";
                }

                /* mode */
                $arr["hide"][] = '<input type="hidden" name="' . $fieldprefix . $this->fieldName() . '[mode]" value="' . $mode . '">';

                /* add fields */

                $forceList = Atk_Tools::decodeKeyValueSet($this->m_destinationFilter);
                if ($this->m_refKey != "") {
                    if ($this->destinationHasRelation()) {
                        $forceList[$this->m_refKey][$this->m_ownerInstance->primaryKeyField()] = $defaults[$this->m_ownerInstance->primaryKeyField()];
                    } else {
                        // its possible that the destination has no relation back. In that case the refKey is just an attribute
                        $forceList[$this->m_refKey] = $defaults[$this->m_ownerInstance->primaryKeyField()];
                    }
                }

                $a = $this->m_destInstance->editArray($mode, $myrecord, $forceList, array(), $fieldprefix . $this->fieldName() . "_AE_", false, false);

                /* hidden fields */
                $arr["hide"] = array_merge($arr["hide"], $a["hide"]);

                /* editable fields, if AF_NOLABEL is specified or if there is just 1 field with the
                 * same name as the relation we don't display a label
                 * TODO FIXME
                 */
                if (!is_array($arr['fields']))
                    $arr['fields'] = array();
                if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) && !$this->hasFlag(AF_NOLABEL) && !(count($a["fields"]) == 1 && $a["fields"][0]["name"] == $this->m_name)) {
                    /* separator and name */
                    if ($arr['fields'][count($arr['fields']) - 1]['html'] !== '-')
                        $arr["fields"][] = array("html" => "-", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
                    $arr["fields"][] = array("line" => "<b>" . Atk_Tools::atktext($this->m_name, $this->m_ownerInstance->m_module, $this->m_ownerInstance->m_type) . "</b>", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
                }

                if (is_array($a["fields"])) {
                    // in non-integration mode we move all the fields to the one-to-one relations tabs/sections
                    if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) || $this->hasFlag(AF_ONETOONE_RESPECT_TABS)) {
                        foreach (array_keys($a['fields']) as $key) {
                            $a['fields'][$key]['tabs'] = $this->m_tabs;
                            $a['fields'][$key]['sections'] = $this->getSections();
                        }
                    }

                    $arr["fields"] = array_merge($arr["fields"], $a["fields"]);
                }

                if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) && !$this->hasFlag(AF_NOLABEL) && !(count($a["fields"]) == 1 && $a["fields"][0]["name"] == $this->m_name)) {
                    /* separator */
                    $arr["fields"][] = array("html" => "-", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
                }

                $fields = $arr['fields'];
                foreach ($fields as &$field) {
                    $field['attribute'] = '';
                }
            }
        }
    }

    /**
     * Adds the attribute's view / hide HTML code to the view array.
     *
     * This method is called by the node if it wants the data needed to create
     * a view form.
     *
     * This is a framework method, it should never be called directly.
     *
     * @param String $mode     the mode ("view")
     * @param array  $arr      pointer to the view array
     * @param array  $defaults pointer to the default values array
     */
    function addToViewArray($mode, &$arr, &$defaults)
    {
        if ($this->hasFlag(AF_HIDE_VIEW))
            return;

        /* we first check if there is no display override method, if there
         * is this method has the same behaviour as the atkAttribute's method
         */
        if (method_exists($this->m_ownerInstance, $this->m_name . "_display") ||
            $this->display($defaults, 'view') !== NULL) {
            Atk_Attribute::addToViewArray($mode, $arr, $defaults);
        }

        /* how we handle 1:1 relations normally */ else {
            if (!$this->createDestination())
                return;

            $record = $defaults[$this->fieldName()];
            $a = $this->m_destInstance->viewArray($mode, $record, false);

            /* editable fields, if AF_NOLABEL is specified or if there is just 1 field with the
             * same name as the relation we don't display a label
             * TODO FIXME
             */
            if (!is_array($arr['fields']))
                $arr['fields'] = array();
            if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) && !$this->hasFlag(AF_NOLABEL) && !(count($a["fields"]) == 1 && $a["fields"][0]["name"] == $this->m_name)) {
                /* separator and name */
                if ($arr['fields'][count($arr['fields']) - 1]['html'] !== '-')
                    $arr["fields"][] = array("html" => "-", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
                $arr["fields"][] = array("line" => "<b>" . Atk_Tools::atktext($this->m_name, $this->m_ownerInstance->m_module, $this->m_ownerInstance->m_type) . "</b>", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
            }

            if (is_array($a["fields"])) {
                if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) || $this->hasFlag(AF_ONETOONE_RESPECT_TABS)) {
                    foreach (array_keys($a['fields']) as $key) {

                        $a['fields'][$key]['tabs'] = $this->m_tabs;
                        $a['fields'][$key]['sections'] = $this->getSections();
                    }
                }
                $arr["fields"] = array_merge($arr["fields"], $a["fields"]);
            }

            if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) && !$this->hasFlag(AF_NOLABEL) && !(count($a["fields"]) == 1 && $a["fields"][0]["name"] == $this->m_name)) {
                /* separator */
                $arr["fields"][] = array("html" => "-", "tabs" => $this->m_tabs, 'sections' => $this->getSections());
            }
        }
    }

    /**
     * Check if a record has an empty value for this attribute.
     * @param array $record The record that holds this attribute's value.
     * @todo This method is not currently implemented properly and returns
     *       false in all cases.
     * @return boolean
     */
    function isEmpty($record)
    {
        return false;
    }

    /**
     * Checks if a value is valid.
     *
     * For the atkOneToOneRelation, this method delegates the actual
     * validation of values to the destination node.
     *
     * @param array $record The record that holds the value for this
     *                      attribute. If an error occurs, the error will
     *                      be stored in the 'atkerror' field of the record.
     * @param String $mode The mode for which should be validated ("add" or
     *                     "update")
     */
    function validate(&$record, $mode)
    {
        // zitten AF_ONETOONE_ERROR en AF_OBLIGATORY elkaar soms in de weg
        if ($this->hasFlag(AF_ONETOONE_ERROR) &&
            ($mode != "add" || !$this->hasFlag(AF_HIDE_ADD)) &&
            $this->createDestination()) {
            $this->m_destInstance->validate($record[$this->fieldName()], $mode, array($this->m_refKey));

            // only add 'atkerror' record when 1:1 relation contains error
            if (!isset($record[$this->fieldName()]["atkerror"])) {
                return;
            }

            foreach ($record[$this->fieldName()]["atkerror"] as $error) {
                $error['tab'] = $this->hasFlag(AF_ONETOONE_RESPECT_TABS) ? $this->m_tabs[0]
                        : $error['tab'];
                $record["atkerror"][] = $error;
            }
        }
    }

    /**
     * @deprecated Use getDestinationFilterCondition() instead.
     */
    function getFilter()
    {
        $filter = $this->m_destinationFilter;
        if (is_array($filter)) {
            $tmp_filter = "";
            for ($i = 0, $_i = count($filter); $i < $_i; $i++) {
                if ($tmp_filter != "")
                    $tmp_filter.=" AND ";
                $tmp_filter.=$filter[$i];
            }
            return $tmp_filter;
        }
        else {
            return $filter;
        }
    }

    /**
     * Get list of additional tabs.
     *
     * Attributes can add new tabs to tabbed screens. This method will be
     * called to retrieve the tabs. When AF_ONETOONE_INTEGRATE is set, the
     * atkOneToOneRelation adds tabs from the destination node to the tab
     * screen, so the attributes are seamlessly integrated but still on their
     * own tabs.
     *
     * @param String $action The action for which additional tabs should be
     *                       loaded.
     * @return array The list of tabs to add to the screen.
     */
    function getAdditionalTabs($action)
    {
        if ($this->hasFlag(AF_ONETOONE_INTEGRATE) && $this->createDestination()) {
            $detailtabs = $this->m_destInstance->getTabs($action);
            if (count($detailtabs) == 1 && $detailtabs[0] == "default") {
                // All elements in the relation are on the default tab. That means we should
                // inherit the tab from the onetoonerelation itself.
                return parent::getAdditionalTabs($action);
            }
            return $detailtabs;
        }
        return parent::getAdditionalTabs($action);
    }

    /**
     * Check if the attribute wants to be shown on a certain tab.
     *
     * @param String $tab The name of the tab to check.
     * @return boolean
     */
    function showOnTab($tab)
    {
        if ($this->hasFlag(AF_ONETOONE_INTEGRATE) && $this->createDestination()) {
            foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
                $p_attrib = &$this->m_destInstance->m_attribList[$attribname];
                if ($p_attrib->showOnTab($tab))
                    return true; // If we have one match, we can return true.
            }
            // None of the destionation attributes wants to be displayed on the tab.
            // If the entire onetoone itself is on that tab however, we should put all attribs on
            // this tab.
            return parent::showOnTab($tab);
        }
        return parent::showOnTab($tab);
    }

    /**
     * Adds the attribute / field to the list header. This includes the column name and search field.
     *
     * Framework method. It should not be necessary to call this method directly.
     *
     * @param String $action the action that is being performed on the node
     * @param array  $arr reference to the the recordlist array
     * @param String $fieldprefix the fieldprefix
     * @param int    $flags the recordlist flags
     * @param array  $atksearch the current ATK search list (if not empty)
     * @param String $atkorderby the current ATK orderby string (if not empty)
     * @see Atk_Node::listArray
     */
    function addToListArrayHeader($action, &$arr, $fieldprefix, $flags, $atksearch, $columnConfig, Atk_DataGrid $grid = null, $column = '*')
    {
        if ($this->hasFlag(AF_HIDE_LIST) || !$this->createDestination()) {
            return;
        }

        if ((!$this->hasFlag(AF_ONETOONE_INTEGRATE) && $column == '*') || $column == null) {
            // regular behaviour.
            parent::addToListArrayHeader($action, $arr, $fieldprefix, $flags, $atksearch, $columnConfig, $grid, $column);
            return;
        } else if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) || ($column != '*' && $this->getDestination()->getAttribute($column) == null)) {
            throw new Exception("Invalid list column {$column} for atkOneToOneRelation " . $this->getOwnerInstance()->atkNodeType() . '::' . $this->fieldName());
        }

        // integrated version, don't add ourselves, but add all columns from the destination.
        $prefix = $fieldprefix . $this->fieldName() . "_AE_";
        foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
            if ($column != '*' && $column != $attribname) {
                continue;
            }

            $p_attrib = &$this->m_destInstance->getAttribute($attribname);
            $p_attrib->addToListArrayHeader($action, $arr, $prefix, $flags, $atksearch[$this->fieldName()], $columnConfig, $grid, null);
        }
    }

    /**
     * Adds the attribute / field to the list row. And if the row is totalisable also to the total.
     *
     * Framework method. It should not be necessary to call this method directly.
     *
     * @param String $action the action that is being performed on the node
     * @param array  $arr reference to the the recordlist array
     * @param int    $nr the current row number
     * @param String $fieldprefix the fieldprefix
     * @param int    $flags the recordlist flags
     * @see Atk_Node::listArray
     */
    function addToListArrayRow($action, &$arr, $nr, $fieldprefix, $flags, $edit = false, Atk_DataGrid $grid = null, $column = '*')
    {
        if ($this->hasFlag(AF_HIDE_LIST) || !$this->createDestination()) {
            return;
        }

        if ((!$this->hasFlag(AF_ONETOONE_INTEGRATE) && $column == '*') || $column == null) {
            parent::addToListArrayRow($action, $arr, $nr, $fieldprefix, $flags, $edit, $grid, $column);
            return;
        } else if (!$this->hasFlag(AF_ONETOONE_INTEGRATE) || ($column != '*' && $this->getDestination()->getAttribute($column) == null)) {
            throw new Exception("Invalid list column {$column} for atkOneToOneRelation " . $this->getOwnerInstance()->atkNodeType() . '::' . $this->fieldName());
        }


        // integrated version, don't add ourselves, but add all columns from the destination
        // small trick, the destination record is in a subarray. The destination
        // addToListArrayRow will not expect this though, so we have to modify the
        // record a bit before passing it to the detail columns.
        $oldrecord = $arr["rows"][$nr]["record"];
        $arr["rows"][$nr]["record"] = $arr["rows"][$nr]["record"][$this->fieldName()];

        $prefix = $fieldprefix . $this->fieldName() . "_AE_";
        foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
            if ($column != '*' && $column != $attribname) {
                continue;
            }

            $p_attrib = &$this->m_destInstance->getAttribute($attribname);
            $p_attrib->addToListArrayRow($action, $arr, $nr, $prefix, $flags, $edit, $grid, null);
        }

        $arr["rows"][$nr]["record"] = $oldrecord;
    }

    /**
     * Creates a searchcondition for the field,
     * was once part of searchCondition, however,
     * searchcondition() also immediately adds the search condition.
     *
     * @param Atk_Query $query     The query object where the search condition should be placed on
     * @param String $table       The name of the table in which this attribute
     *                              is stored
     * @param mixed $value        The value the user has entered in the searchbox
     * @param String $searchmode  The searchmode to use. This can be any one
     *                              of the supported modes, as returned by this
     *                              attribute's getSearchModes() method.
     * @return String The searchcondition to use.
     */
    function getSearchCondition(&$query, $table, $value, $searchmode)
    {
        if ($this->createDestination() && is_array($value)) {
            // we are a relation, so instead of hooking ourselves into the
            // query, hook the attributes in the destination node onto the query
            foreach ($value as $key => $val) {
                // if we aren't searching for anything in this field, there is no need
                // to look any further:
                if ($val === "" || $val === null)
                    continue;

                $p_attrib = &$this->m_destInstance->m_attribList[$key];

                if (is_object($p_attrib)) {
                    if ($this->m_refKey && $this->createDestination()) {
                        // master mode
                        $new_table = &$this->fieldName();
                    } else {
                        // slave mode
                        $new_table = &$this->m_destInstance->m_table;

                        // we need to left join the destination table into the query
                        // (don't worry ATK won't add it when it's already there)
                        $query->addJoin($new_table, $new_table, ($this->getJoinCondition($query)), false);
                    }
                    $p_attrib->searchCondition($query, $new_table, $val, $this->getChildSearchMode($searchmode, $p_attrib->formName()));
                } else {
                    // attribute not found in destination, so it should
                    // be in the owner (this is the case when extra fields
                    // are in the relation
                    $p_attrib = &$this->m_ownerInstance->m_attribList[$key];
                    if (is_object($p_attrib)) {
                        $p_attrib->searchCondition($query, $p_attrib->getTable($key), $val, $this->getChildSearchMode($searchmode, $p_attrib->formName()));
                    } else
                        Atk_Tools::atkdebug("Field $key was not found in this relation (this is very weird)");
                }
            }
        }
        else {
            // we were passed a value that is not an array, so appearantly the function calling us
            // does not know we are a relation, not just another attrib
            // so we assume that it is looking for something in the descriptor def of the destination
            if ($this->createDestination()) {
                $descfields = $this->m_destInstance->descriptorFields();
                foreach ($descfields as $key) {
                    $p_attrib = &$this->m_destInstance->m_attribList[$key];
                    if (is_object($p_attrib)) {
                        if ($this->m_refKey && $this->createDestination()) {
                            // master mode
                            $new_table = &$this->fieldName();
                        } else {
                            // slave mode
                            $new_table = &$this->m_destInstance->m_table;

                            // we need to left join the destination table into the query
                            // (don't worry ATK won't add it when it's already there)
                            $query->addJoin($new_table, $new_table, ($this->getJoinCondition()), false);
                        }
                        $p_attrib->searchCondition($query, $new_table, $value, $searchmode);
                    }
                }
            }
        }
    }

    /**
     * Returns the condition which can be used when calling Atk_Query's addJoin() method
     * Joins the relation's owner with the destination
     * 
     * @param Atk_Query $query The query object
     * @param string $tablename The name of the table
     * @param string $fieldalias The field alias
     * @return  string  condition the condition that can be pasted into the query
     */
    function getJoinCondition(&$query, $tablename = "", $fieldalias = "")
    {
        $condition = $this->m_ownerInstance->m_table . "." . $this->fieldName();
        $condition .= "=";
        $condition .= $this->m_destInstance->m_table . "." . $this->m_destInstance->primaryKeyField();
        return $condition;
    }

    /**
     * Overridden method; in the integrated version, we should let the destination
     * attributes hook themselves into the fieldlist instead of hooking the relation
     * in it.
     * For original documentation for this method, please see the atkAttribute class
     * 
     * @param array   $fields            The array containing fields to use in the
     *                                   extended search
     * @param Atk_Node $node              The node where the field is in
     * @param array   $record            A record containing default values to put
     *                                   into the search fields.
     * @param array   $fieldprefix       search / mode field prefix
     * @param array   $currentSearchMode current search mode
     */
    function addToSearchformFields(&$fields, &$node, &$record, $fieldprefix = "", $currentSearchMode = array())
    {
        if (!is_array($currentSearchMode))
            $currentSearchMode = array();

        if ($this->hasFlag(AF_ONETOONE_INTEGRATE) && $this->createDestination()) {
            $prefix = $fieldprefix . $this->fieldName() . "_AE_";
            foreach (array_keys($this->m_destInstance->m_attribList) as $attribname) {
                $p_attrib = &$this->m_destInstance->m_attribList[$attribname];

                if (!$p_attrib->hasFlag(AF_HIDE_SEARCH)) {
                    $p_attrib->addToSearchformFields($fields, $node, $record[$this->fieldName()], $prefix, $currentSearchMode[$this->fieldName()]);
                }
            }
        } else {
            parent::addToSearchformFields($fields, $node, $record, $fieldprefix, $currentSearchMode);
        }
    }

    /**
     * Convert the internal value to the database value
     *
     * @param array $rec The record that holds the value for this attribute
     * @return mixed The database value
     */
    function value2db($rec)
    {
        if (is_array($rec) && isset($rec[$this->fieldName()])) {
            if (is_array($rec[$this->fieldName()]))
                return $this->escapeSQL($rec[$this->fieldName()][$this->m_destInstance->primaryKeyField()]);
            else
                return $rec[$this->fieldName()];
        }
        return NULL;
    }

}


