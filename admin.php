// ==================== 4. admin.php (API Admin) ====================
<?php
require_once 'config/cors.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($action) {
        case 'dashboard':
            // Statistiques totales
            $stats = [];
            
            // Utilisateurs
            $query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
            $stmt = $db->query($query);
            $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Produits
            $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
            $stmt = $db->query($query);
            $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Commandes
            $query = "SELECT COUNT(*) as total FROM orders";
            $stmt = $db->query($query);
            $stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Revenu
            $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'";
            $stmt = $db->query($query);
            $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // DerniÃ¨res commandes
            $query = "SELECT o.*, u.first_name, u.last_name, u.email 
                     FROM orders o 
                     INNER JOIN users u ON o.user_id = u.user_id 
                     ORDER BY o.created_at DESC 
                     LIMIT 10";
            $stmt = $db->query($query);
            $stats['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'product/add':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "INSERT INTO products (name, category_id, seller_id, description, price, unit, stock_quantity) 
                     VALUES (:name, :category_id, :seller_id, :description, :price, :unit, :stock_quantity)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':seller_id', $data['seller_id']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product added']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add product']);
            }
            break;

        case 'product/update':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE products SET 
                     name = :name,
                     category_id = :category_id,
                     seller_id = :seller_id,
                     description = :description,
                     price = :price,
                     unit = :unit,
                     stock_quantity = :stock_quantity
                     WHERE product_id = :product_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':seller_id', $data['seller_id']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update product']);
            }
            break;

        case 'product/delete':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "DELETE FROM products WHERE product_id = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $data['product_id']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product deleted']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete product']);
            }
            break;

        case 'category/add':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "INSERT INTO categories (name, description, icon, display_order) 
                     VALUES (:name, :description, :icon, :display_order)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':icon', $data['icon']);
            $stmt->bindParam(':display_order', $data['display_order']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category added']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add category']);
            }
            break;

        case 'category/update':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE categories SET 
                     name = :name,
                     description = :description,
                     icon = :icon
                     WHERE category_id = :category_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':icon', $data['icon']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update category']);
            }
            break;

        case 'category/toggle':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE categories SET is_active = :is_active WHERE category_id = :category_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':is_active', $data['is_active']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category status updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update status']);
            }
            break;

        case 'seller/verify':
            if($method !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE sellers SET is_verified = :verified WHERE seller_id = :seller_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':seller_id', $data['seller_id']);
            $stmt->bindParam(':verified', $data['verified']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Seller status updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update seller']);
            }
            break;

        case 'analytics':
            $analytics = [];
            
            // Commandes ce mois
            $query = "SELECT COUNT(*) as count FROM orders 
                     WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                     AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $db->query($query);
            $analytics['monthly_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Revenu ce mois
            $query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                     WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                     AND YEAR(created_at) = YEAR(CURRENT_DATE())
                     AND status != 'cancelled'";
            $stmt = $db->query($query);
            $analytics['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            
            // Panier moyen
            $query = "SELECT COALESCE(AVG(total_amount), 0) as avg_order FROM orders WHERE status != 'cancelled'";
            $stmt = $db->query($query);
            $analytics['average_order'] = $stmt->fetch(PDO::FETCH_ASSOC)['avg_order'];
            
            // Nouveaux clients ce mois
            $query = "SELECT COUNT(*) as count FROM users 
                     WHERE role = 'customer' 
                     AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                     AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $db->query($query);
            $analytics['new_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Top produits
            $query = "SELECT p.name as product_name, c.name as category_name,
                     SUM(oi.quantity) as total_quantity,
                     SUM(oi.subtotal) as total_revenue
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.product_id
                     INNER JOIN categories c ON p.category_id = c.category_id
                     GROUP BY oi.product_id
                     ORDER BY total_quantity DESC
                     LIMIT 10";
            $stmt = $db->query($query);
            $analytics['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top vendeurs
            $query = "SELECT s.business_name, s.rating,
                     COUNT(DISTINCT o.order_id) as order_count,
                     SUM(oi.subtotal) as total_revenue
                     FROM sellers s
                     INNER JOIN products p ON s.seller_id = p.seller_id
                     INNER JOIN order_items oi ON p.product_id = oi.product_id
                     INNER JOIN orders o ON oi.order_id = o.order_id
                     WHERE o.status != 'cancelled'
                     GROUP BY s.seller_id
                     ORDER BY total_revenue DESC
                     LIMIT 10";
            $stmt = $db->query($query);
            $analytics['top_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $analytics]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>