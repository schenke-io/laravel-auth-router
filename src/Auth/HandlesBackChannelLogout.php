<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Events\BackChannelLogoutEvent;

trait HandlesBackChannelLogout
{
    public function backChannelLogout(Request $request, RouterData $routerData): ResponseFactory|Response
    {
        $tokenString = $request->input('logout_token');
        if (! is_string($tokenString) || $tokenString === '') {
            return response('Missing logout_token', 400);
        }

        try {
            /** @var UnencryptedToken $token */
            $token = $this->parseToken($tokenString);

            // 1. MUST NOT contain nonce
            if ($token->claims()->has('nonce')) {
                return response('Token contains nonce', 400);
            }

            // 2. MUST contain events claim
            $events = $token->claims()->get('events');
            if (! is_array($events) || ! isset($events['http://schemas.openid.net/event/backchannel-logout'])) {
                return response('Missing backchannel-logout event', 400);
            }

            // 3. MUST contain iat
            if (! $token->claims()->has('iat')) {
                return response('Missing iat claim', 400);
            }

            // 4. MUST contain sub or sid
            if (! $token->claims()->has('sub') && ! $token->claims()->has('sid')) {
                return response('Missing sub or sid', 400);
            }

            // 5. Validate issuer if possible
            $issuer = $this->getIssuer();
            if ($issuer && $token->claims()->get('iss') !== $issuer) {
                return response('Invalid issuer', 400);
            }

            // 5. Validate audience if possible
            $clientId = $this->getClientId();
            if ($clientId && ! in_array($clientId, (array) $token->claims()->get('aud'))) {
                return response('Invalid audience', 400);
            }

            // Optional: Signature validation would go here if we had the key

            $this->log($routerData, 'Back-channel logout received', [
                'sub' => $token->claims()->get('sub'),
                'sid' => $token->claims()->get('sid'),
            ]);

            BackChannelLogoutEvent::dispatch(
                $this->name,
                $token->claims()->get('sub'),
                $token->claims()->get('sid')
            );

            return response('OK', 200);

        } catch (\Exception $e) {
            return response('Invalid token: '.$e->getMessage(), 400);
        }
    }

    /**
     * @param  non-empty-string  $tokenString
     */
    protected function parseToken(string $tokenString): Token
    {
        return (new Parser(new JoseEncoder))->parse($tokenString);
    }
}
