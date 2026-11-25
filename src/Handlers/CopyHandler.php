<?php

namespace Sintattica\Atk\Handlers;

use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Session\SessionManager;

/**
 * Handler for the 'tcopy' action of a node. It copies the selected
 * record, and then redirects back to the calling page.
 *
 * @author Ivo Jansch <ivo@achievo.org>
 */
class CopyHandler extends ActionHandler
{
    /**
     * The action handler.
     */
    public function action_copy()
    {
        // copying changes data: the request must carry the session's CSRF token
        if (!$this->requestHasValidCSRFToken()) {
            $this->renderAccessDeniedPage();

            return;
        }

        $this->invoke('nodeCopy');
    }

    /**
     * Copies a record, based on parameters passed in the url.
     */
    public function nodeCopy()
    {
        Tools::atkdebug('CopyHandler::nodeCopy()');
        $recordset = $this->m_node->select($this->m_postvars['atkselector'])->mode('copy')->getAllRows();
        $db = $this->m_node->getDb();
        if (Tools::count($recordset) > 0) {
            // allowed to copy record?
            if (!$this->allowed($recordset[0])) {
                $this->renderAccessDeniedPage();

                return;
            }

            if (!$this->m_node->copyDb($recordset[0])) {
                Tools::atkdebug('node::action_copy() -> Error');
                $db->rollback();
                $location = $this->m_node->feedbackUrl('save', self::ACTION_FAILED, $recordset[0], $db->getErrorMsg());
                Tools::atkdebug('node::action_copy() -> Redirect');
                $this->m_node->redirect($location);
            } else {
                $db->commit();
                $this->notify('copy', $recordset[0]);
                $this->clearCache();
            }
        }

        // Without a previous level on the session stack, going "back" leads to
        // this same action again: the record was copied once more on every
        // redirect until the browser gave up. Go to the list instead.
        $sm = SessionManager::getInstance();
        if ($sm->atkLevel() < 1) {
            $this->m_node->redirect($sm->sessionUrl(Tools::dispatch_url($this->m_node->atkNodeUri(), 'admin'), SessionManager::SESSION_NEW));

            return;
        }

        $this->m_node->redirect();
    }
}
