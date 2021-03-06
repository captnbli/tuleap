<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'constants.php';

use \Tuleap\Svn\SvnRouter;
use \Tuleap\Svn\Repository\RepositoryManager;
use \Tuleap\Svn\Dao;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;

/**
 * SVN plugin
 */
class SvnPlugin extends Plugin {
    const SERVICE_SHORTNAME = 'plugin_svn';

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::GET_SVN_LIST_REPOSITORIES_SQL_FRAGMENTS);
        $this->addHook('cssfile');
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function getTypes() {
        return array(
            SystemEvent_SVN_CREATE_REPOSITORY::NAME
        );
    }

    public function get_svn_list_repositories_sql_fragments(array $params) {
        $dao = new Dao();
        $params['sql_fragments'][] = $dao->getListRepositoriesSqlFragment();
    }

    public function system_event_get_types_for_default_queue($params) {
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_CREATE_REPOSITORY::NAME;
    }

    public function get_system_event_class($params) {
        switch($params['type']) {
            case 'SVN_CREATE_REPOSITORY' :
                include_once dirname(__FILE__).'/events/SystemEvent_SVN_CREATE_REPOSITORY.class.php';
                $params['class'] = 'SystemEvent_SVN_CREATE_REPOSITORY';
                $params['dependencies'] = array(
                    Backend::instance(Backend::SVN)
                );
                break;
        }
    }

    private function getRouter() {
        return new SvnRouter(
              new RepositoryManager(new Dao(), ProjectManager::instance()),
              ProjectManager::instance()
        );
    }

    public function process(HTTPRequest $request) {
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $request->getProject()->getId())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_manage_repository','plugin_not_activated'));
            $GLOBALS['Response']->redirect('/projects/'.$request->getProject()->getUnixNameMixedCase().'/');
        } else {
            $this->getRouter()->route($request);
        }
    }

    public function cssFile($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="/viewvc-static/styles.css" />';
        }
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e804';
    }

    public function service_classnames(array $params) {
        $params['classnames'][$this->getServiceShortname()] = 'Tuleap\Svn\ServiceSvn';
    }
}