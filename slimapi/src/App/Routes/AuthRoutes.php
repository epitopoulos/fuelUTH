<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use App\Repositories\UserRepository;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

return function (App $app) {
    $app->post('/api/login', function (Request $request, Response $response) {
        $body = $request->getBody()->getContents();
        error_log("Received request body: " . $body);
    
        $data = json_decode($body, true);

        error_log("Decoded data:");
        error_log(print_r($data, true));

        $usernameOrEmail = $data['username_or_email'] ?? '';
        $password = $data['password'] ?? '';
        error_log(print_r($usernameOrEmail, true));
        error_log(print_r($password, true));

        $repository = $this->get(UserRepository::class);

        $user = $repository->findByEmail($usernameOrEmail);

        if (!$user) {
            $user = $repository->findByUsername($usernameOrEmail);
            error_log('User found: ' . print_r($user, true));
        }

        if (!$user || $password != $user['password']) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $secretKey = $_ENV['JWT_SECRET_KEY'];
        $issuedAt = time();
        $expirationTime = $issuedAt + (3600 * 24);
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];

        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        $response->getBody()->write(json_encode(['token' => $jwt, 'username' => $user['username']]));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
