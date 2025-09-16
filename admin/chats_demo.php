<?php
session_start();

// Simular datos de operadores para demo (en producción esto vendría de la base de datos)
$demo_operators = [
    ['id' => 1, 'cid' => '5794', 'name' => 'Daniel Valentino', 'hash' => 'abc123'],
    ['id' => 2, 'cid' => '1234', 'name' => 'María González', 'hash' => 'def456'],
    ['id' => 3, 'cid' => '', 'name' => 'Carlos Ruiz', 'hash' => 'ghi789'],
    ['id' => 4, 'cid' => '9876', 'name' => 'Ana López', 'hash' => ''],
    ['id' => 5, 'cid' => '', 'name' => 'Pedro Silva', 'hash' => '']
];

// Manejar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_operators':
            try {
                // En producción: SELECT id, cid, name, hash FROM operators ORDER BY name
                echo json_encode([
                    'success' => true,
                    'operators' => $demo_operators
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'assign_operator':
            try {
                $operator_id = $_POST['operator_id'] ?? null;
                $order_id = $_POST['order_id'] ?? null;
                
                if (!$operator_id || !$order_id) {
                    throw new Exception('Faltan parámetros requeridos');
                }
                
                // En producción: UPDATE orders SET operator_id = ? WHERE id = ?
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
            try {
                $order_id = $_POST['order_id'] ?? null;
                $status = $_POST['status'] ?? null;
                
                if (!$order_id || !$status) {
                    throw new Exception('Faltan parámetros requeridos');
                }
                
                // En producción: UPDATE orders SET status = ? WHERE id = ?
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
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .operator-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .operator-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 14px;
        }
        
        .operator-item:hover {
            background-color: #f8f9fa;
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
            line-height: 1;
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
            font-size: 14px;
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
        
        .demo-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .demo-info h3 {
            margin-top: 0;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Chats - Demo</h1>
        
        <div class="demo-info">
            <h3>Información de Demo</h3>
            <p>Esta es una demostración del modal para asignar operadores. En el formato se muestra:</p>
            <ul>
                <li><strong>cid</strong> si está disponible y no vacío</li>
                <li><strong>hash</strong> si cid no está disponible pero hash sí</li>
                <li><strong>id</strong> si ni cid ni hash están disponibles</li>
            </ul>
            <p>Formato: <code>{identificador} - {nombre}</code></p>
        </div>
        
        <!-- Botón de prueba para abrir el modal -->
        <button class="btn" onclick="openAssignOperatorModalForDelivering(123)">
            Abrir Modal de Asignación de Operador
        </button>
        
        <!-- Modal para asignar operador -->
        <div id="assignOperatorModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAssignOperatorModal()">&times;</span>
                <h2>Asignar Operador</h2>
                <p>Seleccione un operador para asignar a esta orden en tránsito:</p>
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