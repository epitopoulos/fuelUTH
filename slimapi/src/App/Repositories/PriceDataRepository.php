<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class PriceDataRepository
{
    public function __construct(private Database $database)
    {
    }

    public function getAll(): array
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->query('SELECT * FROM pricedata');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 

    public function getByFuelTypeID(string $fuelTypeID): array
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM pricedata WHERE fuelTypeID = :fuelTypeID');
        $stmt->bindParam(':fuelTypeID', $fuelTypeID, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMinMaxAvg($fuelTypeID)
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT MIN(fuelPrice) as min, MAX(fuelPrice) as max, ROUND(AVG(fuelPrice), 3) as avg FROM pricedata WHERE fuelTypeID = :fuelTypeID');
        $stmt->bindParam(':fuelTypeID', $fuelTypeID, PDO::PARAM_STR);
        $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPriceList($gasStationID)
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM pricedata WHERE gasStationID = :gasStationID');
        $stmt->bindParam(':gasStationID', $gasStationID, PDO::PARAM_STR);
        $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePrice($productID, $newPrice, $dateUpdated, $username)
    {
        $pdo = $this->database->getConnection();

        $ownerCheckStmt = $pdo->prepare('SELECT count(*) FROM gasstations INNER JOIN pricedata ON gasstations.gasStationID = pricedata.gasStationID WHERE gasstations.username = :username AND pricedata.productID = :productID');
        $ownerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $ownerCheckStmt->bindParam(':productID', $productID, PDO::PARAM_INT);
        $ownerCheckStmt->execute();
        $isOwner = $ownerCheckStmt->fetchColumn();

        if (!$isOwner) {
            return [
                'success' => false,
                'message' => "Unauthorized. You are not an owner or the product does not belong to your gas station.",
            ];
        }

        if (floor($newPrice * 1000) != $newPrice * 1000) {
            return [
                'success' => false,
                'message' => "Invalid price format. The new price must have a maximum of 3 decimal places.",
            ];
        }
    
        $currentPriceStmt = $pdo->prepare('SELECT fuelPrice FROM pricedata WHERE productID = :productID');
        $currentPriceStmt->bindParam(':productID', $productID, PDO::PARAM_INT);
        $currentPriceStmt->execute();
        $currentPriceResult = $currentPriceStmt->fetch(PDO::FETCH_ASSOC);
    
        if ($currentPriceResult && $currentPriceResult['fuelPrice'] == $newPrice) {
            return [
                'success' => false,
                'message' => "No changes made. The new price is the same as the current price for productID: $productID",
                'affectedRows' => 0
            ];
        }
    
        $stmt = $pdo->prepare('UPDATE pricedata SET fuelPrice = :fuelPrice, dateUpdated = :dateUpdated WHERE productID = :productID');
        $stmt->bindParam(':productID', $productID, PDO::PARAM_INT);
        $stmt->bindParam(':fuelPrice', $newPrice);
        $stmt->bindParam(':dateUpdated', $dateUpdated, PDO::PARAM_STR);
        $stmt->execute();
    
        $affectedRows = $stmt->rowCount();
    
        if ($affectedRows > 0) {
            return [
                'success' => true,
                'message' => "Price updated successfully for productID: $productID",
                'affectedRows' => $affectedRows
            ];
        } else {
            return [
                'success' => false,
                'message' => "No changes made. Either productID: $productID doesn't exist.",
                'affectedRows' => $affectedRows
            ];
        }
    }
}