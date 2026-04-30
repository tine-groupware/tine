# Configure {{ branding.title }} Broadcasthub

The {{ branding.title }} Broadcasthub was built to deliver status messages about files and containers from the {{ branding.title }} Server to the {{ branding.title }} Client in the browser. One use case would be to mark files in the {{ branding.title }} file manager that are currently opened in the {{ branding.title }} OnlyOffice integration by other users. But in general the Broadcasthub just pipes through any message it receives, it is up to the {{ branding.title }} Server what to send.

The {{ branding.title }} Server publishes messages to a Redis channel. The {{ branding.title }} Broadcasthub listens to this channel. Furthermore the {{ branding.title }} Broadcasthub is a websocket server. {{ branding.title }} clients in the browsers can connect to the {{ branding.title }} Broadcasthub as websocket clients. When the {{ branding.title }} Broadcasthub receives a message from the Redis channel, then it sends this message to all connected websocket clients.

In order to publish status messages about files and containers the {{ branding.title }} Server could send messages in a JSON format for example, eventually with fields for record ID, container ID, model name and HTTP verb (create, update, delete) that was used at last on the resource. For the {{ branding.title }} Broadcasthub it does not matter, what string is published to the Redis channel, it just sends all string messages received from the Redis channel to the corresponding {{ branding.title }} clients.

The {{ branding.title }} Broadcasthub is a NodeJS application.

Fetch it from https://hub.docker.com/r/tinegroupware/broadcasthub (operation via Docker is recommended)

see https://github.com/tine-groupware/broadcasthub/blob/master/TINEDOCS.md for more information and the setup howto…

# Check if Broadcasthub is working

- go into browser debug console
- switch to network
- activate "WS" (websockets)
- it should show the connection (IP/Hostname)
- selecting the connection should show the messages, for example record updates:

      {verb: "update", model: "Addressbook_Model_Contact",…}
