<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6" href="index.php?page=dashboard">StockFlow</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <input class="form-control form-control-dark w-100 rounded-0 border-0" type="text" placeholder="Search" aria-label="Search">
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="logout.php">Sign out</a>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3 sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="?page=dashboard">
                            <span data-feather="home" class="align-text-bottom"></span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=users">
                            <span data-feather="users" class="align-text-bottom"></span>
                            Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=suppliers">
                            <span data-feather="suppliers" class="align-text-bottom"></span>
                            Fournisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=stock">
                            <span data-feather="stocks" class="align-text-bottom"></span>
                            Stocks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=transactions">
                            <span data-feather="transactions" class="align-text-bottom"></span>
                            Operations/Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=reports">
                            <span data-feather="reports" class="align-text-bottom"></span>
                            Rapports
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>