<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use Error;
use SimpleXMLElement;
use PDO;

class GasStationsRepository
{
    public function __construct(private Database $database)
    {
    }

    public function getAll(): array
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->query('SELECT * FROM gasStations');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllxml(): string
    {
        $pdo = $this->database->getConnection();
    
        $stmt = $pdo->query('SELECT * FROM gasStations');
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $xml = new SimpleXMLElement('<GasStations/>');

        array_walk($results, function ($row) use ($xml) {
            $gasStation = $xml->addChild('GasStation');
            foreach ($row as $key => $value) {
                $gasStation->addChild($key, htmlspecialchars((string)$value));
            }
        });
    
        return $xml->asXML();
    }

    public function getByGasStationID(string $gasStationID): array
    {
        $pdo = $this->database->getConnection();

        error_log("GasStationID: " . $gasStationID);

        $stmt = $pdo->prepare('SELECT * FROM gasStations WHERE gasStationID = :gasStationID');
        $stmt->bindParam(':gasStationID', $gasStationID, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByGasStationIDxml(string $gasStationID): string
    {
    $pdo = $this->database->getConnection();

    $stmt = $pdo->prepare('SELECT * FROM gasStations WHERE gasStationID = :gasStationID');
    $stmt->bindParam(':gasStationID', $gasStationID, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $xml = new SimpleXMLElement('<?xml version="1.0"?><GasStations/>');

    foreach ($results as $row) {
        $gasStation = $xml->addChild('Station');
        foreach ($row as $key => $value) {
            $gasStation->addChild($key, htmlspecialchars((string)$value));
        }
    }

    return $xml->asXML();
    }

    public function countByGasStationID($id)
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT gasStationID) as count from  gasstations WHERE FIND_IN_SET(gasStationID, :id)");
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    public function countAll()
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM gasstations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    public function getByUsername($username)
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare("SELECT gasStationID FROM gasstations WHERE username = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? null;

        if ($result) {
            return ['owner_or_customer' => 'owner', 'gasStationID' => $result['gasStationID']];
        } else {
            return ['owner_or_customer' => 'customer'];
        }
    }
}