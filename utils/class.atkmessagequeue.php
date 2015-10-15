<?php
/**
 * This file is part of the ATK distribution on GitHub.
 * Detailed copyright and licensing information can be found
 * in the doc/COPYRIGHT and doc/LICENSE files which should be
 * included in the distribution.
 *
 * @package atk
 * @subpackage utils
 *
 * @copyright (c)2006 Ibuildings.nl BV
 * @license http://www.achievo.org/atk/licensing ATK Open Source License
 *
 * @version $Revision: 6320 $
 * $Id$
 */
/**
 * Message queue flags.
 */
define('AMQ_GENERAL', 0);
define('AMQ_SUCCESS', 1);
define('AMQ_WARNING', 2);
define('AMQ_FAILURE', 3);

/**
 * This class implements the ATK message queue for showing messages
 * at the top of a page.
 *
 * @author Patrick van der Velden <patrick@ibuildings.nl>
 * @package atk
 * @subpackage utils
 *
 */
class Atk_MessageQueue
{

    /**
     * Retrieve the atkMessageQueue instance
     *
     * @return atkMessageQueue The instance.
     */
    function &getInstance()
    {
        static $s_instance = NULL;
        if ($s_instance == NULL) {
            global $g_sessionManager;
            if (is_object($g_sessionManager)) { // don't bother to create if session has not yet been initialised
                $s_instance = new Atk_MessageQueue();
            }
        }
        return $s_instance;
    }

    /**
     * Constructor
     */
    function __construct()
    {
        
    }

    /**
     * Add message to queue
     *
     * @static
     * @param string $txt
     * @param int $type 
     * @return boolean Success
     */
    function addMessage($txt, $type = AMQ_GENERAL)
    {
        $instance = Atk_MessageQueue::getInstance();
        if (is_object($instance)) {
            return $instance->_addMessage($txt, $type);
        }
        return false;
    }

    /**
     * Get the name of the message type
     *
     * @param int $type The message type
     * @return string The name of the message type
     */
    function _getTypeName($type)
    {
        if ($type == AMQ_SUCCESS)
            return 'success';
        else if ($type == AMQ_FAILURE)
            return 'failure';
        else if ($type == AMQ_WARNING)
            return 'warning';
        else
            return 'general';
    }

    /**
     * Add message to queue (private)
     *
     * @param string $txt
     * @param int $type
     * @return boolean Success
     */
    function _addMessage($txt, $type)
    {
        $q = &$this->getQueue();
        $q[] = array('message' => $txt, 'type' => $this->_getTypeName($type));
        return true;
    }

    /**
     * Get first message from queue and remove it
     *
     * @static
     * @return string message
     */
    function getMessage()
    {
        $instance = Atk_MessageQueue::getInstance();
        if (is_object($instance)) {
            return $instance->_getMessage();
        }
        return "";
    }

    /**
     * Get first message from queue and remove it (private)
     *
     * @return string message
     */
    function _getMessage()
    {
        $q = &$this->getQueue();
        return array_shift($q);
    }

    /**
     * Get all messages from queue and empty the queue
     *
     * @return array messages
     */
    function getMessages()
    {
        $instance = Atk_MessageQueue::getInstance();
        if (is_object($instance)) {
            return $instance->_getMessages();
        }
        return array();
    }

    /**
     * Get all messages from queue and empty the queue (private)
     *
     * @return array messages
     */
    function _getMessages()
    {
        $q = &$this->getQueue();
        $queue_copy = $q;
        $q = array();
        return $queue_copy;
    }

    /**
     * Get the queue
     *
     * @return array The message queue
     */
    function &getQueue()
    {
        $sessionmgr = Atk_SessionManager::atkGetSessionManager();
        $session = &$sessionmgr->getSession();
        if (!isset($session['atkmessagequeue'])) {
            $session['atkmessagequeue'] = array();
        }
        return $session['atkmessagequeue'];
    }

}


