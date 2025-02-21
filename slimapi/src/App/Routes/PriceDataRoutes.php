<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Repositories\PriceDataRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


use function DI\get;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

return function (App $app) {
    $app->get('/api/pricedata', function (Request $request, Response $response) {
        $queryParams = $request->getQueryParams();

        $fuelTypeID = $queryParams['fuelTypeID'] ?? null;

        if ($fuelTypeID && strpos($fuelTypeID, ',') !== false) {
            $fuelTypeIDs = explode(',', $fuelTypeID);
        } else {
            $fuelTypeIDs = $fuelTypeID ? [$fuelTypeID] : null;
        }
        
        $repository = $this->get(PriceDataRepository::class);

        if ($fuelTypeIDs) {
            $data = [];
            foreach ($fuelTypeIDs as $id) {
                $data = array_merge($data, $repository->getByFuelTypeID($id));
            }
        } else {
            $data = $repository->getAll();
        }
        
        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/pricedata/minmaxavg', function (Request $request, Response $response) {
        $queryParams = $request->getQueryParams();
        $fuelTypeID = $queryParams['fuelTypeID'] ?? null;
    
        $repository = $this->get(PriceDataRepository::class);
    
        $stats = $repository->getMinMaxAvg($fuelTypeID);
    
        $data = [
            'min' => $stats['min'],
            'max' => $stats['max'],
            'avg' => $stats['avg']
        ];
        $body = json_encode($data);
    
        $response->getBody()->write($body);
    
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/pricedata/pricelist', function (Request $request, Response $response) {
        $queryParams = $request->getQueryParams();

        $gasStationId = $queryParams['gasStationID'] ?? null;
        
        $repository = $this->get(PriceDataRepository::class);

        $data = $repository->getPriceList($gasStationId);
        
        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->put('/api/pricedata/update/{productID}', function (Request $request, Response $response, $args) {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization header missing or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $parts = explode(' ', $authorizationHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            $response->getBody()->write(json_encode(['error' => 'Authorization header not properly formatted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($bearer, $token) = $parts;

        $secretKey = $_ENV['JWT_SECRET_KEY'];

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

        $priceAndTime = $request->getParsedBody();
        if (!isset($priceAndTime['newPrice'], $priceAndTime['dateUpdated'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing order details']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $productID = $args['productID'];

        $newPrice = $priceAndTime['newPrice'];

        error_log($priceAndTime['newPrice']);

        $sqlDateTime = date('Y-m-d H:i:s', $priceAndTime['dateUpdated']);

        $dateUpdated = $sqlDateTime;

        error_log($dateUpdated);

        $repository = $this->get(PriceDataRepository::class);

        $putResult = $repository->updatePrice($productID, $newPrice, $dateUpdated, $username);

        $response->getBody()->write(json_encode($putResult));

        return $response->withHeader('Content-Type', 'application/json');

    });
};