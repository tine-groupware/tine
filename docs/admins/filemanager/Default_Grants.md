Tinebase Filesystem Default Grants
=

This is the example config of setting default grants for filesystem nodes.

Template

~~~php
'Filemanager/folders/personal/([^/]+)/[^/]+' => [
    [
        'account_id' => ‘$1’,
        'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
        'addGrant' => true,
    ]
]
'Filemanager/folders/shared/[^/]' => [
    [
        'account_id' => [
            ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_USER_GROUP]
        ],
        'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
        'addGrant' => true,
    ],
    [
        'account_id' => [
            ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_ADMIN_GROUP]
        ],
         'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
         'addGrant' => true,
    ],
]
'Filemanager/folders/shared/Aktenplan/([^/]+)/[^/]' => [
    [
        'account_id' => [
            ['field' => 'name ', 'operator' => 'equals', 'value' => ‘<Gruppe>$1’]
        ],
        'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
        'addGrant' => true,
    ],
]
~~~
