<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Estudiante</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <style>
        input[type="text"],
        select {
            text-transform: uppercase;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        /* Pop-up de confirmación */
        .alert-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            background-color: #28a745;
            color: #fff;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .alert-popup.show {
            opacity: 1;
            visibility: visible;
        }

        /* Estilo para el modal de errores */
        .error-text {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body style="background-color: #1E2A38; color: #ffffff;">
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>


        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-3" style="max-width: 1200px; margin: auto;">
            <h2 class="mb-3">Formulario de Registro de Estudiantes</h2>
            <form id="studentForm" action="guardarNuevoEstudiante.php" method="POST" onsubmit="handleSubmit(event)">
                <!-- Información Académica -->
                <div class="mb-3 p-3" style="background-color: #2C3E50; border-radius: 10px;">
                    <h4 class="mb-2">Información Académica</h4>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="nivel" class="form-label">Nivel</label>
                            <select class="form-select" id="nivel" name="nivel" onchange="actualizarCursos()" required>
                                <option value="">Seleccione</option>
                                <option value="Inicial">Inicial</option>
                                <option value="Primario">Primario</option>
                                <option value="Secundario">Secundario</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="curso" class="form-label">Curso</label>
                            <select class="form-select" id="curso" name="curso" required>
                                <option value="">Seleccione Nivel Primero</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="paralelo" class="form-label">Paralelo</label>
                            <select class="form-select" id="paralelo" name="paralelo" required>
                                <option value="">Seleccione</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Información del Estudiante -->
                <div class="mb-3 p-3" style="background-color: #2C3E50; border-radius: 10px;">
                    <h4 class="mb-2">Información del Estudiante</h4>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="last_name_father" class="form-label">A Paterno</label>
                            <input type="text" class="form-control" id="last_name_father" name="last_name_father">
                        </div>
                        <div class="col-md-4">
                            <label for="last_name_mother" class="form-label">A Materno</label>
                            <input type="text" class="form-control" id="last_name_mother" name="last_name_mother">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label for="identity_card" class="form-label">CI</label>
                            <input type="text" class="form-control" id="identity_card" name="identity_card"
                                placeholder="Opcional">
                        </div>
                        <div class="col-md-3">
                            <label for="gender" class="form-label">Sexo</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Seleccione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="birth_date" class="form-label">Fecha Nacimiento</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>
                        <div class="col-md-3">
                            <label for="rude_number" class="form-label">RUDE</label>
                            <input type="text" class="form-control" id="rude_number" name="rude_number"
                                placeholder="Opcional">
                        </div>
                    </div>
                </div>
                <!-- Información del Responsable -->
                <div class="mb-3 p-3" style="background-color: #2C3E50; border-radius: 10px;">
                    <h4 class="mb-2">Información del Responsable (Opcional)</h4>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="guardian_first_name" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="guardian_first_name" name="guardian_first_name">
                        </div>
                        <div class="col-md-4">
                            <label for="guardian_last_name" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="guardian_last_name" name="guardian_last_name">
                        </div>
                        <div class="col-md-4">
                            <label for="guardian_identity_card" class="form-label">CI</label>
                            <input type="text" class="form-control" id="guardian_identity_card"
                                name="guardian_identity_card">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label for="guardian_phone_number" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="guardian_phone_number"
                                name="guardian_phone_number">
                        </div>
                        <div class="col-md-6">
                            <label for="guardian_relationship" class="form-label">Relación</label>
                            <select class="form-select" id="guardian_relationship" name="guardian_relationship">
                                <option value="">Seleccione</option>
                                <option value="padre">Padre</option>
                                <option value="madre">Madre</option>
                                <option value="tutor">Tutor</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="action-buttons">
                    <a href="estudiantes.php" class="btn btn-back">Atrás</a>
                    <button type="submit" class="btn btn-success">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pop-up de confirmación -->
    <div id="alertPopup" class="alert-popup">Estudiante registrado correctamente.</div>

    <!-- Modal de Duplicados -->
    <div class="modal fade" id="duplicadoModal" tabindex="-1" aria-labelledby="duplicadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="duplicadoModalLabel" style="color: black;">Error de Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body error-text" id="duplicadoModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        function actualizarCursos() {
            const nivel = document.getElementById('nivel').value;
            const curso = document.getElementById('curso');
            curso.innerHTML = '<option value="">Seleccione</option>';

            if (nivel === 'Inicial') {
                curso.innerHTML += '<option value="1">1</option><option value="2">2</option>';
            } else if (nivel === 'Primario' || nivel === 'Secundario') {
                for (let i = 1; i <= 6; i++) {
                    curso.innerHTML += `<option value="${i}">${i}</option>`;
                }
            }
        }

        function validateForm() {
            const lastNameFather = document.getElementById('last_name_father').value;
            const lastNameMother = document.getElementById('last_name_mother').value;

            if (!lastNameFather && !lastNameMother) {
                alert('Debe ingresar al menos un apellido: paterno o materno.');
                return false;
            }

            return true;
        }

        function verificarDuplicados(callback) {
            const identity_card = document.getElementById('identity_card').value;
            const rude_number = document.getElementById('rude_number').value;

            fetch('verificarEstudiante.php', {
                method: 'POST',
                body: new URLSearchParams(`identity_card=${identity_card}&rude_number=${rude_number}`)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'error') {
                        const modalBody = document.getElementById('duplicadoModalBody');
                        modalBody.textContent = data.message;
                        const modal = new bootstrap.Modal(document.getElementById('duplicadoModal'));
                        modal.show();
                    } else if (callback) {
                        callback();
                    }
                });
        }

        function handleSubmit(event) {
            event.preventDefault(); // Prevenir envío estándar del formulario

            verificarDuplicados(() => {
                const form = document.getElementById('studentForm');

                // Simular el envío del formulario
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                })
                    .then(response => {
                        if (response.ok) {
                            const alertPopup = document.getElementById('alertPopup');
                            alertPopup.classList.add('show');

                            // Redirigir después de 0.8 segundos
                            setTimeout(() => {
                                window.location.href = 'estudiantes.php';
                            }, 800);
                        } else {
                            alert('Ocurrió un error al registrar el estudiante.');
                        }
                    })
                    .catch(() => alert('Ocurrió un error al registrar el estudiante.'));
            });
        }
    </script>
</body>

</html>