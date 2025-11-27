<?php
// Handle form submissions for new transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transaction') {
    $item_id = $_POST['item_id'] ?? 0;
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $transaction_type = $_POST['transaction_type'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');

    $error = '';

    // Validate input
    if (empty($item_id) || empty($supplier_id) || empty($transaction_type) || empty($quantity)) {
        $error = 'Please fill all required fields';
    } elseif ($transaction_type !== 'in' && $transaction_type !== 'out') {
        $error = 'Invalid transaction type';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than zero';
    } else {
        // For stock out, check if enough stock is available
        if ($transaction_type === 'out') {
            $query = "SELECT quantity FROM stock WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $current_qty = $result->fetch_assoc()['quantity'];

                if ($current_qty < $quantity) {
                    $error = 'Not enough stock available. Current quantity: ' . $current_qty;
                }
            } else {
                $error = 'Item not found';
            }
        }
    }

    if (empty($error)) {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert transaction record
            $user_id = $_SESSION['user_id'];

            $query = "INSERT INTO transactions (item_id, supplier_id, user_id, type, quantity, transaction_date, notes) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiissss", $item_id, $supplier_id, $user_id, $transaction_type, $quantity, $transaction_date, $notes);
            $stmt->execute();

            // Update stock quantity
            if ($transaction_type === 'in') {
                $query = "UPDATE stock SET quantity = quantity + ? WHERE id = ?";
            } else {
                $query = "UPDATE stock SET quantity = quantity - ? WHERE id = ?";
            }

            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $quantity, $item_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            echo '<div class="alert alert-success">Transaction recorded successfully!</div>';
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }
}

// Get transaction list with pagination
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_clause = "1=1";
$params = [];
$types = "";

// Apply filters if set
if (isset($_GET['filter_item']) && !empty($_GET['filter_item'])) {
    $where_clause .= " AND i.id = ?";
    $params[] = $_GET['filter_item'];
    $types .= "i";
}

if (isset($_GET['filter_supplier']) && !empty($_GET['filter_supplier'])) {
    $where_clause .= " AND s.id = ?";
    $params[] = $_GET['filter_supplier'];
    $types .= "i";
}

if (isset($_GET['filter_type']) && !empty($_GET['filter_type'])) {
    $where_clause .= " AND t.type = ?";
    $params[] = $_GET['filter_type'];
    $types .= "s";
}

if (isset($_GET['filter_date_from']) && !empty($_GET['filter_date_from'])) {
    $where_clause .= " AND DATE(t.transaction_date) >= ?";
    $params[] = $_GET['filter_date_from'];
    $types .= "s";
}

if (isset($_GET['filter_date_to']) && !empty($_GET['filter_date_to'])) {
    $where_clause .= " AND DATE(t.transaction_date) <= ?";
    $params[] = $_GET['filter_date_to'];
    $types .= "s";
}

// Count total records for pagination
$query = "SELECT COUNT(*) as total FROM transactions t 
          JOIN stock i ON t.item_id = i.id
          JOIN suppliers s ON t.supplier_id = s.id
          JOIN users u ON t.user_id = u.id
          WHERE $where_clause";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get transactions
$query = "SELECT t.id, t.transaction_date, t.type, t.quantity, t.notes,
          i.name as item_name, i.unit,
          s.name as supplier_name,
          u.username as user_name
          FROM transactions t 
          JOIN stock i ON t.item_id = i.id
          JOIN suppliers s ON t.supplier_id = s.id
          JOIN users u ON t.user_id = u.id
          WHERE $where_clause
          ORDER BY t.transaction_date DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$types .= "ii";
$params[] = $limit;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Get items and suppliers for filter dropdowns
$query = "SELECT id, name FROM stock ORDER BY name";
$items_result = $conn->query($query);
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

$query = "SELECT id, name FROM suppliers ORDER BY name";
$suppliers_result = $conn->query($query);
$suppliers = [];
while ($row = $suppliers_result->fetch_assoc()) {
    $suppliers[] = $row;
}

// Determine if we're showing a new transaction form
$show_form = false;
$transaction_type = '';
$selected_item = null;

