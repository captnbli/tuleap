<?php
/**
 * Copyright (c) Enalean, 2014, 2015. All Rights Reserved.
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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Action_CopyArtifactTest extends TuleapTestCase {

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Action_CopyArtifact */
    private $action;

    /** @var Codendi_Request */
    private $request;

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $xml_exporter;

    /** @var int */
    private $changeset_id = 101;

    /** @var Tracker_Artifact */
    private $from_artifact;

    /** @var Tracker_Artifact */
    private $new_artifact;

    /** @var int */
    private $artifact_id = 123;

    /** @var int */
    private $new_artifact_id = 456;

    /** @var Tracker_IDisplayTrackerLayout */
    private $layout;

    /** @var Tracker_Artifact_XMLImport */
    private $xml_importer;

    /** @var Tracker_XML_Updater_ChangesetXMLUpdater */
    private $xml_updater;

    /** @var array */
    private $submitted_values;

    /** @var Tracker_Artifact_Changeset */
    private $from_changeset;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $file_updater;

    /** @var Tracker_XML_Exporter_ChildrenXMLExporter */
    private $children_xml_exporter;

    /** @var Tracker_XML_Importer_ChildrenXMLImporter */
    private $children_xml_importer;

    /** @var Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifacts_imported_mapping;

    /** @var Tracker_XML_Importer_CopyArtifactInformationsAggregator */
    private $logger;

    /** @var Tracker_Artifact */
    private $a_mocked_artifact;

    public function setUp() {
        parent::setUp();

        $changeset_factory    = mock('Tracker_Artifact_ChangesetFactory');
        $this->tracker        = aMockTracker()->withId(1)->build();
        $this->new_artifact   = partial_mock('Tracker_Artifact', array('createNewChangeset'));
        $this->new_artifact->setId($this->new_artifact_id);
        $this->layout         = mock('Tracker_IDisplayTrackerLayout');
        $this->user           = mock('PFUser');
        $this->xml_exporter   = mock('Tracker_XML_Exporter_ArtifactXMLExporter');
        $this->xml_importer   = mock('Tracker_Artifact_XMLImport');
        $this->xml_updater    = mock('Tracker_XML_Updater_ChangesetXMLUpdater');
        $this->file_updater   = mock('Tracker_XML_Updater_TemporaryFileXMLUpdater');
        $this->from_changeset = stub('Tracker_Artifact_Changeset')->getId()->returns($this->changeset_id);
        $this->from_artifact  = partial_mock('Tracker_Artifact', array('getChangesetFactory'));
        $this->from_artifact->setId($this->artifact_id);
        $this->from_artifact->setTracker($this->tracker);
        $this->from_artifact->setChangesets(array($this->changeset_id => $this->from_changeset));
        stub($this->from_artifact)->getChangesetFactory()->returns($changeset_factory);
        stub($this->from_changeset)->getArtifact()->returns($this->from_artifact);
        $this->children_xml_exporter       = mock('Tracker_XML_Exporter_ChildrenXMLExporter');
        $this->children_xml_importer       = mock('Tracker_XML_Importer_ChildrenXMLImporter');
        $this->artifacts_imported_mapping  = mock('Tracker_XML_Importer_ArtifactImportedMapping');

        $backend_logger = mock("BackendLogger");
        $this->logger   = new Tracker_XML_Importer_CopyArtifactInformationsAggregator($backend_logger);

        $this->submitted_values = array();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        stub($this->artifact_factory)
            ->getArtifactByIdUserCanView($this->user, $this->artifact_id)
            ->returns($this->from_artifact);

        $this->request = aRequest()
            ->with('from_artifact_id',  $this->artifact_id)
            ->with('from_changeset_id', $this->changeset_id)
            ->with('artifact',          $this->submitted_values)
            ->build();

        $this->action = new Tracker_Action_CopyArtifact(
            $this->tracker,
            $this->artifact_factory,
            $this->xml_exporter,
            $this->xml_importer,
            $this->xml_updater,
            $this->file_updater,
            $this->children_xml_exporter,
            $this->children_xml_importer,
            $this->artifacts_imported_mapping,
            $this->logger
        );

        $this->a_mocked_artifact = mock("Tracker_Artifact");
    }

    public function itExportsTheRequiredSnapshotArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_exporter)->exportSnapshotWithoutComments('*', $this->from_changeset)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itImportTheXMLArtifactWithEmptyExtractionPath() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_importer)->importOneArtifactFromXML($this->tracker, '*', '', '*')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheNewArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->new_artifact);

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?aid=456')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNothingAndRedirectsToTheTrackerIfCannotSubmit() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(false);

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheTrackerIfXMLImportFailed() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns(null);

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itUpdatesTheXMLWithIncomingValues() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_updater)->update(
            $this->tracker,
            '*',
            $this->submitted_values,
            $this->user,
            $_SERVER['REQUEST_TIME']
        )->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRecursivelyExportsChildrenOfTheCurrentArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->children_xml_exporter)->exportChildren()->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itImportsChildren() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->new_artifact);

        expect($this->children_xml_importer)->importChildren('*', '*', '*', $this->new_artifact, $this->user)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itStacksTheMappingBetweenOldAndNewArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->new_artifact);

        expect($this->artifacts_imported_mapping)->add($this->artifact_id, $this->new_artifact_id)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itUpdateTheXMLToNotMoveTheOriginalAttachementButToDoACopyInstead() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->file_updater)->update()->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoArtifactIdInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_changeset_id', $this->changeset_id)
            ->with('artifact',          $this->submitted_values)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoChangesetIdInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id', $this->artifact_id)
            ->with('artifact',         $this->submitted_values)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoSubmittedValuesInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id',  $this->artifact_id)
            ->with('from_changeset_id', $this->changeset_id)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfArtifactDoesNotBelongToTracker() {
        $another_tracker = aTracker()->withId(111)->build();
        $artifact_in_another_tracker = anArtifact()->withTracker($another_tracker)->build();
        stub($this->artifact_factory)
            ->getArtifactByIdUserCanView($this->user, 666)
            ->returns($artifact_in_another_tracker);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id', 666)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itCreatesACommentChangesetContainingAllTheErrorRaisedDuringTheMigrationProcess() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->a_mocked_artifact);

        expect($this->a_mocked_artifact)->createNewChangeset(array(), '*', $this->user, '*', Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }
}
