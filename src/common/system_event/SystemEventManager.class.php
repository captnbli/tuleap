<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/dao/SystemEventDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/event/EventManager.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_USER_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_USER_DELETE.class.php');


/**
* Manager of system events
*
* Base class to manage system events
*/
class SystemEventManager {
    
    var $dao;


    // Constructor
    function SystemEventManager() {
        $this->_getDao();

        $event_manager =& $this->_getEventManager();
        $event_manager->addListener('register_project_creation',$this, 'addSystemEvent', true, 0);
        $event_manager->addListener('project_is_deleted',$this, 'addSystemEvent', true, 0);
        $event_manager->addListener('project_admin_add_user',$this, 'addSystemEvent', true, 0);
        $event_manager->addListener('project_admin_remove_user',$this, 'addSystemEvent', true, 0);
        $event_manager->addListener('project_admin_activate_user',$this, 'addSystemEvent', true, 0);
        $event_manager->addListener('project_admin_delete_user',$this, 'addSystemEvent', true, 0);
    }

    function &_getEventManager() {
        return EventManager::instance();
    }

    function _getDao() {
        if (!$this->dao) {
            $this->dao = new SystemEventDao(CodexDataAccess::instance());
        }
        return  $this->dao;
    }

    /*
     * Convert selected event into a system event, and store it accordingly
     */
    function addSystemEvent($event, $params) {
        //$event = constant(strtoupper($event));
        $param_separator="::";
        switch ($event) {
        case 'register_project_creation':
            $sysevent = new SystemEvent(SystemEvent::PROJECT_CREATE,$params['group_id'],2);
            $this->dao->store($sysevent);
            break;
        case 'project_is_deleted':
            $sysevent = new SystemEvent(SystemEvent::PROJECT_DELETE,$params['group_id'],3);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_add_user':
            $sysevent = new SystemEvent(SystemEvent::MEMBERSHIP_CREATE,$params['group_id'].$param_separator.$params['user_id'],2);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_remove_user':
            $sysevent = new SystemEvent(SystemEvent::MEMBERSHIP_DELETE,$params['group_id'].$param_separator.$params['user_id'],2);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_activate_user':
            $sysevent = new SystemEvent(SystemEvent::USER_CREATE,$params['user_id'],2);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_delete_user':
            $sysevent = new SystemEvent(SystemEvent::USER_DELETE,$params['user_id'],3);
            $this->dao->store($sysevent);
            break;
        default:
            break;
        }
    }


    /**
     * Process stored events. Should this be moved to a new class?
     */
    function processEvents() {
        while (($dar=$this->dao->checkOutNextEvent()) != null) {
            if ($row = $dar->getRow()) {
                switch ($row['type']) {
                case 'PROJECT_CREATE':
                    $sysevent = new SystemEvent_PROJECT_CREATE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                case 'PROJECT_DELETE':
                    $sysevent = new SystemEvent_PROJECT_DELETE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                case 'MEMBERSHIP_CREATE':
                    $sysevent = new SystemEvent_MEMBERSHIP_CREATE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                case 'MEMBERSHIP_DELETE':
                    $sysevent = new SystemEvent_MEMBERSHIP_DELETE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                case 'USER_CREATE':
                    $sysevent = new SystemEvent_USER_CREATE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                case 'USER_DELETE':
                    $sysevent = new SystemEvent_USER_DELETE($row['id'],$row['parameters'],$row['priority'],$row['status']);
                    break;
                default:              
                     $sysevent = null;
                   break;
                }

                // Process $sysevent
                if (isset($sysevent) && ($sysevent)) {
                    if ($sysevent->process()) {
                        $this->dao->close($sysevent);
                    }
                }
            }
        }
    }


}

?>
