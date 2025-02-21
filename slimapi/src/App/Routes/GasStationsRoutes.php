<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Repositories\GasStationsRepository;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

return function (App $app) {
    $app->get('/api/gasstations', function (Request $request, Response $response) {
        $queryParams = $request->getQueryParams();

        $gasStationID = $queryParams['gasStationID'] ?? null;

        if ($gasStationID && strpos($gasStationID, ',') !== false) {
            $gasStationIDs = explode(',', $gasStationID);
        } else {
            $gasStationIDs = $gasStationID ? [$gasStationID] : null;
        }
        
        $repository = $this->get(GasStationsRepository::class);

        if ($gasStationIDs) {
            $data = [];
            foreach ($gasStationIDs as $id) {
                $data = array_merge($data, $repository->getByGasStationID($id));
            }
        } else {
            $data = $repository->getAll();
        }
        
        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/gasstations/xml', function (Request $request, Response $response) use ($app) {
        $queryParams = $request->getQueryParams();
    
        $gasStationID = $queryParams['gasStationID'] ?? null;
    
        if ($gasStationID && strpos($gasStationID, ',') !== false) {
            $gasStationIDs = explode(',', $gasStationID);
        } else {
            $gasStationIDs = $gasStationID ? [$gasStationID] : [];
        }
    
        $repository = $this->get(GasStationsRepository::class);
    
        $xml = new SimpleXMLElement('<GasStations/>');
        try {
            if (!empty($gasStationIDs)) {
                foreach ($gasStationIDs as $id) {
                    $xmlData = $repository->getByGasStationIDxml($id);
                    if ($xmlData) {
                        $xmlChild = new SimpleXMLElement($xmlData);
                        mergeXML($xml, $xmlChild);
                    }
                }
            } else {
                $xmlData = $repository->getAllxml();
                if ($xmlData) {
                    $xml = new SimpleXMLElement($xmlData);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $response->getBody()->write('<error>Internal Server Error</error>');
            return $response->withStatus(500)->withHeader('Content-Type', 'application/xml');
        }
    
        $response->getBody()->write($xml->asXML());
    
        return $response->withHeader('Content-Type', 'application/xml');
    });

    function mergeXML(SimpleXMLElement $to, SimpleXMLElement $from) {
        foreach ($from->children() as $child) {
            $newChild = $to->addChild($child->getName(), (string)$child);
            foreach ($child->attributes() as $attrKey => $attrValue) {
                $newChild->addAttribute($attrKey, $attrValue);
            }
            if ($child->count() > 0) {
                mergeXML($newChild, $child);
            }
        }
    }

    $app->get('/api/gasstations/count', function (Request $request, Response $response) {
        $queryParams = $request->getQueryParams();

        $gasStationID = $queryParams['gasStationID'] ?? null;

        $repository = $this->get(GasStationsRepository::class);

        if ($gasStationID) {
            $count = $repository->countByGasStationID($gasStationID);
        } else {
            $count = $repository->countAll();
        }

        $data = ['count' => $count];
        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/gasstations/owners/{username}', function (Request $request, Response $response, $args) {

        $username = $args['username'];

        $repository = $this->get(GasStationsRepository::class);

        $data = $repository->getByUsername($username);

        $body = json_encode($data);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    });
};