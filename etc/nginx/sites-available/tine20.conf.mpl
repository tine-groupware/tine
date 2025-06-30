server {
    include /etc/nginx/snippets/tine20-common.conf;

    listen 80;
    listen [::]:80;

    server_name {{ getenv "NGINXV_SERVER_NAME" "_"}};

    root {{ getenv "TINE20ROOT" "/usr/share"}}/tine20;

    error_log /dev/stderr;
    access_log /dev/stdout {{getenv "NGINXV_LOG_FORMAT" "tine20"}};

    set $PHP_ADMIN_VALUE "error_log = /var/log/nginx/php-error.log";
    set $PHP_VALUE "include_path={{getenv "NGINXV_TINE20_CONFIG_DIR" "/etc/tine20"}}:/usr/share/tine20";


    {{if not (eq (getenv "BROADCASTHUB_URL" "") "")}}
    location /broadcasthub {
        proxy_pass {{ getenv "BROADCASTHUB_URL" }};
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
    {{ end }}

    include /etc/nginx/snippets/tine20-locations.conf;
}
