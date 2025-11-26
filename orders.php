// ==================== 5. orders.php (API Commandes) ====================
<?php
require_once 'config/cors.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($action) {
        case 'all':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            
            $query = "SELECT o.*, u.first_name, u.last_name, u.email,
                     COUNT(oi.order_item_id) as total_items
                     FROM orders o
                     INNER JOIN users u ON o.user_id = u.user_id
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     GROUP BY o.order_id
                     ORDER BY o.created_at DESC
                     LIMIT :limit";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $orders]);
            break;

        case 'details':
            $orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;
            if(!$orderId) {
                echo json_encode(['success' => false, 'error' => 'Order ID required']);
                break;
            }
            
            // Informations de la commande
            $query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone
                     FROM orders o
                     INNER JOIN users u ON o.user_id = u.user_id
                     WHERE o.order_id = :order_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$order) {
                echo json_encode(['success' => false, 'error' => 'Order not found']);
                break;
            }
            
            // Articles de la commande
            $query = "SELECT oi.*, p.name as product_name, p.unit
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.product_id
                     WHERE oi.order_id = :order_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $order]);
            break;

        case 'create':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $db->beginTransaction();
            
            try {
                // CrÃ©er la commande
                $orderNumber = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
                
                $query = "INSERT INTO orders (user_id, order_number, total_amount, delivery_address, status) 
                         VALUES (:user_id, :order_number, :total_amount, :delivery_address, 'pending')";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $data['user_id']);
                $stmt->bindParam(':order_number', $orderNumber);
                $stmt->bindParam(':total_amount', $data['total_amount']);
                $stmt->bindParam(':delivery_address', $data['delivery_address']);
                $stmt->execute();
                
                $orderId = $db->lastInsertId();
                
                // Ajouter les articles
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                         VALUES (:order_id, :product_id, :quantity, :price, :subtotal)";
                
                $stmt = $db->prepare($query);
                
                foreach($data['items'] as $item) {
                    $stmt->bindParam(':order_id', $orderId);
                    $stmt->bindParam(':product_id', $item['product_id']);
                    $stmt->bindParam(':quantity', $item['quantity']);
                    $stmt->bindParam(':price', $item['price']);
                    $stmt->bindParam(':subtotal', $item['subtotal']);
                    $stmt->execute();
                    
                    // Mettre Ã  jour le stock
                    $updateQuery = "UPDATE products SET stock_quantity = stock_quantity - :quantity 
                                   WHERE product_id = :product_id";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':quantity', $item['quantity']);
                    $updateStmt->bindParam(':product_id', $item['product_id']);
                    $updateStmt->execute();
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'order_number' => $orderNumber
                ]);
            } catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'update-status':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE orders SET status = :status WHERE order_id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->bindParam(':status', $data['status']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Order status updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update status']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>