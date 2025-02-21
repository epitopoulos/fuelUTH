<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Repositories\OrdersRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

return function (App $app) {
    $app->post('/api/orders', function (Request $request, Response $response) {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $secretKey = $_ENV['JWT_SECRET_KEY'];

        $repository = $this->get(OrdersRepository::class);

        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($bearer, $token) = $parts;

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            if (!isset($decoded->data->username)) {
                throw new Exception('Username not found in token');
            }
            $username = $decoded->data->username;
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $orderDetails = $request->getParsedBody();
        if (!isset($orderDetails['productID'], $orderDetails['quantity'], $orderDetails['when'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing order details']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); // Bad Request
        }

        if ($orderDetails['quantity'] <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Quantity must be greater than 0']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); // Bad Request
        }

        $sqlDateTime = date('Y-m-d H:i:s', $orderDetails['when']);
        
        $productID = $orderDetails['productID'];
        $quantity = $orderDetails['quantity'];
        $when = $sqlDateTime;
        
        $body = $repository->postOrder($productID, $username, $quantity, $when);

        $response->getBody()->write(json_encode($body));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/orders/owner/{gasStationID}', function (Request $request, Response $response, $args) {

        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $secretKey = $_ENV['JWT_SECRET_KEY'];

        $repository = $this->get(OrdersRepository::class);

        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($bearer, $token) = $parts;

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            if (!isset($decoded->data->username)) {
                throw new Exception('Username not found in token');
            }
            $username = $decoded->data->username;
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $gasStationID = $args['gasStationID'];

        $repository = $this->get(OrdersRepository::class);

        $data = $repository->getOrdersByGasStationID($gasStationID, $username);

        $body = json_encode($data);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/orders/customer/{username}', function (Request $request, Response $response, $args) {

        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $secretKey = $_ENV['JWT_SECRET_KEY'];

        $repository = $this->get(OrdersRepository::class);

        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($bearer, $token) = $parts;

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            if (!isset($decoded->data->username)) {
                throw new Exception('Username not found in token');
            }
            $tokenUsername = $decoded->data->username;
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $username = $args['username'];

        if ($username !== $tokenUsername) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $repository = $this->get(OrdersRepository::class);

        $data = $repository->getOrdersByUsername($username);

        $body = json_encode($data);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->put('/api/orders/{orderId}/accept', function (Request $request, Response $response, $args) {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $secretKey = $_ENV['JWT_SECRET_KEY'];

        $repository = $this->get(OrdersRepository::class);

        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($bearer, $token) = $parts;

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            if (!isset($decoded->data->username)) {
                throw new Exception('Username not found in token');
            }
            $username = $decoded->data->username;
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $orderId = $args['orderId'];

        $body = $repository->acceptOrder($orderId, $username);

        $response->getBody()->write(json_encode($body));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->delete('/api/orders/{orderId}/delete', function (Request $request, Response $response, $args) {
        $authorizationHeader = $request->getHeaderLine('Authorization');
    
        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
        $secretKey = $_ENV['JWT_SECRET_KEY'];
    
        $repository = $this->get(OrdersRepository::class);
    
        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
        list($bearer, $token) = $parts;
    
        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            if (!isset($decoded->data->username)) {
                throw new Exception('Username not found in token');
            }
            $username = $decoded->data->username;
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
        $orderId = $args['orderId'];
    
        $body = $repository->deleteOrder($orderId, $username);
    
        $response->getBody()->write(json_encode($body));

        return $response->withHeader('Content-Type', 'application/json');
    });
};