if (isset($_GET['action']) && $_GET['action'] === 'new') {
    $show_form = true;
    $transaction_type = isset($_GET['type']) && in_array($_GET['type'], ['in', 'out']) ? $_GET['type'] : 'in';

    if (isset($_GET['item_id']) && intval($_GET['item_id']) > 0) {
        $item_id = intval($_GET['item_id']);
        $query = "SELECT id, name, quantity FROM stock WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $selected_item = $result->fetch_assoc();
        }
    }
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transaction</h1>
    </div>
    <?php if ($show_form): ?>
        <form class="row g-3 needs-validation" novalidate method="post" action="index.php?page=transactions">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h5>New <?php echo ucfirst($transaction_type); ?> Transaction</h5>
            </div>
            <input type="hidden" name="action" value="add_transaction">
            <input type="hidden" name="transaction_type" value="<?php echo $transaction_type; ?>">

            <div class="col-md-4">
                <label for="validationItem_id" class="form-label">Produit</label>
                <select class="form-select" id="validationItem_id" name="item_id" required>
                    <option value="">Select Item</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>" <?php echo $selected_item && $selected_item['id'] == $item['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    Please select a valid state.
                </div>
                <?php if ($selected_item && $transaction_type === 'out'): ?>
                    <div class="mt-2 text-info">
                        Current quantity in stock: <?php echo $selected_item['quantity']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label for="validationSupplier_id" class="form-label">Fournisseur</label>
                <select class="form-select" id="validationSupplier_id" name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>">
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    Please select a valid state.
                </div>
            </div>
            <div class="col-md-4">
                <label for="validationQte" class="form-label">Quantité</label>
                <input type="number" class="form-control" id="validationQte" name="quantity" min="1" required>
                <div class="invalid-feedback">
                    Entrez la quantité.
                </div>
                <div class="valid-feedback">
                    Bien.
                </div>
            </div>
            <div class="col-md-12">
                <label for="validationDate" class="form-label">Date</label>
                <input type="datetime-local" class="form-control" id="validationDate" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                <div class="invalid-feedback">
                    Entrez la date.
                </div>
                <div class="valid-feedback">
                    Bien.
                </div>
            </div>
            <div class="mb-3 col-md-12">
                <label for="validationNotes" class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="validationNotes"></textarea>
                <div class="invalid-feedback">
                    Please enter a message in the textarea.
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Enregistrer la Transaction</button>
                <a href="index.php?page=transactions" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    <?php endif ?>

    <form class="row g-3 needs-validation" novalidate method="get" action="index.php">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h5>Historique des Transactions</h5>
            <div>
                <a href="index.php?page=transactions&action=new&type=in" class="btn btn-sm btn-success">New Stock In</a>
                <a href="index.php?page=transactions&action=new&type=out" class="btn btn-sm btn-warning">New Stock Out</a>
            </div>
        </div>
        <input type="hidden" name="page" value="transactions">

        <div class="col-md-4">
            <label for="validationItem_filter" class="form-label">Produit</label>
            <select class="form-select" id="validationItem_filter" name="filter_item" required>
                <option value="">All Items</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?php echo $item['id']; ?>" <?php echo isset($_GET['filter_item']) && $_GET['filter_item'] == $item['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($item['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                Please select a valid state.
            </div>
        </div>
        <div class="col-md-4">
            <label for="validationSupplier_filter" class="form-label">Fournisseur</label>
            <select class="form-select" id="validationSupplier_filter" name="filter_supplier" required>
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>" <?php echo isset($_GET['filter_supplier']) && $_GET['filter_supplier'] == $supplier['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                Please select a valid state.
            </div>
        </div>
        <div class="col-md-4">
            <label for="validationType_filter" class="form-label">Type</label>
            <select class="form-select" id="validationType_filter" name="filter_type" required>
                <option value="">All Types</option>
                <option value="in" <?php echo isset($_GET['filter_type']) && $_GET['filter_type'] === 'in' ? 'selected' : ''; ?>>Stock In</option>
                <option value="out" <?php echo isset($_GET['filter_type']) && $_GET['filter_type'] === 'out' ? 'selected' : ''; ?>>Stock Out</option>
            </select>
            <div class="invalid-feedback">
                Please select a valid state.
            </div>
        </div>
        <div class="col-md-6">
            <label for="validationDate_from" class="form-label">Date de:</label>
            <input type="date" class="form-control" id="validationDate_from" name="filter_date_from" value="<?php echo $_GET['filter_date_from'] ?? ''; ?>" required>
            <div class="invalid-feedback">
                Entrez la date.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-6">
            <label for="validationDate_to" class="form-label">Date à:</label>
            <input type="date" class="form-control" id="validationDate_to" name="filter_date_to" value="<?php echo $_GET['filter_date_to'] ?? ''; ?>" required>
            <div class="invalid-feedback">
                Entrez la date.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-6">
            <button type="submit" class="btn btn-primary form-control">Apliquer les Filtres</button>
        </div>
        <div class="col-6">
            <a href="index.php?page=transactions" class="btn btn-secondary form-control">Reinitialiser</a>
        </div>
    </form>

    <!-- list -->
    <div class="col my-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Resultat du filtre</h2>
            </div>
            <div class="card-body">
                <?php if (count($transactions) > 0): ?>
                    <table class="table table-success table-striped-columns table-bordered table-responsive">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Produit</th>
                                <th scope="col">Type</th>
                                <th scope="col">Qte</th>
                                <th scope="col">Fournisseur</th>
                                <th scope="col">Enregistrer par...</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                    <td>
                                        <?php if ($transaction['type'] == 'in'): ?>
                                            <span class="badge text-bg-success">Stock In</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning">Stock Out</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $transaction['quantity'] . ' ' . htmlspecialchars($transaction['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="index.php?page=transactions&p=<?php echo $page - 1; ?>" class="btn btn-sm btn-secondary">&laquo; Previous</a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);

                            for ($i = $start; $i <= $end; $i++): ?>
                                <a href="index.php?page=transactions&p=<?php echo $i; ?>" class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="index.php?page=transactions&p=<?php echo $page + 1; ?>" class="btn btn-sm btn-secondary">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center">No transactions found matching the criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>