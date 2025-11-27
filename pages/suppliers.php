<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = $_POST['name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        $error = '';

        // Validate input
        if (empty($name) || empty($contact_person) || empty($email)) {
            $error = 'Please fill all required fields';
        }

        if (empty($error)) {
            if ($action === 'add') {
                // Insert new supplier
                $query = "INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">Supplier added successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
                }
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? 0;

                if ($id > 0) {
                    $query = "UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);

                    if ($stmt->execute()) {
                        echo '<div class="alert alert-success">Supplier updated successfully!</div>';
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
            // Check if supplier has transactions
            $query = "SELECT COUNT(*) as count FROM transactions WHERE supplier_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];

            if ($count > 0) {
                echo '<div class="alert alert-danger">Cannot delete supplier with existing transactions!</div>';
            } else {
                $query = "DELETE FROM suppliers WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">Supplier deleted successfully!</div>';
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
$supplier_to_edit = null;

if ($url_action === 'edit' && $edit_id > 0) {
    // Get supplier data for editing
    $query = "SELECT id, name, contact_person, email, phone, address FROM suppliers WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $supplier_to_edit = $result->fetch_assoc();
    }
}

// Get all suppliers
$query = "SELECT s.id, s.name, s.contact_person, s.email, s.phone, 
          COUNT(t.id) as transaction_count
          FROM suppliers s
          LEFT JOIN transactions t ON s.id = t.supplier_id
          GROUP BY s.id
          ORDER BY s.name";
$result = $conn->query($query);
$suppliers = [];

while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}
?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Fournisseurs</h1>
    </div>

    <form class="row g-3 needs-validation" novalidate method="post" action="index.php?page=suppliers">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h5><?php echo $supplier_to_edit ? 'Modifier le fournisseur' : 'Nouveau Fournisseur'; ?></h5>
        </div>
        <input type="hidden" name="action" value="<?php echo $supplier_to_edit ? 'edit' : 'add'; ?>">
        <?php if ($supplier_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo $supplier_to_edit['id']; ?>">
        <?php endif; ?>

        <div class="col-md-6">
            <label for="validationSupplier" class="form-label">Societe du Fournisseur</label>
            <input type="text" class="form-control" id="validationSupplier" name="name" value="<?php echo $supplier_to_edit ? htmlspecialchars($supplier_to_edit['name']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez le nom de la societe du fournisseur.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>

        <div class="col-md-6">
            <label for="validationPersonContact" class="form-label">Nom du fourniseur</label>
            <input type="text" class="form-control" id="validationPersonContact" name="contact_person" value="<?php echo $supplier_to_edit ? htmlspecialchars($supplier_to_edit['contact_person']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez un Nom.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-6">
            <label for="validationEmail" class="form-label">Email du fourniseur</label>
            <input type="email" class="form-control" id="validationEmail" name="email" value="<?php echo $supplier_to_edit ? htmlspecialchars($supplier_to_edit['email']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez une Email valide.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md-6">
            <label for="validationPhone" class="form-label">Numero de téléphone du fourniseur</label>
            <input type="phone" class="form-control" id="validationPhone" name="phone" value="<?php echo $supplier_to_edit ? htmlspecialchars($supplier_to_edit['phone']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez un numero valide.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="mb-3 col-md-12">
            <label for="validationTextarea" class="form-label">Adresse</label>
            <textarea class="form-control" name="address" id="validationTextarea" placeholder="Entrez l'adresse du fournisseur"><?php echo $supplier_to_edit ? htmlspecialchars($supplier_to_edit['contact_person']) : ''; ?></textarea>
            <div class="invalid-feedback">
                Please enter a message in the textarea.
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?php echo $supplier_to_edit ? 'Modidier le founiseur' : 'Ajouter le fournisseur'; ?></button>
            <?php if ($supplier_to_edit): ?>
                <a href="index.php?page=suppliers" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- suppliers list -->
    <div class="col my-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Liste des Fournisseurs</h2>
            </div>
            <div class="card-body">
                <?php if (count($suppliers) > 0): ?>

                    <table class="table table-success table-striped-columns table-bordered table-responsive">
                        <thead>
                            <tr>
                                <th scope="col">Societe</th>
                                <th scope="col">Nom</th>
                                <th scope="col">Email</th>
                                <th scope="col">Numero</th>
                                <th scope="col">Transactions</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                    <td><?php echo $supplier['transaction_count']; ?></td>
                                    <td>
                                        <a href="index.php?page=suppliers&action=edit&id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                                        <?php if ($supplier['transaction_count'] == 0): ?>
                                            <form method="post" action="index.php?page=suppliers" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No Suppliers found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>