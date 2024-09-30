<?php declare(strict_types=1);

class Tinebase_Webfinger
{
    /**
     * Tinebase_Expressive_RoutHandler function
     *
     * @return \Laminas\Diactoros\Response
     */
    public static function handlePublicGet(): \Laminas\Diactoros\Response
    {
        /** @var \Laminas\Diactoros\ServerRequest $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        if (self::unpleasantRequest($request) == true) {
            return static::notFound();
        }
        $params = $request->getQueryParams();
        if (!isset($params['resource']) || !isset($params['rel'])) {
            return static::badRequest();
        }
        $resource = $params['resource'];
        $rel = $params['rel'];

        $response = new \Laminas\Diactoros\Response('php://memory', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/jrd+json'
        ]);

        $result = [
            'subject' => $resource,
            'aliases' => [],
            'properties' => [],
            'links' => []
        ];

        $relHandler = Tinebase_Config::getInstance()->{Tinebase_Config::WEBFINGER_REL_HANDLER};
        if (isset($relHandler[$rel])) {
            call_user_func_array($relHandler[$rel], [&$result]);
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }
    
    protected static function badRequest(): \Laminas\Diactoros\Response
    {
        return new \Laminas\Diactoros\Response('php://memory', 400);
    }

    protected static function notFound(): \Laminas\Diactoros\Response
    {
        return new \Laminas\Diactoros\Response('php://memory', 404);
    }

    /*
     * Some clients may have strange results if webfinger is present. 
     */
    protected static function unpleasantRequest(\Laminas\Diactoros\ServerRequest $request): bool
    {
        // OwnCloud client asks for SSO, Tine publishes webfinger regardless if SSO is enabled.
        // Tested with Android App 4.3.0. 
        //
        // Better not ask for SSO > Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED); <,
        // even if there is no extra value if ownCloud instances are not listed; But there might be valid cases. 
        $user_agent = $request->getHeader('User-Agent');
        if(!empty($user_agent)) {
            $ownCloud_agents = Tinebase_WebDav_Plugin_OwnCloud::USER_AGENTS;
            if (preg_match('/('. implode('|', $ownCloud_agents) .')\/(\d+\.\d+\.\d+)/', $user_agent[0])) {
                return true;
            }
        }

        return false;
    }
}
