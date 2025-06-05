How to Customize GDOR Template
----

1. find the templates directory from `GDPR_Config::TEMPLATE_PATH`, the default dir should be `GDPR/views/`
2. to add the translated templates, please copy all the default templates from `GDPR/views/` to `GDPR/views/{language}/`
3. customize the localized templates from `GDPR/views/{language}/`

----

***Example Template:***

```
{% block subject %}
    {{ _('Confirm the subscription for ') }} {{ localizeString(dipr.name, app.user.locale) }}
{% endblock %}

{% block body %}
    {% set localized_dip_name = localizeString(dipr.name, app.user.locale)  | default('') %}
    <div>{{ _('Dear ' )}}  {{ contact.getTitle() | default('user') }} </div>
    <div>{{ _('Please click this link to finalize the subscription for ') }} {{  localizeString(dipr.name, app.user.locale) }} : <a href="{{ link }}">Subscribe</a></div>
    {% if localized_dip_name != '' and localized_dip_name == 'Newsletter' %}
        <div>{{ _('We sill send our latest news to you !' )}}</div>
    {% endif %}
    <div>{{ _('Thank you.') }}</div>
    <div>{{ _('Your %1$s Team') | format(app.branding.title) }}</div>
    <div><img src="{{ app.branding.logoContent.getBody() | data_uri }}" width="135" height="50" /></div>
    {#    <div><img alt="{{ app.branding.title }}" src="https://metaways.tine20.net/logo" width="135" height="50" /></div>#}
{% endblock %}
```

Available parameters
----
- `link` (registration link / manage consent page link / email page link)
- `dipr` (data intended purpose record)
  - localized name : `localizeString(dipr.name, app.user.locale)`
  - localized description : `localizeString(dipr.description, app.user.locale)`
- `contact` (contact record)
- global parameters

***The global parameters can be used in twig template:***

```
'app' = [
    'websiteUrl'        => $tbConfig->{Tinebase_Config::WEBSITE_URL},
    'branding'          => [
        'logo'              => Tinebase_Core::getInstallLogo(),
        'logoContent'       => Tinebase_Controller::getInstance()->getLogo(),
        'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
        'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
        'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
    ],
    'user'              => [
        'locale'            => Tinebase_Core::getLocale(),
        'timezone'          => Tinebase_Core::getUserTimezone(),
    ],
    'currencySymbol'    => $tbConfig->{Tinebase_Config::CURRENCY_SYMBOL},
];
```

***Example of displaying custom content for specific data intended purpose:***
~~~
    {% if localized_dip_name != '' and localized_dip_name == 'Newsletter' %}
        <div>{{ "add custom text here" )}}</div>
    {% endif %}
~~~




