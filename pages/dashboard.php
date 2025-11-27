<?php
// Get stats from database
$stats = [];

// Total items in stock
$query = "SELECT SUM(quantity) as total_items FROM stock";
$result = $conn->query($query);
$stats['total_items'] = $result->fetch_assoc()['total_items'] ?? 0;

// Total suppliers
$query = "SELECT COUNT(*) as total_suppliers FROM suppliers";
$result = $conn->query($query);
$stats['total_suppliers'] = $result->fetch_assoc()['total_suppliers'] ?? 0;

// Total users
$query = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($query);
$stats['total_users'] = $result->fetch_assoc()['total_users'] ?? 0;

// Recent transactions
$query = "SELECT t.id, t.transaction_date, t.type, t.quantity, i.name as item_name, 
          s.name as supplier_name
          FROM transactions t
          JOIN stock i ON t.item_id = i.id
          JOIN suppliers s ON t.supplier_id = s.id
          ORDER BY t.transaction_date DESC
          LIMIT 5";
$result = $conn->query($query);
$recent_transactions = [];
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}

// Low stock items
$query = "SELECT id, name, quantity FROM stock WHERE quantity <= min_quantity ORDER BY quantity ASC LIMIT 5";
$result = $conn->query($query);
$low_stock = [];
while ($row = $result->fetch_assoc()) {
    $low_stock[] = $row;
}
?>
<symbol id="chevron-right" viewBox="0 0 16 16">
    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
</symbol>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</i></h1>
    </div>

    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Bienvenue <?php echo $_SESSION['username'] ?? 'User'; ?>!</strong> Consultes ton dashboard.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5 cards">
        <div class="col d-flex align-items-start card">
            <i class="fas fa-boxes fa-2x"></i>
            <div>
                <h3 class="fw-bold mb-0 fs-4">Stock total</h3>
                <p>Quantité totale du stock.</p>
                <h3><?php echo number_format($stats['total_items']) ?></h3>
                <a href="?page=stock" class="icon-link d-inline-flex align-items-center">
                    aller
                    <svg class="bi" width="1em" height="1em">
                        <use xlink:href="#chevron-right" />
                    </svg>
                </a>
            </div>

        </div>
        <div class="col d-flex align-items-start card">
            <i class="fas fa-user-lock fa-2x"></i>
            <div>
                <h3 class="fw-bold mb-0 fs-4">Fournisseurs</h3>
                <p>Nombres total de fournisseurs</p>
                <h3><?php echo number_format($stats['total_suppliers']) ?></h3>
                <a href="?page=suppliers" class="icon-link d-inline-flex align-items-center">
                    aller
                    <svg class="bi" width="1em" height="1em">
                        <use xlink:href="#chevron-right" />
                    </svg>
                </a>
            </div>
        </div>
        <div class="col d-flex align-items-start card">
            <i class="fas fa-users fa-2x"></i>
            <div>
                <h3 class="fw-bold mb-0 fs-4">Utilisateurs</h3>
                <p>Nombres total des Utilisateurs</p>
                <h3><?php echo number_format($stats['total_users']) ?></h3>
                <a href="?page=users" class="icon-link d-inline-flex align-items-center">
                    aller
                    <svg class="bi" width="1em" height="1em">
                        <use xlink:href="#chevron-right" />
                    </svg>
                </a>
            </div>

        </div>
        <div class="col d-flex align-items-start card">
            <i class="fas fa-chart-line fa-2x"></i>
            <div>
                <h3 class="fw-bold mb-0 fs-4">Transaction(s)</h3>
                <p>Toutes les transaction d'aujourd'hui</p>
                <h3>
                    <?php
                    $query = "SELECT COUNT(*) as count FROM transactions 
                              WHERE DATE(transaction_date) = CURDATE()";
                    $result = $conn->query($query);
                    echo $result->fetch_assoc()['count'] ?? 0;
                    ?>
                </h3>
                <a href="?page=transactions" class="icon-link d-inline-flex align-items-center">
                    aller
                    <svg class="bi" width="1em" height="1em">
                        <use xlink:href="#chevron-right" />
                    </svg>
                </a>
            </div>
        </div>
    </div>


    <h2 style="text-decoration:underline;">Etat des élements du stock</h2>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Quantité</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>
                            <a href="index.php?page=transactions&action=new&type=in&item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">Restock</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
        <a href="?page=stock" class="icon-link d-inline-flex align-items-center">
            aller
            <svg class="bi" width="1em" height="1em">
                <use xlink:href="#chevron-right" />
            </svg>
        </a>
    </div>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h5 class="border-bottom pb-2 mb-0">Transaction recentes</h5>
        <?php if (count($recent_transactions) > 0): ?>
            <?php foreach ($recent_transactions as $transaction): ?>
                <div class="d-flex text-muted pt-3">
                    <svg class="bd-placeholder-img flex-shrink-0 me-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 32x32" preserveAspectRatio="xMidYMid slice" focusable="false">
                        <title>Placeholder</title>
                        <rect width="100%" height="100%" fill="#007bff" /><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                    </svg>

                    <p class="pb-3 mb-0 lh-sm border-bottom">
                        <strong class="d-block text-gray-dark"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></strong>
                        <?php if ($transaction['type'] == 'in'): ?>
                            La marchandise appellé "<?php echo htmlspecialchars($transaction['item_name']); ?>" a été <span class="badge text-bg-success">débité</span> par le fournisseur @<?php echo htmlspecialchars($transaction['supplier_name']); ?>. Nouveau stock: <?php echo $transaction['quantity']; ?>
                        <?php else: ?>
                            La marchandise appellé "<?php echo htmlspecialchars($transaction['item_name']); ?>" a été <span class="badge text-bg-danger">crédité</span> par le fournisseur @<?php echo htmlspecialchars($transaction['supplier_name']); ?>. Stock restant: <?php echo $transaction['quantity']; ?>
                        <?php endif; ?>

                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <p class="text-center">Pas de transactions recentes.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

        <?php endif; ?>
        <small class="d-block text-end mt-3">
            <a href="?page=reports">Voir tout</a>
        </small>
    </div>

</main>