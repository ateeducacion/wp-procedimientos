<?php
/**
 * Ordered load list for the modular PRC procedures application.
 *
 * Single source of truth: `bootstrap.php` requires these files for tests and
 * the dev environment, and `build/pack-snippet.php` inlines them in this same
 * order into the Code Snippets bundle. A new file under `src/Prc/` must be
 * added here or the bundler fails.
 *
 * Paths are relative to this directory. Order matters: a class must come after
 * everything it extends or uses at load time, and `App.php` goes last. The
 * first entry has to start with its `namespace` statement: the bundler injects
 * the PRC_BUNDLE_LOADED guard right after it.
 *
 * @package Prc
 */

return array(
	'Meta/ProcedureMetaKeys.php',
	'Meta/ApplicationMetaKeys.php',
	'Domain/DateRange.php',
	'Domain/ProcedureState.php',
	'Domain/QuestionPoints.php',
	'Domain/QuestionGrid.php',
	'Domain/ProcedureQuestions.php',
	'Domain/QuestionChanges.php',
	'Domain/ProcedureInput.php',
	'Domain/ApplicationInput.php',
	'Domain/PeopleSpec.php',
	'Domain/ProcedureCopy.php',
	'Domain/ApplicationPeople.php',
	'Domain/ApplicationFilter.php',
	'Domain/ApplicationCompleteness.php',
	'Domain/CentreEligibility.php',
	'Centre/CentreCatalogue.php',
	'Centre/CentreCatalogueSync.php',
	'Domain/CentreCatalog.php',
	'Access/CentreScope.php',
	'Access/ObserverScope.php',
	'Access/ProcedureAccess.php',
	'Access/ApplicationActions.php',
	'Meta/ProcedureMetaRegistration.php',
	'Meta/ApplicationMetaRegistration.php',
	'Meta/MetaSwap.php',
	'PostType/ProcedurePostType.php',
	'PostType/ApplicationPostType.php',
	'Taxonomy/ProcedureTaxonomies.php',
	'PublicFront/Assets.php',
	'PublicFront/ExitSignal.php',
	'PublicFront/Shell.php',
	'PublicFront/EditLock.php',
	'PublicFront/View/PanelParts.php',
	'PublicFront/CentreColumns.php',
	'PublicFront/Applications.php',
	'PublicFront/ApplicationFiles.php',
	'PublicFront/ApplicationLog.php',
	'PublicFront/ApplicationOperations.php',
	'PublicFront/ApplicationHistory.php',
	'PublicFront/People.php',
	'PublicFront/PeopleExport.php',
	'PublicFront/ApplicationExport.php',
	'PublicFront/ApplicationQuery.php',
	'PublicFront/PeopleSpecPanel.php',
	'PublicFront/ApplicationsPanel.php',
	'PublicFront/ApplicationActionsPanel.php',
	'PublicFront/View/HomeView.php',
	'PublicFront/Home.php',
	'PublicFront/HelpContent.php',
	'PublicFront/View/HelpView.php',
	'PublicFront/Help.php',
	'PublicFront/View/ProcedureChrome.php',
	'PublicFront/ProcedureView.php',
	'PublicFront/View/WorkspaceView.php',
	'PublicFront/Workspace.php',
	'PublicFront/View/ExportDialogView.php',
	'PublicFront/View/ApplicationActionsView.php',
	'PublicFront/View/ApplicationsPanelView.php',
	'PublicFront/View/ProcedureEditorView.php',
	'PublicFront/ProcedureEditor.php',
	'PublicFront/ProcedureDuplicate.php',
	'PublicFront/View/MeasuredQuestionView.php',
	'PublicFront/View/PeopleView.php',
	'PublicFront/View/ApplyFormView.php',
	'PublicFront/ApplyForm.php',
	'PublicFront/WithdrawForm.php',
	'PublicFront/PeopleForm.php',
	'PublicFront/View/MyCentreView.php',
	'PublicFront/MyCentre.php',
	'PublicFront/ObserveSettings.php',
	'PublicFront/View/ObserveView.php',
	'PublicFront/Observe.php',
	'Notifications/Messages.php',
	'Notifications/Mailer.php',
	'Admin/ProcedureAdmin.php',
	'Admin/Settings.php',
	'App.php',
);
