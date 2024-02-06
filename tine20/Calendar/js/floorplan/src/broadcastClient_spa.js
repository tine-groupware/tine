/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const constrainedRand = (min, max) => {
    return Math.max(max, Math.round(Math.random() * max) + min);
};

const wait = async(timeout) => {
    return new Promise((resolve) => {
        window.setTimeout(resolve, timeout);
    });
}

const randWait = async (min = 0, max = 2000) => {
    return wait(constrainedRand(min, max));
};

let running = false;

const init = async (wsUrl, jsonRPC, cb) => {

    const d = await jsonRPC("Tinebase.getAuthToken", [['broadcasthub'], 100])
    const authToken = d.result.auth_token
    const socket = new WebSocket(wsUrl);
    let authResponse = null;
    const jsonApiUrl = window.location.protocol + '//' + window.location.host + '/' + 'index.php'

    socket.onopen = async (e) => {
        running = true;
        socket.send(JSON.stringify({ token: authToken, jsonApiUrl}));
    };

    socket.onmessage = async (event) => {
        if (!authResponse) {
            authResponse = event.data;
            if (authResponse !== 'AUTHORIZED') {
                console.error(`[broadcastClient] not authorised: code=${event.code} ${event.data}`);
                running = false;
            } else {
                console.info('[broadcastClient] authorised: listening')
            }
            return;
        }

        try {
            const data = JSON.parse(event.data);
            cb(data)
        } catch (e) {
            console.error(`[broadcastClient] error processing event: `,event, e);
        }
    };

    socket.onclose = async (event) => {
        if (event.wasClean) {
            console.error(`[close] Connection closed cleanly, code=${event.code} reason=${event.reason}`);
        } else {
            // e.g. server process killed or network down
            // event.code is usually 1006 in this case
            console.error('[broadcastClient] Connection died');
        }
        running = false;
        await randWait(5000, 10000);
        init(wsUrl, jsonRPC, cb);
    };

    socket.onerror = async (error) => {
        console.error(`[broadcastClient] error:`, error);
    };
};

export { init };
