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

namespace Tuleap\Svn\Repository;

use \Tuleap\Svn\Dao;
use \Tuleap\Svn\Repository\Repository;
use Project;
use \Tuleap\Svn\Repository\CannotCreateRepositoryException;
use \Tuleap\Svn\Repository\CannotFindRepositoryException;
use ProjectManager;

class RepositoryManager {

    private $dao;
    private $project_manager;

    public function __construct(Dao $dao, ProjectManager $project_manager) {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    public function getRepositoriesInProject(Project $project) {
        $repositories = array();
        foreach ($this->dao->searchByProject($project) as $row) {
            $repositories[] = $this->instantiateFromRow($row, $project);
        }

        return $repositories;
    }

    public function getRepositoryByName(Project $project, $name) {
        $row = $this->dao->searchRepositoryByName($project, $name);
        if ($row) {
            return $this->instantiateFromRow($row, $project);
        } else {
            throw new CannotFindRepositoryException ($GLOBALS['Language']->getText('plugin_svn','find_error'));
        }
    }

    public function getById($id_repository, Project $project) {
        $row = $this->dao->searchByRepositoryIdAndProjectId($id_repository, $project);
        return $this->instantiateFromRow($row, $project);
    }

    public function create(Repository $repositorysvn) {
        if (! $this->dao->create($repositorysvn)) {
            throw new CannotCreateRepositoryException ($GLOBALS['Language']->getText('plugin_svn','update_error'));
        }
    }

    public function instantiateFromRow(array $row, Project $project) {
        return new Repository(
            $row['id'],
            $row['name'],
            $project
        );
    }
}
