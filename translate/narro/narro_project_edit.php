<?php
    /**
     * Narro is an application that allows online software translation and maintenance.
     * Copyright (C) 2008 Alexandru Szasz <alexxed@gmail.com>
     * http://code.google.com/p/narro/
     *
     * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
     * License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any
     * later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
     * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
     * more details.
     *
     * You should have received a copy of the GNU General Public License along with this program; if not, write to the
     * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
     */

    require_once('includes/prepend.inc.php');

    class NarroProjectEditForm extends QForm {
            // General Form Variables
        protected $objNarroProject;
        protected $strTitleVerb;
        protected $blnEditMode;

        // Controls for NarroProject's Data Fields
        protected $lblProjectId;
        protected $txtProjectName;
        protected $lstProjectTypeObject;
        protected $chkActive;

        // Other ListBoxes (if applicable) via Unique ReverseReferences and ManyToMany References

        // Button Actions
        protected $btnSave;
        protected $btnCancel;
        protected $btnDelete;

        protected function SetupNarroProject() {
            // Lookup Object PK information from Query String (if applicable)
            // Set mode to Edit or New depending on what's found
            $intProjectId = NarroApp::QueryString('p');
            if (($intProjectId)) {
                $this->objNarroProject = NarroProject::Load(($intProjectId));

                if (!$this->objNarroProject)
                    throw new Exception('Could not find a NarroProject object with PK arguments: ' . $intProjectId);

                $this->strTitleVerb = NarroApp::Translate('Edit');
                $this->blnEditMode = true;
            } else {
                $this->objNarroProject = new NarroProject();
                $this->strTitleVerb = NarroApp::Translate('Create');
                $this->blnEditMode = false;
            }
        }

        protected function Form_Create() {
            parent::Form_Create();

            // Call SetupNarroProject to either Load/Edit Existing or Create New
            $this->SetupNarroProject();

            // Create/Setup Controls for NarroProject's Data Fields
            $this->lblProjectId_Create();
            $this->txtProjectName_Create();
            $this->lstProjectTypeObject_Create();
            $this->chkActive_Create();

            // Create/Setup ListBoxes (if applicable) via Unique ReverseReferences and ManyToMany References

            // Create/Setup Button Action controls
            $this->btnSave_Create();
            $this->btnCancel_Create();
            $this->btnDelete_Create();

            if ($this->objNarroProject->ProjectId) {
                $this->pnlBreadcrumb->strSeparator = ' | ';

                $this->pnlBreadcrumb->setElements(
                    NarroLink::ProjectTextList($this->objNarroProject->ProjectId, 1, 1, '', $this->objNarroProject->ProjectName),
                    NarroLink::ProjectFileList($this->objNarroProject->ProjectId, null, t('Files'))
                );

                if (NarroApp::HasPermissionForThisLang('Can manage project', $this->objNarroProject->ProjectId))
                    $this->pnlBreadcrumb->addElement(NarroLink::ProjectManage($this->objNarroProject->ProjectId, t('Manage')));

                $this->pnlBreadcrumb->addElement(t('Edit'));

                $this->pnlBreadcrumb->addElement(NarroLink::ProjectLanguages($this->objNarroProject->ProjectId, t('Languages')));
            }
            else {
                $this->pnlBreadcrumb->DisplayStyle = QDisplayStyle::None;
            }
        }

        // Protected Create Methods
        // Create and Setup lblProjectId
        protected function lblProjectId_Create() {
            $this->lblProjectId = new QLabel($this);
            $this->lblProjectId->Name = NarroApp::Translate('Project Id');
            if ($this->blnEditMode)
                $this->lblProjectId->Text = $this->objNarroProject->ProjectId;
            else
                $this->lblProjectId->Text = 'N/A';
        }

        // Create and Setup txtProjectName
        protected function txtProjectName_Create() {
            $this->txtProjectName = new QTextBox($this);
            $this->txtProjectName->Name = NarroApp::Translate('Project Name');
            $this->txtProjectName->Text = $this->objNarroProject->ProjectName;
            $this->txtProjectName->Required = true;
            $this->txtProjectName->MaxLength = NarroProject::ProjectNameMaxLength;
        }

        // Create and Setup lstProjectTypeObject
        protected function lstProjectTypeObject_Create() {
            $this->lstProjectTypeObject = new QListBox($this);
            $this->lstProjectTypeObject->Name = NarroApp::Translate('Project Type Object');
            $this->lstProjectTypeObject->Required = true;
            foreach (NarroProjectType::$NameArray as $intId => $strValue)
                $this->lstProjectTypeObject->AddItem(new QListItem($strValue, $intId, $this->objNarroProject->ProjectType == $intId));
        }

        // Create and Setup chkActive
        protected function chkActive_Create() {
            $this->chkActive = new QCheckBox($this);
            $this->chkActive->Name = NarroApp::Translate('Active');
            $this->chkActive->Checked = $this->objNarroProject->Active;
        }


        // Setup btnSave
        protected function btnSave_Create() {
            $this->btnSave = new QButton($this);
            $this->btnSave->Text = NarroApp::Translate('Save');
            $this->btnSave->AddAction(new QClickEvent(), new QServerAction('btnSave_Click'));
            $this->btnSave->PrimaryButton = true;
            $this->btnSave->CausesValidation = true;
            if ($this->blnEditMode)
                $this->btnSave->Visible = NarroApp::HasPermissionForThisLang('Can edit project', $this->objNarroProject->ProjectId);
            else
                $this->btnSave->Visible = NarroApp::HasPermissionForThisLang('Can add project', null);
        }

        // Setup btnCancel
        protected function btnCancel_Create() {
            $this->btnCancel = new QButton($this);
            $this->btnCancel->Text = NarroApp::Translate('Cancel');
            $this->btnCancel->AddAction(new QClickEvent(), new QServerAction('btnCancel_Click'));
            $this->btnCancel->CausesValidation = false;
        }

        // Setup btnDelete
        protected function btnDelete_Create() {
            $this->btnDelete = new QButton($this);
            $this->btnDelete->Text = NarroApp::Translate('Delete');
            $this->btnDelete->AddAction(new QClickEvent(), new QConfirmAction(sprintf(NarroApp::Translate('Are you SURE you want to DELETE the project "%s"?\nThis operation might take a while and will delete:\n - user permissions related to this project\n - project contexts and associated comments\n - votes for contexts belonging to this project\n - project\'s files.\nTexts and suggestions are not deleted.'), $this->objNarroProject->ProjectName)));
            $this->btnDelete->AddAction(new QClickEvent(), new QServerAction('btnDelete_Click'));
            $this->btnDelete->CausesValidation = false;

            if (!$this->blnEditMode)
                $this->btnDelete->Visible = false;

            $this->btnDelete->Visible = NarroApp::HasPermissionForThisLang('Can delete project', $this->objNarroProject->ProjectId);
        }

        // Protected Update Methods
        protected function UpdateNarroProjectFields() {
            $this->objNarroProject->ProjectName = $this->txtProjectName->Text;
            $this->objNarroProject->ProjectType = $this->lstProjectTypeObject->SelectedValue;
            $this->objNarroProject->Active = ($this->chkActive->Checked)?1:0;
        }


        // Control ServerActions
        protected function btnSave_Click($strFormId, $strControlId, $strParameter) {
            if ($this->blnEditMode && !NarroApp::HasPermissionForThisLang('Can edit project', $this->objNarroProject->ProjectId))
                NarroApp::Redirect(NarroLink::ProjectList());

            if (!$this->blnEditMode && !NarroApp::HasPermissionForThisLang('Can add project', null))
                NarroApp::Redirect(NarroLink::ProjectList());

            $this->UpdateNarroProjectFields();
            $this->objNarroProject->Save();

            if (!file_exists(__DOCROOT__ . __SUBDIRECTORY__ . __IMPORT_PATH__ . '/' . $this->objNarroProject->ProjectId . '/' . NarroApp::$Language->LanguageCode)) {
                mkdir(__DOCROOT__ . __SUBDIRECTORY__ . __IMPORT_PATH__ . '/' . $this->objNarroProject->ProjectId . '/' . NarroApp::$Language->LanguageCode, 0777, true);
                NarroUtils::RecursiveChmod(__DOCROOT__ . __SUBDIRECTORY__ . __IMPORT_PATH__ . '/' . $this->objNarroProject->ProjectId . '/' . NarroApp::$Language->LanguageCode);
            }

            NarroApp::Redirect(sprintf('narro_project_manage.php?p=%d', $this->objNarroProject->ProjectId));
        }

        protected function btnCancel_Click($strFormId, $strControlId, $strParameter) {
            NarroApp::Redirect(NarroLink::ProjectList());
        }

        protected function btnDelete_Click($strFormId, $strControlId, $strParameter) {
            if (!NarroApp::HasPermissionForThisLang('Can delete project', $this->objNarroProject->ProjectId))
                NarroApp::Redirect(NarroLink::ProjectList());

            $objDatabase = NarroApp::$Database[1];
/*
            $strQuery = sprintf("DELETE FROM `narro_user_permission` WHERE project_id = %d", $this->objNarroProject->ProjectId);
            try {
                $objDatabase->NonQuery($strQuery);
            }catch (Exception $objEx) {
                throw new Exception(sprintf(t('Error while executing sql query in file %s, line %d: %s'), __FILE__, __LINE__ - 4, $objEx->getMessage()));
            }
*/
            $strQuery = sprintf("DELETE FROM `narro_context` WHERE project_id = %d", $this->objNarroProject->ProjectId);
            try {
                $objDatabase->NonQuery($strQuery);
            }catch (Exception $objEx) {
                throw new Exception(sprintf(t('Error while executing sql query in file %s, line %d: %s'), __FILE__, __LINE__ - 4, $objEx->getMessage()));
            }

            $objDatabase->NonQuery('SET FOREIGN_KEY_CHECKS=0');
            $strQuery = sprintf("DELETE FROM `narro_file` WHERE project_id = %d", $this->objNarroProject->ProjectId);
            try {
                $objDatabase->NonQuery($strQuery);
            }catch (Exception $objEx) {
                throw new Exception(sprintf(t('Error while executing sql query in file %s, line %d: %s'), __FILE__, __LINE__ - 4, $objEx->getMessage()));
            }

            if (file_exists(__DOCROOT__ . __SUBDIRECTORY__ . __IMPORT_PATH__ . '/' . $this->objNarroProject->ProjectId))
                NarroUtils::RecursiveDelete(__DOCROOT__ . __SUBDIRECTORY__ . __IMPORT_PATH__ . '/' . $this->objNarroProject->ProjectId);

            $this->objNarroProject->Delete();

            NarroApp::Redirect(NarroLink::ProjectList());
        }

    }

    NarroProjectEditForm::Run('NarroProjectEditForm', 'templates/narro_project_edit.tpl.php');
?>