<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Exception\HttpUnauthorizedException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class JwtMiddleware
{
    private $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            throw new HttpUnauthorizedException($request, 'Missing Authorization Header');
        }

        $jwt = str_replace('Bearer ', '', $authHeader[0]);
        try {
            $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);
        } catch (ExpiredException $e) {
            throw new HttpUnauthorizedException($request, 'Expired token: ' . $e->getMessage());
        } catch (SignatureInvalidException $e) {
            throw new HttpUnauthorizedException($request, 'Invalid signature: ' . $e->getMessage());
        } catch (BeforeValidException $e) {
            throw new HttpUnauthorizedException($request, 'Token not yet valid: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new HttpUnauthorizedException($request, 'Invalid token: ' . $e->getMessage());
        }
    }
}
