<?php
    require_once './functions/DBconfig.php';

    //CONEXION 1//
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, PORT);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    //CONEXION 2 Y CREDENCIALES PARA TELEGRAM//
    $conexion2 = new mysqli(DB_HOST1, DB_USER1, DB_PASS1, DB_NAME1, PORT);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $query_config = "SELECT chatId, botToken FROM config";

    $resultado_config = $conexion2->query($query_config);

    if ($resultado_config->num_rows > 0) {
        $fila_config = $resultado_config->fetch_assoc();

        $chatId = $fila_config['chatId'];
        $botToken = $fila_config['botToken'];
        
    } else {
        echo "No se encontraron resultados en la tabla config.";
    }

    // Función para enviar mensajes a Telegram
    function sendMessageToTelegram($message)
    {
        global $botToken, $chatId;
        $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&parse_mode=HTML&text=" . urlencode($message);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    // Consulta para obtener todas las tareas asignadas a cada usuario
    $query_tareas = //"Consulta que obtiene las tareas asiganadas de los usuarios";

    $resultado_tareas = mysqli_query($conexion, $query_tareas);

    $query_sprint = "SELECT END_DATE FROM AO_60DB71_SPRINT ORDER BY ID DESC LIMIT 1";

    $resultado_sprint = mysqli_query($conexion, $query_sprint);

    // Verificar si hay resultados de sprint y tareas
    if ($resultado_tareas && $resultado_sprint && mysqli_num_rows($resultado_tareas) > 0 && mysqli_num_rows($resultado_sprint) > 0) {
        $fila_sprint = mysqli_fetch_assoc($resultado_sprint);
        $end_date_sprint = $fila_sprint['END_DATE'] / 1000; 
        
        $hoy = time();
        $dias_restantes_sprint = floor(($end_date_sprint - $hoy) / (60 * 60 * 24));
        
        // Mensaje inicial
        $mensaje_inicial = "📅 Estas son las tareas pendientes para la semana\n";
        if ($dias_restantes_sprint == 0) {
            $mensaje_inicial .= "🎉 Hoy termina el sprint!!!! 🥳🎉🎊🍾 \n";
        } elseif ($dias_restantes_sprint == 1) {
            $mensaje_inicial .= "⏰ ¡Apúrate que mañana termina el sprint!\n";
        } else {
            $mensaje_inicial .= "🕒 Faltan $dias_restantes_sprint días para que termine el sprint\n";
            $mensaje_inicial .= "QUE TENGAS UNA GRAN JORNADA!!\n";
        }
        $response_inicial = sendMessageToTelegram($mensaje_inicial);

        if (!$response_inicial) {
            echo "Hubo un error al enviar el mensaje inicial.";
        } else {
            echo "Mensaje inicial enviado correctamente.";
        }

        while ($fila_tareas = mysqli_fetch_assoc($resultado_tareas)) {
            $lower_user_name = $fila_tareas['lower_user_name'];
            $tareas_asignadas = $fila_tareas['tareas_asignadas'];
        
            $tareas_array = explode(", ", $tareas_asignadas);
        
            $mensaje = "Quedan todas estas tareas por delante para ti, @$lower_user_name:\n<pre>";
        
            foreach ($tareas_array as $tarea) {
                $mensaje .= "- Tarea: $tarea\n";
            }
        
            $mensaje .= "</pre>";
            if ($dias_restantes_sprint == 0) {
                $mensaje .= "🎉 Dale que hoy termina el sprint 😎🥳 !!!! (al fin 😓)\n";
            } elseif ($dias_restantes_sprint == 1) {
                $mensaje .= "⏰ ¡Apúrate que mañana termina el sprint!\n";
            } else {
                $mensaje .= "¡Apúrate que faltan $dias_restantes_sprint días para que termine el sprint! 🏃‍♂️💨";
            }

            // Enviar el mensaje a través de Telegram
            $response_reporter = sendMessageToTelegram($mensaje);
        
            if (!$response_reporter) {
                echo "Hubo un error al enviar el mensaje al usuario @$lower_user_name.";
            } else {
                echo "Mensaje enviado correctamente al usuario @$lower_user_name.";
            }
        }
    } else {
        echo "No se encontraron resultados para la consulta de tareas o sprint.";
    }
?>