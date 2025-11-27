<?php
// Check if user has admin privileges
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $error = '';

        // Validate input
        if (empty($username) || empty($email) || empty($role)) {
            $error = 'Please fill all required fields';
        } elseif ($action === 'add' && (empty($password) || empty($confirm_password))) {
            $error = 'Please provide and confirm password';
        } elseif ($action === 'add' && $password !== $confirm_password) {
            $error = 'Passwords do not match';
        }

        if (empty($error)) {
            if ($action === 'add') {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">User added successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
                }
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? 0;

                if ($id > 0) {
                    if (!empty($password)) {
                        // Update user with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $id);
                    } else {
                        // Update user without changing password
                        $query = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssi", $username, $email, $role, $id);
                    }

                    if ($stmt->execute()) {
                        echo '<div class="alert alert-success">User updated successfully!</div>';
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
            // Prevent deleting own account
            if ($id == $_SESSION['user_id']) {
                echo '<div class="alert alert-danger">You cannot delete your own account!</div>';
            } else {
                $query = "DELETE FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">User deleted successfully!</div>';
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
$user_to_edit = null;

if ($url_action === 'edit' && $edit_id > 0) {
    // Get user data for editing
    $query = "SELECT id, username, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_to_edit = $result->fetch_assoc();
    }
}

// Get all users
$query = "SELECT id, username, email, role FROM users ORDER BY username";
$result = $conn->query($query);
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Utilisateurs</h1>
    </div>

    <form class="row g-3 needs-validation" novalidate action="?page=users" method="post" id="#user-form">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h5><?php echo $user_to_edit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'; ?></h5>
        </div>
        <input type="hidden" name="action" value="<?php echo $user_to_edit ? 'edit' : 'add'; ?>">
        <?php if ($user_to_edit): ?>
            <input type="hidden" name="id" value="<?php echo $user_to_edit['id']; ?>">
        <?php endif; ?>

        <div class="col-md-6">
            <label for="validationCustomUsername" class="form-label">Username</label>
            <div class="input-group has-validation">
                <span class="input-group-text" id="inputGroupPrepend">@</span>
                <input type="text" class="form-control" id="validationCustomUsername" aria-describedby="inputGroupPrepend" name="username" value="<?php echo $user_to_edit ? htmlspecialchars($user_to_edit['username']) : ''; ?>" required>
                <div class="invalid-feedback">
                    Please choose a username.
                </div>
                <div class="valid-feedback">
                    Bien.
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label for="validationEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="validationEmail" name="email" value="<?php echo $user_to_edit ? htmlspecialchars($user_to_edit['email']) : ''; ?>" required>
            <div class="invalid-feedback">
                Entrez une adresse email valide.
            </div>
            <div class="valid-feedback">
                Bien.
            </div>
        </div>
        <div class="col-md">
            <label for="validationRole" class="form-label">rôle</label>
            <select class="form-select" id="validationRole" name="role" required>
                <option value="user" <?php echo $user_to_edit && $user_to_edit['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo $user_to_edit && $user_to_edit['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
            <div class="invalid-feedback">
                Please select a valid state.
            </div>
        </div>
        <div class="col-md">
            <label for="validationPassword" class="form-label"><?php echo $user_to_edit ? 'New Password (leave blank to keep current)' : 'Password'; ?></label>
            <input type="password" class="form-control" id="validationPassword" name="password" <?php echo $user_to_edit ? '' : 'required'; ?>>
            <div class="invalid-feedback">
                Entrer un mot de passe.
            </div>
        </div>
        <div class="col-md">
            <label for="validationConfirmPassword" class="form-label"><?php echo $user_to_edit ? 'Confirm New Password' : 'Confirm Password'; ?></label>
            <input type="password" class="form-control" id="validationConfirmPassword" name="confirm_password" <?php echo $user_to_edit ? '' : 'required'; ?>>
        </div>
        <div class="col-12">
            <button class="btn btn-primary col-md" type="submit"><?php echo $user_to_edit ? 'Modifier l\'utilisateur' : 'Ajouter l\'Utilisateur'; ?></button>
            <?php if ($user_to_edit): ?>
                <a href="index.php?page=users" class="btn btn-secondary col-md">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- list users -->
    <div class="col my-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Liste des Utilisateurs</h2>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>

                    <table class="table table-success table-striped-columns table-bordered table-responsive">
                        <thead>
                            <tr>
                                <th scope="col">Username</th>
                                <th scope="col">Email</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td>
                                        <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">Editer</a>

                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="post" action="index.php?page=users" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>