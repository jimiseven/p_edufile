<?php
// Obtener la página actual
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar p-3" style="background-color: #000; color: #fff; min-width: 250px;">
    <h3 class="text-center">EduFile</h3>
    <nav class="nav flex-column">
        <!-- Enlace para Inicio -->
        <a href="index.php" class="nav-link text-white <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i> Inicio
        </a>

        <!-- Menú desplegable para Niveles -->
        <div>
            <a class="nav-link text-white" data-bs-toggle="collapse" href="#nivelMenu" role="button" aria-expanded="false" aria-controls="nivelMenu">
                <i class="bi bi-box"></i> Niveles
            </a>
            <div class="collapse ms-3" id="nivelMenu">
                <a href="inicialCursos.php" class="nav-link text-white"><i class="bi bi-circle"></i> Inicial</a>
                <a href="primariaCursos.php" class="nav-link text-white"><i class="bi bi-circle"></i> Primaria</a>
                <a href="secundariaCursos.php" class="nav-link text-white"><i class="bi bi-circle"></i> Secundaria</a>
            </div>
        </div>

        <!-- Enlace para Estudiantes -->
        <a class="nav-link text-white <?= ($currentPage == 'estudiantes.php') ? 'active' : '' ?>" href="estudiantes.php">
            <i class="bi bi-people"></i> Estudiantes
        </a>

        <!-- Enlace para Imprimir Listas - Respaldo -->
        <div class="mt-3">
            <a class="nav-link text-white" href="#" data-bs-toggle="modal" data-bs-target="#listOptionsModal">
                <i class="bi bi-printer"></i> Imprimir Listas - Respaldo
            </a>
        </div>

        <!-- Enlace para Log acciones -->
        <div class="mt-3">
            <a class="nav-link text-white <?= ($currentPage == 'registroLog.php') ? 'active' : '' ?>" href="registroLog.php">
                <i class="bi bi-file-earmark-text-fill"></i> Log acciones
            </a>
        </div>
    </nav>

    <!-- Modal for List Options -->
    <div class="modal fade" id="listOptionsModal" tabindex="-1" aria-labelledby="listOptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="listOptionsModalLabel" style="color: black;">Opciones de Listas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="color: black;">
                    <p>Seleccione el tipo de lista que desea imprimir:</p>
                    <div class="d-grid gap-2">
                        <a href="imprimirListas.php" class="btn btn-info" target="_blank">Listas Completas</a>
                        <a href="imprimirListas2.php" class="btn btn-info" target="_blank">Listas de Efectivos</a>
                        <a href="listExcel.html" class="btn btn-info" target="_blank">Listas Excel</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
