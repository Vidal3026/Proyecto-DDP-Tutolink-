<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">General</div>
            <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                Dashboard
            </a>

            <!--Gestión-->
            <div class="sb-sidenav-menu-heading">Gestión</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsuarios"
                aria-expanded="false" aria-controls="collapseUsuarios">
                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                Gestión de Usuarios
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseUsuarios" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="GestionarUsuarios.php">Gestionar Usuarios</a>
                    <a class="nav-link" href="Reseñas.php">Ver Perfiles</a>
                    <a class="nav-link" href="Reseñas.php">Ver Reseñas</a>
                </nav>
            </div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTutorias"
                aria-expanded="false" aria-controls="collapseTutorias">
                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                Gestión de Tutorías
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseTutorias" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="VerTutorias.php">Todas las Tutorías</a>
                    <a class="nav-link" href="HorariosGlobales.php">Horarios Globales</a>
                    <a class="nav-link" href="Incidencias.php">Incidencias</a>
                </nav>
            </div>

            <!--Estadística-->
            <div class="sb-sidenav-menu-heading">Estadísticas</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseEstadisticas"
                aria-expanded="false" aria-controls="collapseEstadisticas">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                Estadísticas/Reportes
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseEstadisticas" aria-labelledby="headingOne"
                data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="UsoPlataforma.php">Uso de Plataforma</a>
                    <a class="nav-link" href="ActividadTutorias.php">Actividad de Tutorías</a>
                    <a class="nav-link" href="Finanzas.php">Finanzas</a>
                </nav>
            </div>

            <!--Configuración-->
            <div class="sb-sidenav-menu-heading">Configuración</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseConfiguracion"
                aria-expanded="false" aria-controls="collapseConfiguracion">
                <div class="sb-nav-link-icon"><i class="fas fa-cog"></i></div>
                Ajustes del Sistema
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseConfiguracion" aria-labelledby="headingOne"
                data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="Notificaciones.php">Notificaciones</a>
                    <a class="nav-link" href="RolesPermisos.php">Roles y Permisos</a>
                </nav>
            </div>

            <!--Herramientas-->
            <div class="sb-sidenav-menu-heading">Herramientas</div>
            <a class="nav-link" href="Calendario.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-calendar"></i></div>
                Calendario General
            </a>
            <a class="nav-link" href="Mensajes.php">
                <div class="sb-nav-link-icon"><i class="fas fa-comments"></i></div>
                Mensajes / Chats
            </a>
        </div>
    </div>
    <center>
        <div class="sb-sidenav-footer">
            <div class="small">Inicio de sesión como:</div>
            <b>Administrador</b>
        </div>
    </center>
</nav>