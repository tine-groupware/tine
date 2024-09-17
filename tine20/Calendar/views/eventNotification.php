<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
?>
<?php if ($this && count((array)$this->updates) > 0): ?>
<?php echo $this->translate->_('Updates from') . ' ' . $this->updater->accountDisplayName ?>:
<?php 
foreach ($this->updates as $field => $update) {
    if ($field != 'attendee') {
        $i18nFieldName = Calendar_Model_Event::getTranslatedFieldName($field, $this->translate);
        $i18nOldValue  = Calendar_Model_Event::getTranslatedValue($field, $update, $this->translate, $this->timezone);
        $i18nCurrValue = Calendar_Model_Event::getTranslatedValue($field, $this->event->$field, $this->translate, $this->timezone);
        
        if($field == 'rrule' && $this->event->recurid) {
            echo $this->translate->_("This is an event series exception.")  . "\n" ;
            continue;
        }
        
        echo sprintf($this->translate->_('%1$s changed from "%2$s" to "%3$s"'), $i18nFieldName, $i18nOldValue, $i18nCurrValue) . "\n";
    }
}

if ((isset($this->updates['attendee']) || array_key_exists('attendee', $this->updates))) {
    if ((isset($this->updates['attendee']['toCreate']) || array_key_exists('toCreate', $this->updates['attendee']))) {
        foreach ($this->updates['attendee']['toCreate'] as $attender) {
            echo sprintf($this->translate->_('%1$s has been invited'), $attender->getName()) . "\n";
        }
    }
    if ((isset($this->updates['attendee']['toDelete']) || array_key_exists('toDelete', $this->updates['attendee']))) {
        foreach ($this->updates['attendee']['toDelete'] as $attender) {
            echo sprintf($this->translate->_('%1$s has been removed'), $attender->getName()) . "\n";
        }
    }
    if ((isset($this->updates['attendee']['toUpdate']) || array_key_exists('toUpdate', $this->updates['attendee']))) {
        foreach ($this->updates['attendee']['toUpdate'] as $attender) {
            switch ($attender->status) {
                case Calendar_Model_Attender::STATUS_ACCEPTED:
                    echo sprintf($this->translate->_('%1$s accepted invitation'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_DECLINED:
                    echo sprintf($this->translate->_('%1$s declined invitation'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_TENTATIVE:
                    echo sprintf($this->translate->_('Tentative response from %1$s'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_NEEDSACTION:
                    echo sprintf($this->translate->_('No response from %1$s'), $attender->getName()) . "\n";
                    break;
                default:
                    echo sprintf($this->translate->_('"%2$s" response from %1$s'), $attender->getName(), $attender->status) . "\n";
                    break;
            }
        }
    }
}
?>

<?php endif;?>
<?php if ($this->attendeeAccountId): ?>

<?php echo $this->translate->_('Link') ?>: <?php echo $this->event->getDeepLink();?>


<?php endif;?>
<?php echo $this->translate->_('Event details') ?>:
<?php
$orderedFields = array('dtstart', 'dtend', 'summary', 'url', 'location', 'description', 'rrule');

foreach($orderedFields as $field) {
    if ($this->event->$field) {
        echo str_pad(Calendar_Model_Event::getTranslatedFieldName($field, $this->translate) . ':', 20) . 
             Calendar_Model_Event::getTranslatedValue($field, $this->event->$field, $this->translate, $this->timezone) . "\n";
    }
}

echo $this->translate->plural('Attender', 'Attendee', count($this->event->attendee)). ":\n";
        
foreach ($this->event->attendee as $attender) {
    $role = $this->translate->_($attender->getRoleString());
    $status = $this->translate->_($attender->getStatusString());
    
    echo "    {$attender->getName()} ($role, $status) \n";
}
?>

