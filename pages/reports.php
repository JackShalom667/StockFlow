<?php
// Define default date range if not provided
$default_date_from = date('Y-m-01'); // First day of current month
$default_date_to = date('Y-m-d'); // Today

$date_from = $_GET['date_from'] ?? $default_date_from;
$date_to = $_GET['date_to'] ?? $default_date_to;
$supplier_id = $_GET['supplier_id'] ?? '';
$report_type = $_GET['report_type'] ?? 'summary';

// Prepare report data
$report_data = [];
$total_in = 0;
$total_out = 0;

if ($report_type === 'summary') {
    // Summary report by item
    $query = "SELECT 
                i.id,
                i.name as item_name,
                i.unit,
                SUM(CASE WHEN t.type = 'in' THEN t.quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN t.type = 'out' THEN t.quantity ELSE 0 END) as total_out,
                SUM(CASE WHEN t.type = 'in' THEN t.quantity ELSE -t.quantity END) as net_change
              FROM transactions t
              JOIN stock i ON t.item_id = i.id
              WHERE DATE(t.transaction_date) BETWEEN ? AND ?";

    $params = [$date_from, $date_to];
    $types = "ss";

    if (!empty($supplier_id)) {
        $query .= " AND t.supplier_id = ?";
        $params[] = $supplier_id;
        $types .= "i";
    }

    $query .= " GROUP BY i.id ORDER BY i.name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
        $total_in += $row['total_in'];
        $total_out += $row['total_out'];
    }
} else if ($report_type === 'supplier') {
    // Summary report by supplier
    $query = "SELECT 
                s.id,
                s.name as supplier_name,
                SUM(CASE WHEN t.type = 'in' THEN t.quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN t.type = 'out' THEN t.quantity ELSE 0 END) as total_out,
                COUNT(DISTINCT t.item_id) as item_count,
                COUNT(t.id) as transaction_count
              FROM transactions t
              JOIN suppliers s ON t.supplier_id = s.id
              WHERE DATE(t.transaction_date) BETWEEN ? AND ?";

    $params = [$date_from, $date_to];
    $types = "ss";

    if (!empty($supplier_id)) {
        $query .= " AND t.supplier_id = ?";
        $params[] = $supplier_id;
        $types .= "i";
    }

    $query .= " GROUP BY s.id ORDER BY s.name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
        $total_in += $row['total_in'];
        $total_out += $row['total_out'];
    }
} else if ($report_type === 'detailed') {
    // Detailed transaction report
    $query = "SELECT 
                t.id,
                t.transaction_date,
                t.type,
                t.quantity,
                t.notes,
                i.name as item_name,
                i.unit,
                s.name as supplier_name,
                u.username as user_name
              FROM transactions t
              JOIN stock i ON t.item_id = i.id
              JOIN suppliers s ON t.supplier_id = s.id
              JOIN users u ON t.user_id = u.id
              WHERE DATE(t.transaction_date) BETWEEN ? AND ?";

    $params = [$date_from, $date_to];
    $types = "ss";

    if (!empty($supplier_id)) {
        $query .= " AND t.supplier_id = ?";
        $params[] = $supplier_id;
        $types .= "i";
    }

    $query .= " ORDER BY t.transaction_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
        if ($row['type'] === 'in') {
            $total_in += $row['quantity'];
        } else {
            $total_out += $row['quantity'];
        }
    }
}

