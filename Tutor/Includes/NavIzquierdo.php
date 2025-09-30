<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">General</div>
            <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                Dashboard
            </a>
            <div class="sb-sidenav-menu-heading">Tutorías</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts"
                aria-expanded="false" aria-controls="collapseLayouts">
                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                Mis tutorías
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="VerTutorias.php">Ver todas</a>
                    <a class="nav-link" href="AgendarTutoria.php">Crear una nueva</a>
                </nav>
            </div>
            <a class="nav-link" href="Solicitudes.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-inbox"></i></div>
                Solicitudes Recibidas
            </a>
            <a class="nav-link" href="Estadistica.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-chart-column"></i></div>
                Estadística
            </a>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages"
                aria-expanded="false" aria-controls="collapsePages">
                <div class="sb-nav-link-icon"><i class="fas fa-plus-circle"></i></div>
                Aprendizaje
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                    <!-- Tutores Disponibles -->
                    <a class="nav-link" href="MaterialComp.php">
                        Material Compartido
                    </a>

                    <!-- Mis Calificaciones -->
                    <a class="nav-link" href="Progreso.php">
                        Progreso
                    </a>
                </nav>
            </div>
            <div class="sb-sidenav-menu-heading">Herramientas</div>
            <a class="nav-link" href="Calendario.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-calendar"></i></div>
                Calendario
            </a>
            <a class="nav-link" href="Mensajes.php">
                <div class="sb-nav-link-icon"><i class="fas fa-comments"></i></div>
                Mensajes / Chats
            </a>
            <a class="nav-link" href="Configurar_perfil.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-gear"></i></div>
                Configurar Perfil
            </a>
        </div>
    </div>
    <center>
        <div class="sb-sidenav-footer">
            <div class="small">Inicio de sesión como:</div>
            <b>Tutor</b>
        </div>
    </center>

</nav>