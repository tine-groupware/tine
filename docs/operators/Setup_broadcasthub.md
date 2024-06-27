# Configure tine Broadcasthub

The tine Broadcasthub was built to deliver status messages about files and containers from the tine Server to the tine Client in the browser. One use case would be to mark files in the tine file manager that are currently opened in the tine OnlyOffice integration by other users. But in general the Broadcasthub just pipes through any message it receives, it is up to the tine Server what to send.

The tine Server publishes messages to a Redis channel. The tine Broadcasthub listens to this channel. Furthermore the tine Broadcasthub is a websocket server. tine clients in the browsers can connect to the tine Broadcasthub as websocket clients. When the tine Broadcasthub receives a message from the Redis channel, then it sends this message to all connected websocket clients.

In order to publish status messages about files and containers the tine Server could send messages in a JSON format for example, eventually with fields for record ID, container ID, model name and HTTP verb (create, update, delete) that was used at last on the resource. For the tine Broadcasthub it does not matter, what string is published to the Redis channel, it just sends all string messages received from the Redis channel to the corresponding tine clients.

The tine Broadcasthub is a NodeJS application.

Fetch it from https://hub.docker.com/r/tinegroupware/broadcasthub (operation via Docker is recommended)

see https://github.com/tine-groupware/broadcasthub/blob/master/TINEDOCS.md for more information and the setup howto…

# Check if Broadcasthub is working

- go into browser debug console
- switch to network
- activate "WS" (websockets)
- it should show the connection (IP/Hostname)
- selecting the connection should show the messages, for example record updates:

      {verb: "update", model: "Addressbook_Model_Contact",…}
