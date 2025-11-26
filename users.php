// ==================== 6. users.php (API Utilisateurs) ====================
<?php
require_once 'config/cors.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch($action) {
        case 'customers':
            $query = "SELECT u.*, 
                     COUNT(DISTINCT o.order_id) as order_count,
                     COALESCE(SUM(o.total_amount), 0) as total_spent
                     FROM users u
                     LEFT JOIN orders o ON u.user_id = o.user_id AND o.status != 'cancelled'
                     WHERE u.role = 'customer'
                     GROUP BY u.user_id
                     ORDER BY u.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $customers]);
            break;

        case 'customer-details':
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
            if(!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                break;
            }
            
            // Info client
            $query = "SELECT u.*,
                     COUNT(DISTINCT o.order_id) as order_count,
                     COALESCE(SUM(o.total_amount), 0) as total_spent
                     FROM users u
                     LEFT JOIN orders o ON u.user_id = o.user_id AND o.status != 'cancelled'
                     WHERE u.user_id = :user_id
                     GROUP BY u.user_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($customer) {
                // DerniÃ¨res commandes
                $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                $customer['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $customer]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Customer not found']);
            }
            break;

        case 'sellers':
            $query = "SELECT s.*,
                     COUNT(DISTINCT p.product_id) as product_count
                     FROM sellers s
                     LEFT JOIN products p ON s.seller_id = p.seller_id
                     GROUP BY s.seller_id
                     ORDER BY s.business_name";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sellers]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>