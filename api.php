// ==================== 3. api.php (Point d'entrÃ©e principal) ====================
<?php
require_once 'config/cors.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch($action) {
        case 'categories':
            $query = "SELECT c.*, COUNT(p.product_id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.category_id = p.category_id 
                     GROUP BY c.category_id 
                     ORDER BY c.display_order, c.name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $categories]);
            break;

        case 'products':
            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
            
            $query = "SELECT p.*, c.name as category_name, c.icon as category_icon,
                     s.business_name as seller_name, s.location as seller_location
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     INNER JOIN sellers s ON p.seller_id = s.seller_id
                     WHERE p.is_active = 1";
            
            if($categoryId) {
                $query .= " AND p.category_id = :category_id";
            }
            
            $query .= " ORDER BY p.created_at DESC LIMIT :limit";
            
            $stmt = $db->prepare($query);
            if($categoryId) $stmt->bindParam(':category_id', $categoryId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $products]);
            break;

        case 'sellers':
            $query = "SELECT s.*, COUNT(p.product_id) as product_count 
                     FROM sellers s 
                     LEFT JOIN products p ON s.seller_id = p.seller_id 
                     GROUP BY s.seller_id 
                     ORDER BY s.business_name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sellers]);
            break;

        case 'product-details':
            $productId = isset($_GET['product_id']) ? $_GET['product_id'] : null;
            if(!$productId) {
                echo json_encode(['success' => false, 'error' => 'Product ID required']);
                break;
            }
            
            $query = "SELECT p.*, c.name as category_name, c.icon as category_icon,
                     s.business_name as seller_name, s.location as seller_location,
                     s.phone as seller_phone, s.rating as seller_rating
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     INNER JOIN sellers s ON p.seller_id = s.seller_id
                     WHERE p.product_id = :product_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if($product) {
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Product not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>