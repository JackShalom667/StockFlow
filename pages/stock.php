<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $min_quantity = $_POST['min_quantity'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;

        $error = '';

        // Validate input
        if (empty($name) || empty($unit)) {
            $error = 'Please fill all required fields';
        }

        if (empty($error)) {
            if ($action === 'add') {
                // Insert new stock item
                $query = "INSERT INTO stock (name, description, unit, quantity, min_quantity) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssii", $name, $description, $unit, $quantity, $min_quantity);

                if ($stmt->execute()) {
                    $item_id = $conn->insert_id;

                    // If initial quantity is set, record as transaction
                    if ($quantity > 0) {
                        $now = date('Y-m-d H:i:s');
                        $user_id = $_SESSION['user_id'];

                        // Use default supplier (1) or NULL
                        $supplier_id = 1; // Assuming there's at least one supplier

                        $query = "INSERT INTO transactions (item_id, supplier_id, user_id, type, quantity, transaction_date, notes) 
                                 VALUES (?, ?, ?, 'in', ?, ?, 'Initial stock')";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("iiiss", $item_id, $supplier_id, $user_id, $quantity, $now);
                        $stmt->execute();
                    }

                    echo '<div class="alert alert-success">Stock item added successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
                }
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? 0;
                $current_qty = $_POST['current_quantity'] ?? 0;

                if ($id > 0) {
                    $query = "UPDATE stock SET name = ?, description = ?, unit = ?, min_quantity = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssii", $name, $description, $unit, $min_quantity, $id);

                    if ($stmt->execute()) {
                        // If quantity changed, record as adjustment transaction
                        if ($quantity != $current_qty) {
                            $diff = $quantity - $current_qty;
                            $type = $diff > 0 ? 'in' : 'out';
                            $abs_diff = abs($diff);

                            $now = date('Y-m-d H:i:s');
                            $user_id = $_SESSION['user_id'];

                            // Use default supplier (1) or NULL
                            $supplier_id = 1; // Assuming there's at least one supplier

                            $query = "INSERT INTO transactions (item_id, supplier_id, user_id, type, quantity, transaction_date, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'Inventory adjustment')";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("iiisss", $id, $supplier_id, $user_id, $type, $abs_diff, $now);
                            $stmt->execute();

                            // Update stock quantity
                            $query = "UPDATE stock SET quantity = ? WHERE id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ii", $quantity, $id);
                            $stmt->execute();
                        }

                        echo '<div class="alert alert-success">Stock item updated successfully!</div>';
                    } else {
                        echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
                    }
                }
            }
        } else {
            echo '<div class="alert alert-danger">' . $error . '</div>';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;

        if ($id > 0) {
            // Check if item has transactions
            $query = "SELECT COUNT(*) as count FROM transactions WHERE item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];

            if ($count > 0) {
                echo '<div class="alert alert-danger">Cannot delete item with existing transactions!</div>';
            } else {
                $query = "DELETE FROM stock WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">Stock item deleted successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
                }
            }
        }
    }
}

// Handle URL actions
$url_action = $_GET['action'] ?? '';
$edit_id = $_GET['id'] ?? 0;
$item_to_edit = null;

if ($url_action === 'edit' && $edit_id > 0) {
    // Get item data for editing
    $query = "SELECT id, name, description, unit, quantity, min_quantity FROM stock WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $item_to_edit = $result->fetch_assoc();
    }
}

// Get all stock items
$query = "SELECT s.id, s.name, s.description, s.unit, s.quantity, s.min_quantity,
          COUNT(t.id) as transaction_count,
          CASE WHEN s.quantity <= s.min_quantity THEN 1 ELSE 0 END as low_stock
          FROM stock s
          LEFT JOIN transactions t ON s.id = t.item_id
          GROUP BY s.id
          ORDER BY s.name";
$result = $conn->query($query);
$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Stock</h1>
    </div>
    <form class="row g-3 needs-validation" novalidate method="post" action="index.php?page=stock">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h5><?php echo $item_to_edit ? 'Modifier un produit du stock' : 'Ajouter un produit au stock'; ?></h5>
        </div>
        <input type="hidden" name="action" value="<?php echo $user_to_edit ? 'edit' : 'add'; ?>">
        <?php if ($user_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo $user_to_edit['id']; ?>">
        <?php endif; ?>

        <div class="col-md-12">
            <label for="validationItems" class="form-label">Nom du produit</label>
            <input type="text" class="form-control" id="validationItems" name="name" value="<?php echo $item_to_edit ? htmlspecialchars($item_to_edit['name']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez le nom du produit.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-4">
            <label for="validationItems" class="form-label">Prix Unitaire</label>
            <input type="number" class="form-control" id="validationItems" name="unit" value="<?php echo $item_to_edit ? htmlspecialchars($item_to_edit['unit']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez le prix unitaire du produit.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-4">
            <label for="validationMin" class="form-label">quantité Minimum</label>
            <input type="number" class="form-control" id="validationMin" name="email" name="min_quantity" value="<?php echo $item_to_edit ? $item_to_edit['min_quantity'] : '0'; ?>" min="0" required>
            <div class="invalid-feedback">
                Entrez la quantité minimun que le stock peut contenir pour ce produit.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-4">
            <label for="validationQte" class="form-label">Quantité Entrée</label>
            <input type="number" class="form-control" id="validationQte" name="quantity" value="<?php echo $item_to_edit ? $item_to_edit['quantity'] : '0'; ?>" min="0" required>
            <div class="invalid-feedback">
                Entrez la quantité.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="mb-3 col-md-12">
            <label for="validationTextarea" class="form-label">Description du produit</label>
            <textarea class="form-control" id="validationTextarea" placeholder="Petite description du produit" name="description"><?php echo $item_to_edit ? htmlspecialchars($item_to_edit['description']) : ''; ?></textarea>
            <div class="invalid-feedback">
                Please enter a message in the textarea.
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?php echo $item_to_edit ? 'Modifier le produit' : 'Ajouter le produit'; ?></button>
            <?php if ($item_to_edit): ?>
                <a href="index.php?page=stock" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>


    <!-- stock list -->
    <div class="col my-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Inventaire de Stock</h2>
            </div>
            <div class="card-body">
                <?php if (count($items) > 0): ?>

                    <table class="table table-success table-striped-columns table-bordered table-responsive">
                        <thead>
                            <tr>
                                <th scope="col">Produit</th>
                                <th scope="col">Prix unitaire</th>
                                <th scope="col">Quantité</th>
                                <th scope="col">Min Qte</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr<?php echo $item['low_stock'] ? ' class="low-stock"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo $item['min_quantity']; ?></td>
                                    <td>
                                        <?php if ($item['low_stock']): ?>
                                            <span class="badge text-bg-danger">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?page=stock&action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                                        <?php if ($item['transaction_count'] == 0): ?>
                                            <form method="post" action="index.php?page=stock" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="index.php?page=transactions&action=new&type=in&item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-success">Stock In</a>
                                        <a href="index.php?page=transactions&action=new&type=out&item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-warning">Stock Out</a>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No Stock items found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>