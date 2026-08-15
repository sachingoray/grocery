<?php
require_once __DIR__ . '/../../includes/session.php';
mff_require_role(['admin']);

$pdo = mff_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $badge = trim($_POST['badge'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');

        if ($name === '') $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than zero.';

        if (empty($errors) && $pdo !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category, price, stock, low_stock_threshold, badge, image_url, description)
                 VALUES (:name, :category, :price, :stock, 10, :badge, :image_url, :description)'
            );
            $stmt->execute([
                'name' => $name, 'category' => $category, 'price' => $price, 'stock' => $stock,
                'badge' => $badge, 'image_url' => $imageUrl, 'description' => trim($_POST['description'] ?? ''),
            ]);
            mff_set_flash('success', $name . ' added to the catalogue.');
        } elseif (empty($errors)) {
            mff_set_flash('info', 'No database connected — product not persisted (demo mode).');
        }
        header('Location: ' . BASE_URL . '/admin/manage_products.php');
        exit;
    }

    if ($action === 'delete') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($pdo !== null) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute(['id' => $productId]);
        }
        mff_set_flash('success', 'Product removed.');
        header('Location: ' . BASE_URL . '/admin/manage_products.php');
        exit;
    }
}

$products = mff_get_products();

$pageTitle = 'Manage Products';
$activeNav = 'admin';
require __DIR__ . '/../../includes/header.php';
?>

<p class="section-eyebrow">Store management</p>
<h1 class="section-title">Manage products</h1>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error" style="margin-top:1rem;max-width:none;">
    <ul style="margin:0;padding-left:1.1rem;">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card-artisan" style="margin-top:1.75rem;">
  <h2 style="font-size:1.3rem;font-weight:700;">Add new product</h2>
  <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" class="form-grid">
    <input type="hidden" name="action" value="create">
    <div class="form-field"><label for="p-name">Name</label><input id="p-name" name="name" required type="text"></div>
    <div class="form-field">
      <label for="p-category">Category</label>
      <select id="p-category" name="category">
        <option>Produce</option><option>Bakery</option><option>Meat &amp; Seafood</option><option>Dairy</option><option>Beverages</option>
      </select>
    </div>
    <div class="form-field"><label for="p-price">Price ($)</label><input id="p-price" name="price" required type="number" step="0.01" min="0.01"></div>
    <div class="form-field"><label for="p-stock">Stock quantity</label><input id="p-stock" name="stock" required type="number" min="0" value="0"></div>
    <div class="form-field"><label for="p-badge">Badge (optional)</label><input id="p-badge" name="badge" type="text" placeholder="e.g. Organic"></div>
    <div class="form-field"><label for="p-image">Image URL</label><input id="p-image" name="image_url" type="url" placeholder="https://..."></div>
    <div class="form-field form-field--full"><label for="p-description">Description</label><textarea id="p-description" name="description" rows="2"></textarea></div>
    <div class="form-field--full"><button type="submit" class="btn-tomato">Add product</button></div>
  </form>
</div>

<div class="data-table-wrap">
  <table class="data-table">
    <caption class="sr-only">Product catalogue</caption>
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($products as $product): ?>
        <?php $isLow = $product['stock'] <= ($product['low_stock_threshold'] ?? 10); ?>
        <tr>
          <td style="font-weight:700;"><?= htmlspecialchars($product['name']) ?></td>
          <td><?= htmlspecialchars($product['category']) ?></td>
          <td><?= mff_money($product['price']) ?></td>
          <td><?= (int) $product['stock'] ?></td>
          <td><span class="status <?= $isLow ? 'status-low' : 'status-instock' ?>"><?= $isLow ? 'Low stock' : 'In stock' ?></span></td>
          <td>
            <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" onsubmit="return confirm('Remove this product?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
              <button type="submit" class="btn-outline">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
