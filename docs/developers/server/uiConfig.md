# tine PHP ui_config

Tab property
------

For example, when we want a new tab in the EditDialog instead of a new field in the same tab, we added like this in the ui_config (class VoluntaryServices_Model_VoluntaryTask):

~~~ php
self::FLD_APPLICANTS         => [
                self::LABEL                 => 'Applicants', // _('Applicants')
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG                => [
                    self::APP_NAME              => VoluntaryServices_Config::APP_NAME,
                    self::MODEL_NAME            => VoluntaryServices_Model_Applicant::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS     => true,
                    self::REF_ID_FIELD          => VoluntaryServices_Model_Applicant::FLD_TASK,
                ],
                self::UI_CONFIG             => [
                    'tab'                       => 'Applicants',
                ]
            ],
~~~