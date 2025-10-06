<?php
session_start();

// Incluir configuración de base de datos (debería estar en un archivo separado)
// require_once 'db_connection.php';

// Configuración de base de datos
$host = "localhost";
$dbname = "ganavenezuela.com";
$username = "root";
$password = "Cable0414++";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // En producción, manejar el error de manera más elegante
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
        exit;
    }
    die("Error de conexión a la base de datos");
}

// Manejar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_operators':
            try {
                $stmt = $pdo->prepare("SELECT id, cid, name, hash FROM operators ORDER BY name");
                $stmt->execute();
                $operators = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'operators' => $operators
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'assign_operator':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }
            
            try {
                $operator_id = $_POST['operator_id'] ?? null;
                $order_id = $_POST['order_id'] ?? null;
                
                if (!$operator_id || !$order_id) {
                    throw new Exception('Faltan parámetros requeridos');
                }
                
                // Asignar operador a la orden
                $stmt = $pdo->prepare("UPDATE orders SET operator_id = ? WHERE id = ?");
                $stmt->execute([$operator_id, $order_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No se pudo actualizar la orden. Verifique que el ID sea válido.');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Operador asignado correctamente'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_order_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }
            
            try {
                $order_id = $_POST['order_id'] ?? null;
                $status = $_POST['status'] ?? null;
                
                if (!$order_id || !$status) {
                    throw new Exception('Faltan parámetros requeridos');
                }
                
                // Validar que el estado sea válido
                $valid_statuses = ['pending', 'confirmed', 'delivering', 'completed', 'cancelled'];
                if (!in_array($status, $valid_statuses)) {
                    throw new Exception('Estado no válido');
                }
                
                // Actualizar estado de la orden
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $order_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No se pudo actualizar el estado. Verifique que el ID sea válido.');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Admin - Gana Venezuela</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        
        .operator-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .operator-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .operator-item:hover {
            background-color: #f5f5f5;
        }
        
        .operator-item:last-child {
            border-bottom: none;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Chats</h1>
        
        <!-- Botón de prueba para abrir el modal -->
        <button class="btn" onclick="openAssignOperatorModalForDelivering(123)">
            Abrir Modal de Asignación de Operador (Prueba)
        </button>
        
        <!-- Modal para asignar operador -->
        <div id="assignOperatorModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAssignOperatorModal()">&times;</span>
                <h2>Asignar Operador</h2>
                <p>Seleccione un operador para asignar a esta orden:</p>
                <div id="operatorsList" class="operator-list">
                    <!-- La lista de operadores se cargará aquí via JavaScript -->
                </div>
                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="closeAssignOperatorModal()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentOrderId = null;

        /**
         * Abre el modal para asignar operador en el flujo "En Tránsito" / delivering
         * @param {number} orderId - ID de la orden
         */
        function openAssignOperatorModalForDelivering(orderId) {
            currentOrderId = orderId;
            
            // Mostrar el modal
            document.getElementById('assignOperatorModal').style.display = 'block';
            
            // Cargar la lista de operadores
            loadOperatorsList();
        }
        
        /**
         * Cierra el modal de asignación de operadores
         */
        function closeAssignOperatorModal() {
            document.getElementById('assignOperatorModal').style.display = 'none';
            currentOrderId = null;
        }
        
        /**
         * Carga la lista de operadores desde el servidor
         */
        function loadOperatorsList() {
            fetch('?action=get_operators')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderOperatorsList(data.operators);
                    } else {
                        console.error('Error al cargar operadores:', data.error);
                        alert('Error al cargar la lista de operadores');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al cargar operadores');
                });
        }
        
        /**
         * Renderiza la lista de operadores en el modal
         * @param {Array} operators - Array de operadores
         */
        function renderOperatorsList(operators) {
            const operatorsList = document.getElementById('operatorsList');
            operatorsList.innerHTML = '';
            
            if (operators.length === 0) {
                operatorsList.innerHTML = '<div class="operator-item">No hay operadores disponibles</div>';
                return;
            }
            
            operators.forEach(operator => {
                const operatorDiv = document.createElement('div');
                operatorDiv.className = 'operator-item';
                operatorDiv.onclick = () => selectOperator(operator);
                
                // Determinar qué identificador mostrar: preferir cid, luego hash, luego id
                let identifier = '';
                if (operator.cid && operator.cid.trim() !== '') {
                    identifier = operator.cid;
                } else if (operator.hash && operator.hash.trim() !== '') {
                    identifier = operator.hash;
                } else {
                    identifier = operator.id;
                }
                
                // Formato: "{cid_or_hash} - {name}"
                operatorDiv.textContent = `${identifier} - ${operator.name}`;
                
                // Guardar el ID del operador como atributo de datos
                operatorDiv.setAttribute('data-operator-id', operator.id);
                
                operatorsList.appendChild(operatorDiv);
            });
        }
        
        /**
         * Maneja la selección de un operador
         * @param {Object} operator - Objeto del operador seleccionado
         */
        function selectOperator(operator) {
            if (!currentOrderId) {
                alert('Error: No hay orden seleccionada');
                return;
            }
            
            // Confirmar la asignación
            const identifier = operator.cid || operator.hash || operator.id;
            const confirmMessage = `¿Confirma asignar el operador "${identifier} - ${operator.name}" a esta orden?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Asignar operador
            assignOperatorToOrder(operator.id, currentOrderId);
        }
        
        /**
         * Asigna un operador a una orden
         * @param {number} operatorId - ID del operador
         * @param {number} orderId - ID de la orden
         */
        function assignOperatorToOrder(operatorId, orderId) {
            const formData = new FormData();
            formData.append('operator_id', operatorId);
            formData.append('order_id', orderId);
            
            fetch('?action=assign_operator', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Operador asignado exitosamente, ahora actualizar estado a 'delivering'
                    updateOrderStatusToDelivering(orderId);
                } else {
                    console.error('Error al asignar operador:', data.error);
                    alert('Error al asignar operador: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al asignar operador');
            });
        }
        
        /**
         * Actualiza el estado de la orden a 'delivering'
         * @param {number} orderId - ID de la orden
         */
        function updateOrderStatusToDelivering(orderId) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', 'delivering');
            
            fetch('?action=update_order_status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Operador asignado y estado actualizado correctamente');
                    closeAssignOperatorModal();
                    // Aquí podrías recargar la página o actualizar la vista
                    // location.reload();
                } else {
                    console.error('Error al actualizar estado:', data.error);
                    alert('Operador asignado pero error al actualizar estado: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Operador asignado pero error de conexión al actualizar estado');
            });
        }
        
        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('assignOperatorModal');
            if (event.target === modal) {
                closeAssignOperatorModal();
            }
        }
    </script>
</body>
</html>