# Rest auth provider from synapse. To use with MatrixSynapseIntegrator

from typing import Awaitable, Callable, Optional, Tuple

import logging
import synapse
import requests
import json
from synapse import module_api

logger = logging.getLogger(__name__)

class RestAuthProvider:
    def __init__(self, config: dict, api: module_api):
        if not config["endpoint"]:
            raise RuntimeError('Missing endpoint config')

        self.endpoint = config["endpoint"]
        
        self.api = api

        logger.info('Endpoint: %s', self.endpoint)

        api.register_password_auth_provider_callbacks(
            auth_checkers={
                ("m.login.password", ("password",)): self.check_pass,
            },
        )

    async def check_pass(
        self,
        username: str,
        login_type: str,
        login_dict: "synapse.module_api.JsonDict",
    ) -> Optional[
        Tuple[
            str,
            Optional[Callable[["synapse.module_api.LoginResponse"], Awaitable[None]]],
        ]
    ]:
        if login_type != "m.login.password":
            return None

        logger.info("RestAuthProvider: Check password: username = " + username)

        user_id = self.api.get_qualified_user_id(username.lower())
        
        data = {'user': {'id': user_id, 'password': login_dict.get("password")}}
        r = requests.post(self.endpoint + '/_matrix-internal/identity/v1/check_credentials', json = data)
        r.raise_for_status()
        r = r.json()
        if not r["auth"]:
            reason = "Invalid JSON data returned from REST endpoint"
            logger.warning(reason)
            raise RuntimeError(reason)

        auth = r["auth"]
        if not auth["success"]:
            logger.info("RestAuthProvider: User not authenticated: username = " + username + " user_id = " + user_id)
            return None

        if await self.api.check_user_exists(user_id) == None:
            logger.info("RestAuthProvider: User dose not exist yet: username = " + username + " user_id = " + user_id)
            localpart = user_id.split(":", 1)[0][1:]

            await self.api.register_user(localpart, auth["profile"]["display_name"])
            logger.info("RestAuthProvider: Registered user: username = " + username + " user_id = " + user_id)

        logger.info("RestAuthProvider: User authenticated: username = " + username + " user_id = " + user_id)
        return (user_id, None)