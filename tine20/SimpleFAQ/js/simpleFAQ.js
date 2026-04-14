/*
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../styles/SimpleFAQ.scss';
import './Application';
import './MainScreen';
import './FaqTreePanel';
import './FaqFilterPanel';
import './Model';
import './FaqGridDetailsPanel';
import './FaqGridPanel';
import './FaqEditDialog';
import './AdminPanel';
import './FaqStatus';
import './FaqStatusFilterModel';
import './FaqType';
import './FaqTypeFilterModel';
import './SearchCombo';

/**
 * @namespace Tine.SimpleFAQ
 * @class Tine.SimpleFAQ.faqBackend
 * @extends Tine.Tinebase.data.RecordProxy
 *
 * Faq Backend
 */
Tine.SimpleFAQ.faqBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'SimpleFAQ',
    modelName: 'Faq',
    recordClass: Tine.SimpleFAQ.Model.Faq
});

/**
 * @namespace Tine.SimpleFAQ
 * @class Tine.SimpleFAQ.settingsBackend
 * @extends Tine.Tinebase.data.RecordProxy
 *
 * Settings Backend
 */
Tine.SimpleFAQ.settingsBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'SimpleFAQ' ,
    modelName: 'Settings',
    recordClass: Tine.SimpleFAQ.Model.Settings
});
