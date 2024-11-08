<?php
// Declaraciones de inclusión
require_once(__DIR__ . '/../../models/db.php');
require_once(__DIR__ . '/../../models/booking.php');

// Conectar a la base de datos
$db = db_connect();
if (!$db) {
    die("Error al conectar con la base de datos."); // Detener ejecución si falla la conexión
}

// Crear instancia del modelo
$booking = new Booking($db);

// Verificar si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_destino = $_POST['id_destino'] ?? null;
    $id_reserva = $_POST['id_reserva'];

    // Validación para asegurar que id_destino sea un id_hotel válido
    $validHotelStmt = $db->prepare("SELECT COUNT(*) FROM tranfer_hotel WHERE id_hotel = :id_destino");
    $validHotelStmt->execute([':id_destino' => $id_destino]);
    $isValidHotel = $validHotelStmt->fetchColumn() > 0;

    if (!$isValidHotel) {
        echo "Error: El id_destino no es válido. Debe ser un id_hotel existente.";
        exit; // Terminar la ejecución si el id_destino no es válido
    }

    // Obtener el valor original de tipo_creador_reserva de la base de datos
    $stmt = $db->prepare("SELECT tipo_creador_reserva FROM transfer_reservas WHERE id_reserva = :id_reserva");
    $stmt->execute([':id_reserva' => $id_reserva]);
    $originalTipoCreador = $stmt->fetchColumn();

    if ($originalTipoCreador === false) {
        echo "Error: La reserva no existe.";
        exit;
    }

    // Asignar id_hotel. Usar id_destino si id_hotel no está presente
    $id_hotel = !empty($_POST['id_hotel']) ? $_POST['id_hotel'] : $id_destino;

    // Preparar los datos para la actualización, usando el tipo_creador_reserva original
    $data = [
        'id_reserva' => $id_reserva,
        'localizador' => $_POST['localizador'],
        'id_hotel' => $id_hotel,
        'id_tipo_reserva' => $_POST['id_tipo_reserva'],
        'email_cliente' => $_POST['email_cliente'],
        'fecha_modificacion' => date('Y-m-d H:i:s'),
        'id_destino' => $id_destino,
        'num_viajeros' => $_POST['num_viajeros'],
        'id_vehiculo' => $_POST['id_vehiculo'] ?? 1,
        'tipo_creador_reserva' => $originalTipoCreador // Usar el valor original de tipo_creador_reserva
    ];

    // Agregar campos adicionales según el tipo de reserva
    if ($data['id_tipo_reserva'] == 1) { // Aeropuerto - Hotel
        $data['fecha_entrada'] = $_POST['fecha_entrada'] ?? null;
        $data['hora_entrada'] = $_POST['hora_entrada'] ?? null;
        $data['numero_vuelo_entrada'] = $_POST['numero_vuelo_entrada'] ?? null;
        $data['origen_vuelo_entrada'] = $_POST['origen_vuelo_entrada'] ?? null;
    } elseif ($data['id_tipo_reserva'] == 2) { // Hotel - Aeropuerto
        $data['fecha_vuelo_salida'] = $_POST['fecha_vuelo_salida'] ?? null;
        $data['hora_vuelo_salida'] = $_POST['hora_vuelo_salida'] ?? null;
    }

    // Llamar al método de actualización en el modelo
    $result = $booking->updateBooking($data);

    // Redirigir a la página de reservas si la actualización es exitosa
    if ($result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit; // Finalizar el script tras la redirección
    } else {
        // Mostrar un mensaje en caso de fallo en la actualización
        echo "Error al actualizar la reserva";
    }
}
?>