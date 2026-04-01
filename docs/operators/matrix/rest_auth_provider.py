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
            check_3pid_auth=self.check_3pid_auth,
            auth_checkers={
                ("m.login.password", ("password",)): self.check_auth,
            },
        )

    async def check_3pid_auth(
        self,
        medium: str, 
        address: str,
        password: str,
    ) -> Optional[Tuple[str,Optional[Callable[["synapse.module_api.LoginResponse"], Awaitable[None]]]]]:
        logger.info("RestAuthProvider: Login attempt (3pid): medium = {}, address = {}".format(medium, address))

        if medium != "email":
            return None

        return await self.check_auth_rest(password=password, email=address)

    async def check_auth(
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

        logger.info("RestAuthProvider: Login attempt (pass): username = {}".format(username))

        return await self.check_auth_rest(password=login_dict.get("password"), username=username)
        
    async def check_auth_rest(self, password: str, username: str = None, email: str = None):
        data = {'user': {'password': password}}

        if username is not None:
            qualified_user_id = self.api.get_qualified_user_id(username.lower())

            if await self.api.check_user_exists(qualified_user_id) is None:
                data['user']['loginName'] = username
                logger.info("RestAuthProvider: No user with id {} exits, expecting {} to be a tine login name:".format(qualified_user_id, username))
            else:
                data['user']['id'] = qualified_user_id

        if email is not None:
            data['user']['loginName'] = email

        r = requests.post(self.endpoint + '/_matrix-internal/identity/v1/check_credentials', json = data)
        r.raise_for_status()
        r = r.json()
        if not r["auth"]:
            reason = "Invalid JSON data returned from REST endpoint"
            logger.warning(reason)
            raise RuntimeError(reason)

        auth = r["auth"]
        if not auth["success"]:
            logger.info("RestAuthProvider: User not authenticated: username = {} email= {} matrix_id = {}".format(username, email, matrix_id))
            return None

        matrix_id = auth["mxid"]

        if await self.api.check_user_exists(matrix_id) == None:
            logger.info("RestAuthProvider: User dose not exist yet: username = {} email= {} matrix_id = {}".format(username, email, matrix_id))
            localpart = matrix_id.split(":", 1)[0][1:]

            await self.api.register_user(localpart, auth["profile"]["display_name"])
            logger.info("RestAuthProvider: Registered user: username = {} email= {} matrix_id = {}".format(username, email, matrix_id))

        logger.info("RestAuthProvider: User authenticated: username = {} email= {} matrix_id = {}".format(username, email, matrix_id))
        return (matrix_id, None)