// Get suppliers for filter dropdown
$query = "SELECT id, name FROM suppliers ORDER BY name";
$suppliers_result = $conn->query($query);
$suppliers = [];
while ($row = $suppliers_result->fetch_assoc()) {
    $suppliers[] = $row;
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

    <form class="row g-3 needs-validation" novalidate method="get" action="index.php">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Rapports</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Générer</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">Exporter</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary p-0">
                    <select class="form-select form-select-sm" aria-label=".form-select-sm example" name="report_type" onchange="this.form.submit()">
                        <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Par Produits</option>
                        <option value="supplier" <?php echo $report_type === 'supplier' ? 'selected' : ''; ?>>Par Fournisseur</option>
                        <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Transaction détaillé</option>
                    </select>
                </button>
            </div>
        </div>

        <input type="hidden" name="page" value="reports">

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h5>Rapports des mouvement des stocks</h5>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="me-2 px-1 btn btn-sm btn-outline-secondary p-0">
                    <label for="date_from">De:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" required style="border: none;">
                </button>
                <button type="button" class="me-2 px-1 btn btn-sm btn-outline-secondary p-0">
                    <label for="date_to">À</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" required style="border: none;">
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary p-0">
                    <select class="form-select form-select-sm" aria-label=".form-select-sm example" id="supplier_id" name="supplier_id">
                        <option value="">Tout les Fournisseurs</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo $supplier_id == $supplier['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </button>
            </div>
        </div>
    </form>

    <div id="report-to-print">
        <div class="report-header">
            <h3>
                <?php
                if ($report_type === 'summary') {
                    echo 'Rapports des mouvement des stocks par produits';
                } else if ($report_type === 'supplier') {
                    echo 'Rapports des mouvement des stocks par fournisseurs';
                } else {
                    echo 'Rapport des transactions détaillées';
                }
                ?>
            </h3>
            <p>
                Periode: <?php echo date('M d, Y', strtotime($date_from)); ?> à <?php echo date('M d, Y', strtotime($date_to)); ?>
                <?php if (!empty($supplier_id)): ?>
                    <?php
                    foreach ($suppliers as $supplier) {
                        if ($supplier['id'] == $supplier_id) {
                            echo '<br>Fournisseur: ' . htmlspecialchars($supplier['name']);
                            break;
                        }
                    }
                    ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="report-summary row">
            <div class="stats-card col-md">
                <div class="stats-title">Total Stock In</div>
                <div class="stats-value"><?php echo number_format($total_in); ?></div>
            </div>

            <div class="stats-card col-md">
                <div class="stats-title">Total Stock Out</div>
                <div class="stats-value"><?php echo number_format($total_out); ?></div>
            </div>

            <div class="stats-card col-md">
                <div class="stats-title">Net Change</div>
                <div class="stats-value"><?php echo number_format($total_in - $total_out); ?></div>
            </div>
        </div>

        <?php if (count($report_data) > 0): ?>            
            <table class="table table-striped-columns table-bordered table-responsive">
                <thead>
                    <tr>
                        <?php if ($report_type === 'summary'): ?>
                            <th scope="col">Produit</th>
                            <th scope="col">Prix unitaire</th>
                            <th scope="col">Debit</th>
                            <th scope="col">Crédit</th>
                            <th scope="col">Echange Net</th>
                        <?php elseif ($report_type === 'supplier'): ?>
                            <th scope="col">Fournisseur</th>
                            <th scope="col">Débit</th>
                            <th scope="col">Crédit</th>
                            <th scope="col">Produit</th>
                            <th scope="col">Transactions</th>
                        <?php else: ?>
                            <th scope="col">Date</th>
                            <th scope="col">Produit</th>
                            <th scope="col">Type</th>
                            <th scope="col">Quantité</th>
                            <th scope="col">Fournisseur</th>
                            <th scope="col">Utilisateur</th>
                            <th scope="col">Notes</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php if ($report_type === 'summary'): ?>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td><?php echo number_format($row['total_in']); ?></td>
                                <td><?php echo number_format($row['total_out']); ?></td>
                                <td><?php echo number_format($row['net_change']); ?></td>
                            <?php elseif ($report_type === 'supplier'): ?>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?php echo number_format($row['total_in']); ?></td>
                                <td><?php echo number_format($row['total_out']); ?></td>
                                <td><?php echo number_format($row['item_count']); ?></td>
                                <td><?php echo number_format($row['transaction_count']); ?></td>
                            <?php else: ?>
                                <td><?php echo date('M d, Y H:i', strtotime($row['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td>
                                    <?php if ($row['type'] === 'in'): ?>
                                        <span class="badge text-bg-success">Stock In</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning">Stock Out</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['quantity'] . ' ' . htmlspecialchars($row['unit']); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">No data found for the selected criteria.</p>
        <?php endif; ?>
    </div>
</main>