<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use BadFunctionCallException;
use PDO;

class OrdersRepository
{
    public function __construct(private Database $database)
    {
    }

    public function postOrder($productID, $username, $quantity, $when)
    {
        $pdo = $this->database->getConnection();

        $customerCheckStmt = $pdo->prepare('SELECT count(*) FROM gasstations where username = :username');
        $customerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $customerCheckStmt->execute();
        $isNotCustomer = $customerCheckStmt->fetchColumn();

        if ($isNotCustomer) {
            return [
                'success' => false,
                'message' => "Unauthorized. You are not a customer.",
            ];
        }

        $stmt = $pdo->prepare('INSERT INTO orders (productID, username, quantity, `when`) VALUES (:productID, :username, :quantity, :when)');
        
        $stmt->bindParam(':productID', $productID, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':when', $when, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new BadFunctionCallException('Failed to insert order');
        }

        return [
            'success' => true,
            'message' => 'Order placed successfully',
        ];
    }

    public function getOrdersByGasStationID($gasStationID, $username)
    {
        $pdo = $this->database->getConnection();

        $ownerCheckStmt = $pdo->prepare('SELECT count(*) FROM gasstations WHERE username = :username AND gasStationID = :gasStationID');
        $ownerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $ownerCheckStmt->bindParam(':gasStationID', $gasStationID, PDO::PARAM_INT);
        $ownerCheckStmt->execute();
        $isOwner = $ownerCheckStmt->fetchColumn();

        if (!$isOwner) {
            return [
                'success' => false,
                'message' => "Unauthorized. You are not the owner of this gas station.",
            ];
        }

        $stmt = $pdo->prepare('SELECT pricedata.fuelName, orders.username, orders.quantity, orders.when, orders.orderId FROM orders INNER JOIN pricedata ON orders.productID = pricedata.productID WHERE pricedata.gasStationID = :gasStationID AND (orders.status != "accepted" OR orders.status IS NULL) ORDER BY orders.when ASC');
        $stmt->bindParam(':gasStationID', $gasStationID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdersByUsername($username)
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare('SELECT pricedata.fuelName, orders.quantity, orders.when, gasstations.gasStationOwner, gasstations.gasStationAddress, orders.orderId FROM orders INNER JOIN pricedata ON orders.productID = pricedata.productID INNER JOIN gasstations ON pricedata.gasStationID = gasstations.gasStationID WHERE orders.username = :username AND (orders.status != "accepted" OR orders.status IS NULL) ORDER BY orders.when ASC');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acceptOrder($orderId, $username)
    {
        $pdo = $this->database->getConnection();

        $ownerCheckStmt = $pdo->prepare('SELECT count(*) FROM orders INNER JOIN pricedata ON orders.productID = pricedata.productID INNER JOIN gasstations ON pricedata.gasStationID = gasstations.gasStationID WHERE gasstations.username = :username AND orders.orderId = :orderId');
        $ownerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $ownerCheckStmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $ownerCheckStmt->execute();
        $isOwner = $ownerCheckStmt->fetchColumn();

        if (!$isOwner) {
            return [
                'success' => false,
                'message' => "Unauthorized. You are not an owner or the product does not belong to your gas station.",
            ];
        }

        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE orderId = :orderId');
    
        $status = 'accepted';
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
    
        if (!$stmt->execute()) {
            throw new BadFunctionCallException('Failed to update order status');
        }
    
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Order accepted successfully',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Order not found or already accepted',
            ];
        }
    }

    public function deleteOrder($orderId, $username)
    {
        $pdo = $this->database->getConnection();
    
        $ownerCheckStmt = $pdo->prepare('SELECT count(*) FROM orders INNER JOIN pricedata ON orders.productID = pricedata.productID INNER JOIN gasstations ON pricedata.gasStationID = gasstations.gasStationID WHERE gasstations.username = :username AND orders.orderId = :orderId');
        $ownerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $ownerCheckStmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $ownerCheckStmt->execute();
        $isOwner = $ownerCheckStmt->fetchColumn();

        $customerCheckStmt = $pdo->prepare('SELECT count(*) FROM orders WHERE orders.username = :username AND orders.orderId = :orderId');
        $customerCheckStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $customerCheckStmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $customerCheckStmt->execute();
        $isCustomer = $customerCheckStmt->fetchColumn();
    
        if (!$isOwner && !$isCustomer) {
            return [
                'success' => false,
                'message' => "Unauthorized. You are not an owner or the order does not belong to your gas station.",
            ];
        }
    
        $stmt = $pdo->prepare('DELETE FROM orders WHERE orderId = :orderId AND status IS NULL');
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
    
        if (!$stmt->execute()) {
            throw new BadFunctionCallException('Failed to delete order');
        }
    
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Order deleted successfully',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Order not found or accepted or already deleted',
            ];
        }
    }
}