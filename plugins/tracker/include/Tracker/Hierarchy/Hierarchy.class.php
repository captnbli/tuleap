<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'NotInHierarchyException.class.php';
require_once 'CyclicHierarchyException.class.php';

class Tracker_Hierarchy {
    
    private $parents = array();
    
    /**
     * @param int $parent The id of the parent in the relatonship
     * @param int $child  The id of the parent in the relatonship
     */
    public function addRelationship($parent, $child) {
        if (!array_key_exists($parent, $this->parents)) {
            $this->parents[$parent] = null;
        }
        $this->parents[$child] = $parent;
    }
    
    /**
     * @throws Tracker_Hierarchy_NotInHierarchyException
     * @throws Tracker_Hierarchy_CyclicHierarchyException
     *
     * @return int the level of the tracker accordingly to the hierarchy
     */
    public function getLevel($tracker_id) {
        $callstack = array();
        return $this->getLevelRecursive($tracker_id, $callstack);
    }
    
    private function getLevelRecursive($tracker_id, array &$callstack) {
        if (array_key_exists($tracker_id, $this->parents)) {
            return $this->computeLevel($this->parents[$tracker_id], $callstack);
        } else {
            throw new Tracker_Hierarchy_NotInHierarchyException();
        }
    }
    
    private function computeLevel($tracker_id, array &$callstack) {
        if (is_null($tracker_id)) {
            return 0;
        }
        $this->assertHierarchyIsNotCyclic($tracker_id, $callstack);
        return $this->getLevelRecursive($tracker_id, $callstack) + 1;
    }
    
    private function assertHierarchyIsNotCyclic($tracker_id, array &$callstack) {
        if (in_array($tracker_id, $callstack)) {
            throw new Tracker_Hierarchy_CyclicHierarchyException();
        }
        $callstack[] = $tracker_id;
    }
}
?>
