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
	'Domain/ProcedureQuestions.php',
	'Domain/ProcedureInput.php',
	'Domain/ApplicationInput.php',
	'Centre/CentreCatalogue.php',
	'Centre/CentreCatalogueSync.php',
	'Domain/CentreCatalog.php',
	'Access/CentreScope.php',
	'Access/ProcedureAccess.php',
	'Meta/ProcedureMetaRegistration.php',
	'Meta/ApplicationMetaRegistration.php',
	'PostType/ProcedurePostType.php',
	'PostType/ApplicationPostType.php',
	'Taxonomy/ProcedureTaxonomies.php',
	'PublicFront/Assets.php',
	'PublicFront/ExitSignal.php',
	'PublicFront/Shell.php',
	'PublicFront/EditLock.php',
	'PublicFront/View/PanelParts.php',
	'PublicFront/Applications.php',
	'PublicFront/ApplicationFiles.php',
	'PublicFront/View/HomeView.php',
	'PublicFront/Home.php',
	'PublicFront/View/ProcedureChrome.php',
	'PublicFront/ProcedureView.php',
	'PublicFront/View/WorkspaceView.php',
	'PublicFront/Workspace.php',
	'PublicFront/View/ProcedureEditorView.php',
	'PublicFront/ProcedureEditor.php',
	'PublicFront/View/ApplyFormView.php',
	'PublicFront/ApplyForm.php',
	'PublicFront/View/MyCentreView.php',
	'PublicFront/MyCentre.php',
	'Admin/ProcedureAdmin.php',
	'Admin/Settings.php',
	'App.php',
);
