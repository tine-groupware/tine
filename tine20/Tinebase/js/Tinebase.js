/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// safari < 15.4
require('broadcastchannel-polyfill');

// @TODO should be imported/required in twing
// use https://github.com/Richienb/node-polyfill-webpack-plugin ?
// window.process = require('process/browser');
// window.Buffer = require('buffer').Buffer;

// message bus
var postal = require('postal');
require('postal.federation');
require('script-loader!store2');
require('script-loader!store2/src/store.bind.js');
require('postal.xwindow');
require('postal.request-response');

// custom ext build
require('../../library/ExtJS/ext-custom');

(typeof window !== "undefined" ? window : global)['Tine'] =
    (typeof window !== "undefined" ? window : global)['Tine'] || {}

/* pkg: Tinebase FAT Client (js/Tinebase-FAT.js)*/
require("./extFixes.js");
require("./touch/checkboxGridSelection.js");
require("./ux/Portal.js");
require("./ux/PortalColumn.js");
require("./ux/Portlet.js");
require("./extInit.js");
require("./ux/util/store.compat.js");
require("./../../library/addressparser.js/addressparser.js");
require("./widgets/MapPanel.js");
require("./widgets/ContentTypeTreePanel.js");
require("./widgets/form/RecordPickerManager.js");
require("./widgets/dialog/AddToRecordPanel.js");
require("./widgets/dialog/ExceptionHandlerDialog.js");
require("./ux/util/Cookie.js");
require("./ux/util/urlCoder.js");
require("./ux/FieldLabeler.js");
require("./ux/String.js");
require("./ux/Array.js");
require("./ux/Date.js");
require("./ux/ConnectionStatus.js");
require("./ux/Direct/JsonRpcProvider.js");
require("./ux/DatePickerWeekPlugin.js");
require("./ux/ButtonLockedToggle.js");
require("./ux/Percentage.js");
require("./ApplicationStarter.js");
require("./ux/PopupWindow.js");
require("./ux/PopupWindowManager.js");
require("./ux/Notification.js");
require("./ux/WindowFactory.js");
require("./ux/SliderTip.js");
require("./ux/Wizard.js");
require("./ux/SearchField.js");
require("./ux/BrowseButton.js");
require("./widgets/grid/ColumnManager.js");
require("./ux/grid/CheckColumn.js");
require("./ux/grid/ActionColumnPlugin.js");
require("./ux/grid/QuickaddGridPanel.js");
require("./ux/grid/RowExpander.js");
require("./ux/grid/PagingToolbar.js");
require("./ux/grid/GridDropZone.js");
require("./ux/grid/GridViewMenuPlugin.js");
require("./ux/grid/MultiCellSelectionModel.js");
require("./ux/file/UploadManager.js");
require("./ux/file/Upload.js");
require("./ux/file/BrowsePlugin.js");
require("./ux/file/Download.js");
require("./ux/form/ColorField.js");
require("./ux/form/IconTextField.js");
require("./ux/form/FieldMaximizePlugin.js");
require("./ux/form/MirrorTextField.js");
require("./ux/form/ColumnFormPanel.js");
require("./ux/form/NumberField.js");
require("./ux/form/PeriodPicker.js");
require("./ux/form/MoneyField.js");
require("./widgets/form/DiscountField.js");
require("./ux/form/BytesField.js");
require("./ux/form/LayerCombo.js");
require("./ux/form/ClearableComboBox.js");
require("./ux/form/ClearableTextField.js");
require("./widgets/form/VMultiPicker/index.js");
require("./ux/form/DateTimeField.js");
require("./ux/form/BooleanCombo.js");
require("./ux/form/ClearableDateField.js");
require("./ux/form/ImageField.js");
require("./ux/form/ImageCropper.js");
require("./ux/form/Spinner.js");
require("./ux/form/SpinnerStrategy.js");
require("./ux/form/DurationSpinner.js");
require("./ux/form/LockCombo.js");
require("./ux/form/LockTextfield.js");
require("./ux/form/HtmlEditor.js");
require("./ux/form/ComboBoxRecentsPlugin.js");
require("./ux/layout/HorizontalFitLayout.js");
require("./ux/layout/CenterLayout.js");
require("./ux/layout/RowLayout.js");
require("./ux/layout/cardLayoutHelper.js");
require("./ux/layout/MultiAccordionLayout.js");
require("./ux/GMapPanel.js");
require("./ux/tree/CheckboxSelectionModel.js");
require("./ux/tree/TreeGridSorter.js");
require("./ux/tree/TreeGridColumnResizer.js");
require("./ux/tree/TreeGridNodeUI.js");
require("./ux/tree/TreeGridLoader.js");
require("./ux/tree/TreeGridColumns.js");
require("./ux/tree/TreeGrid.js");
require("./ux/display/DisplayPanel.js");
require("./ux/display/DisplayField.js");
require("./ux/display/DisplayTextArea.js");
require("./ux/layout/Display.js");
require("./ux/MessageBox.js");
require("./ux/TabPanelSortPlugin.js");
require("./ux/pluginRegistry.js");
require("./ux/ItemRegistry.js");
require("./ux/Function.deferByTickets.js");
require("./ux/Function.createBuffered.js");
require("./ux/Printer/Printer.js");
require("./ux/Printer/renderers/Base.js");
require("./ux/Printer/renderers/GridPanel.js");
require("./ux/Printer/renderers/ColumnTree.js");
require("./ux/Printer/renderers/Field.js");
require("./ux/Printer/renderers/Check.js");
require("./ux/Printer/renderers/Combo.js");
require("./ux/Printer/renderers/Container.js");
require("./ux/Printer/renderers/Panel.js");
require("./ux/Printer/renderers/Tags.js");
require("./ux/Printer/renderers/EditDialogRenderer.js");
require("./data/TitleRendererManager.js");
Tine.Tinebase.data.Record = require("./data/Record.js").default;
Tine.Tinebase.data.RecordMgr = require("./data/RecordMgr.js");
require("./data/RecordStore.js");
require("./data/GroupedStoreCollection.js");
require("./data/RecordProxy.js");
require("./data/AbstractBackend.js");
require("./data/Clipboard.js");
require("./RecentsManager.js");
require("./StateProvider.js");
require("./AppManager.js");
require("./ExceptionHandler.js");
require("./ExceptionHandlerRegistry.js");
require("./ExceptionDialog.js");
require("./Container.js");
require("./EncodingHelper.js");
Tine.Tinebase.common = require("./common.js");
require("./configManager.js");
require("./widgets/grid/RendererManager.js");
require("./Models.js");
require("./Application.js");
require("./widgets/keyfield/Store.js");
require("./widgets/keyfield/ComboBox.js");
require("./widgets/keyfield/Renderer.js");
require("./widgets/LangChooser.js");
require("./widgets/ActionManager.js");
require("./widgets/ActionUpdater.js");
require("./widgets/EditRecord.js");
require("./widgets/VersionCheck.js");
require("./widgets/dialog/AlarmPanel.js");
require("./widgets/dialog/Dialog.js");
require("./widgets/dialog/EditDialog.js");
require("./widgets/dialog/SimpleRecordEditDialog.js");
require("./widgets/dialog/MultipleEditDialogPlugin.js");
require("./widgets/dialog/AddRelationsEditDialogPlugin.js");
require("./widgets/dialog/MultipleEditResultSummary.js");
require("./widgets/display/RecordDisplayPanel.js");
require("./widgets/display/DefaultDisplayPanel.js");
require("./ux/TabPanelKeyPlugin.js");
require("./widgets/dialog/TokenModeEditDialogPlugin.js");
require("./widgets/dialog/WizardPanel.js");
require("./widgets/dialog/AdminPanel.js");
require("./widgets/dialog/CredentialsDialog.js");
require("./widgets/dialog/PreferencesDialog.js");
require("./widgets/dialog/PreferencesTreePanel.js");
require("./widgets/dialog/PreferencesPanel.js");
require("./widgets/dialog/ImportDialog.js");
require("./widgets/dialog/SimpleImportDialog.js");
require("./widgets/dialog/ExportDialog.js");
require("./widgets/dialog/vue/ModalDialog/index.js");
require("./widgets/dialog/vue/MultiOptionsDialog/index.js");
require("./widgets/dialog/vue/ModalDialog/index.js");
require("./widgets/dialog/FileListDialog.js");
require("./widgets/dialog/DuplicateMergeDialog.js");
require("./widgets/dialog/DuplicateResolveGridPanel.js");
require("./widgets/grid/DetailsPanel.js");
require("./widgets/grid/FilterModel.js");
require("./widgets/grid/MonthFilter.js");
require("./widgets/grid/FilterPlugin.js");
require("./widgets/grid/FilterButton.js");
require("./widgets/grid/ExportButton.js");
require("./widgets/exportAction.js");
require("./widgets/importAction.js");
require("./widgets/grid/FilterToolbar.js");
require("./widgets/grid/FilterStructureTreePanel.js");
require("./widgets/grid/FilterPanel.js");
require("./widgets/grid/PickerFilter.js");
require("./widgets/grid/FilterToolbarQuickFilterPlugin.js");
require("./widgets/grid/FilterSelectionModel.js");
require("./widgets/grid/ForeignRecordFilter.js");
require("./ux/grid/GroupingGridPlugin.js");
require("./widgets/grid/OwnRecordFilter.js");
require("./widgets/grid/QuickaddGridPanel.js");
require("./widgets/grid/FileUploadGrid.js");
require("./widgets/dialog/AttachmentsGridPanel.js");
require("./widgets/grid/PickerGridPanel.js");
require("./widgets/form/FieldManager.js");
require("./widgets/grid/MappingPickerGridPanel.js");
require("./widgets/grid/LinkGridPanel.js");
require("./widgets/grid/FilterModelMultiSelect.js");
require("./widgets/grid/QuotaRenderer.js");
require("./widgets/relation/Manager.js");
require("./widgets/relation/GridRenderer.js");
require("./widgets/relation/MenuItemManager.js");
require("./widgets/relation/GenericPickerGridPanel.js");
require("./widgets/relation/PickerGridPanel.js");
require("./widgets/printer/RecordRenderer.js");
require("./widgets/grid/GridPanel.js");
require("./widgets/grid/BbarGridPanel.js");
require("./widgets/keyfield/Filter.js");
require("./widgets/keyfield/ConfigGrid.js");
require("./widgets/keyfield/ConfigField.js");
require("./widgets/tree/Loader.js");
require("./widgets/tree/ContextMenu.js");
require("./widgets/tree/FilterPlugin.js");
require("./widgets/customfields/ConfigManager.js");
require("./widgets/customfields/Field.js");
require("./widgets/customfields/FilterModel.js");
require("./widgets/customfields/Renderer.js");
require("./widgets/customfields/CustomfieldSearchCombo.js");
require("./widgets/customfields/EditDialogPlugin.js");
require("./widgets/customfields/CustomfieldsCombo.js");
require("./widgets/relation/FilterModel.js");
require("./widgets/relation/Renderer.js");
require("./widgets/account/PickerGridPanel.js");
require("./widgets/account/ChangeAccountAction.js");
require("./widgets/container/SelectionComboBox.js");
require("./widgets/container/SelectionDialog.js");
require("./widgets/container/CalDAVContainerPropertiesHookField.js");
require("./widgets/container/GrantsGrid.js");
require("./widgets/container/GrantsDialog.js");
require("./widgets/container/TreePanel.js");
require("./widgets/container/PropertiesDialog.js");
require("./widgets/container/FilterModel.js");
require("./widgets/tags/TagsPanel.js");
require("./widgets/tags/TagCombo.js");
require("./widgets/tags/TagToggleBox.js");
require("./widgets/tags/TagFilter.js");
require("./widgets/tags/TagsMassAttachAction.js");
require("./widgets/tags/TagsMassDetachAction.js");
require("./widgets/mainscreen/WestPanel.js");
require("./widgets/MainScreen.js");
require("./LicenseScreen.js");
require("./CreditsScreen.js");
require("./widgets/CountryCombo.js");
require("./widgets/ActivitiesPanel.js");
require("./widgets/ActivitiesGridPanel.js");
require("./widgets/form/RecordPickerComboBox.js");
require("./widgets/relation/PickerCombo.js");
require("./widgets/grid/PickerGridLayerCombo.js");
require("./widgets/form/ConfigPanel.js");
require("./widgets/form/AutoCompleteField.js");
require("./widgets/form/FileUploadButton.js");
require("./widgets/form/UidTriggerField.js");
require("./widgets/form/PasswordTriggerField.js");
require("./widgets/file/LocationTypePluginFactory.js");
require("./widgets/file/SelectionField.js");
require("./widgets/file/MountpointPicker.js");
require("./widgets/file/mountpointRenderer.js");
require("./widgets/dialog/vue/PasswordDialog/index.js");
require("./widgets/dialog/SecondFactorDialog.js");
require("./widgets/form/RecordForm.js");
require("./widgets/persistentfilter/Model.js");
require("./widgets/persistentfilter/Store.js");
require("./widgets/persistentfilter/PickerPanel.js");
require("./widgets/persistentfilter/EditPersistentFilterPanel.js");
require("./widgets/path/renderer.js");
require("./widgets/dialog/vue/PasswordChangeDialog/index.js");
require("./AboutDialog.js");
require("./AppPile.js");
require("./TineBar/index.js");
require("./TineDock/index.js");
require("./AppTabsPanel.js");
require("./MainContextMenu.js");
require("./MainMenu.js");
require("./MainScreenPanel.js");
require("./LoginPanel.js");
require("./AdminPanel.js");
require("./UserProfilePanel.js");
require("./prototypeTranslations.js");
require("./CanonicalPath.js");
require("./PresenceObserver.js");
require("./PasswordGenerator.js");
require("./RangeSliderComponent.js");
require("./widgets/form/FileSelectionArea.js");
require("./BL/BLConfigPanel.js");
/* pkg: Tinebase FAT Client (css/Tinebase-FAT.css)*/
require("../css/ExtFixes.css");
require("../css/Tinebase.less");
require("../css/mimetypes.css");
require("../css/SmallForms.css");
require("../css/ux/ArrowCollapse.css");
require("../css/ux/SubFormPanel.css");
require("../css/ux/ConnectionStatus.css");
require("../css/ux/Wizard.css");
require("../css/ux/Percentage.css");
require("../css/ux/DatePickerWeekPlugin.css");
require("../css/ux/grid/QuickaddGridPanel.css");
require("../css/ux/grid/IconTextField.css");
require("../css/ux/grid/GridDropZone.css");
require("../css/ux/grid/ActionColumnPlugin.css");
require("../css/ux/grid/GridViewMenuPlugin.css");
require("../css/ux/form/ExpandFieldSet.css");
require("../css/ux/form/ImageField.css");
require("../css/ux/form/Spinner.css");
require("../css/ux/form/HtmlEditor.css");
require("../css/ux/form/LayerCombo.css");
require("../css/ux/display/DisplayPanel.css");
require("../css/ux/layout/CenterLayout.css");
require("../css/ux/tree/treegrid.css");
require("../css/ux/LockCombo.css");
require("../css/ux/LockTextField.css");
require("../css/ux/Menu.css");
require("../css/ux/MessageBox.css");
require("../css/widgets/EditRecord.css");
require("../css/widgets/TagsPanel.css");
require("../css/widgets/FilterToolbar.css");
require("../css/widgets/AccountPicker.css");
require("../css/widgets/PreviewPanel.css");
require("../css/widgets/PreferencesPanel.css");
require("../css/widgets/UidTriggerField.css");
require("../css/widgets/FileSelectionArea.css");
require("../css/widgets/PasswordTriggerField.css");
require("../css/widgets/print.css");

require('./MunicipalityKey/model');
require('./MunicipalityKey/explainer');
require('./MunicipalityKey/picker');
require('./MunicipalityKey/grid');
require('./MunicipalityKey/editDialog');

require('./Model/ImportExportDefinition');

require('./widgets/CountryFilter');
require('./widgets/SiteFilter');
require('./widgets/CurrencyCombo');

require('./widgets/dialog/ResetPasswordDialog');

require('Exception/HTMLReportDialog');

// UI style >= 2019
require('node-waves');
require('node-waves/src/less/waves.less');
require('../css/flat.less');
require('../css/darkmode.less');

// other libs
var lodash = require('lodash');
var director = require('director');
const vue = require('vue');
const mitt = require('mitt')

// custom bootstrap styles
require('../css/bootstrap-vue/custom_vue_styles.scss')

require('./ux/util/screenshot');
require('./ux/file/UploadManagerUI');
require ('./UploadmanagerStatusButton');
require ('BankHoliday/FractionField');

module.exports = {
    director: director,
    postal: postal,
    lodash: lodash,
    _: lodash,
    vue: vue,
    mitt: mitt
};
