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

// FUNCION QUE ENVIA LOS MENSAJES A TELEGRAM //
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

//////////////////////////////////////////////////
// MENSAJE DIAS RESTANTES PARA FINALIZAR SPRINT //
//////////////////////////////////////////////////

    $sql_sprint = "Consulta que extrae el nombre, la fecha de inicio y la fecha de finalización del último sprint registrado en la tabla de sprints.
                    Convierte las fechas almacenadas en milisegundos en un formato legible.
                    Ordena los resultados por el identificador del sprint de manera descendente y selecciona solo la primera fila.";


    $resultado_sprint = $conexion->query($sql_sprint);

    if ($resultado_sprint->num_rows > 0) {
        $fila_sprint = $resultado_sprint->fetch_assoc();

        // Calcular los días restantes para que termine el sprint
        $endDate = strtotime($fila_sprint["END_DATE"]);
        $hoy = time();
        $diasRestantes = floor(($endDate - $hoy) / (60 * 60 * 24));

        if ($diasRestantes == 1) {
            $mensaje_sprint = "🎉 ¡Mañana termina el sprint '{$fila_sprint["NAME"]}'! 🎉";
        } elseif ($diasRestantes == 0) {
                $mensaje_sprint = "🎉🎉 ¡¡Hoy termina el sprint!! 🎉";
        } else {
            $mensaje_sprint = "⏳ Faltan <b>$diasRestantes días</b> para que termine el sprint '{$fila_sprint["NAME"]}'. ";
            $mensaje_sprint .= "El mismo comenzó el <i>" . $fila_sprint["START_DATE"] . "</i> y terminará el <i>" . $fila_sprint["END_DATE"] . "</i>.";
        }

        $response_sprint = sendMessageToTelegram($mensaje_sprint);

        if (!$response_sprint) {
            echo "Hubo un error al enviar el mensaje sobre el sprint.";
        } else {
            echo "Mensaje sobre el sprint enviado correctamente a Telegram.";
        }
    } else {
        echo "No se encontraron resultados para el sprint.";
    }

//////////////////////////////////////
// TAREAS FINALIZADAS POR CADA UNO ///    
//////////////////////////////////////

    $hoy = date('2024-03-14 H:i:s');  //CAMBIAR FECHA 
    $inicio_semana_pasada = date('Y-m-d H:i:s', strtotime('-1 week', strtotime($hoy)));

    $query = "Consulta que selecciona usuarios de la aplicación y sus incidencias reportadas en Jira de la semana pasada.
                Se unen los datos de la tabla 'app_user' con 'jiraissue' usando la clave de usuario.
                Se filtran las incidencias resueltas entre la semana pasada y la fecha actual.
                Los resultados se ordenan por el ID del usuario, la fecha de resolución de la incidencia (descendente) y el ID de la incidencia (descendente).";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $mensajes_por_usuario = array();

        // Iterar sobre los resultados y agrupar las tareas por usuario
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $lower_user_name = $fila['lower_user_name'];
            $summary = $fila['SUMMARY'];

            // Agregar la tarea al array correspondiente al usuario
            if (!isset($mensajes_por_usuario[$lower_user_name])) {
                $mensajes_por_usuario[$lower_user_name] = array();
            }
            $mensajes_por_usuario[$lower_user_name][] = "Tarea: $summary";
        }

        foreach ($mensajes_por_usuario as $lower_user_name => $tareas) {
            $ultima_tarea = end($tareas);
            $tareas[count($tareas) - 1] = "<b>$ultima_tarea</b>";
            $mensaje = "👨‍💻 $lower_user_name Finalizó las siguientes tareas:\n<pre>";
            $mensaje .= implode("\n", $tareas);
            $mensaje .= "</pre>";


            $response_reporter = sendMessageToTelegram($mensaje);

            if (!$response_reporter) {
                echo "Hubo un error al enviar el mensaje al usuario $lower_user_name.";
            } else {
                echo "Mensaje al usuario $lower_user_name enviado correctamente.";
            }
        }
    } else {
        echo "No se encontraron resultados para la consulta.";
    }

//////////////////////////////////////////////////////
// MENSAJE TOTAL DE TAREAS FINALIZADAS POR CADA UNO //
//////////////////////////////////////////////////////

    $query = "-- Consulta que cuenta el total de tareas reportadas por cada usuario en Jira durante la semana pasada.
                    Se unen los datos de la tabla 'app_user' con 'jiraissue' mediante la clave de usuario, y luego con 'cwd_user' usando el ID de usuario.
                    Se filtran las tareas resueltas entre la semana pasada y la fecha actual.
                    Los resultados se agrupan por el nombre de usuario en minúsculas y el nombre de visualización en minúsculas.
                    Finalmente, se ordenan por el total de tareas en orden descendente.";

    $resultado = mysqli_query($conexion, $query);
    $mensaje = "";

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $mensaje = "📋 Total de tareas finalizadas por cada usuario:\n\n";
    
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $lower_user_name = $fila['lower_user_name'];
            $nombre = utf8_decode($fila['lower_display_name']);
            $total_tareas = $fila['total_tareas'];

            $mensaje .= "\t\t\t👤 @$lower_user_name ($nombre): <b>$total_tareas</b> tareas completadas.\n";
        }
    
        $response_reporter = sendMessageToTelegram($mensaje);
    
        if (!$response_reporter) {
            echo "Hubo un error al enviar el mensaje.";
        } else {
            echo "Mensaje enviado correctamente.";
        }
    } else {
        echo "No se encontraron resultados para la consulta.";
    }


/////////////////////////////
// MENSAJE DE FELICITACION // 
/////////////////////////////

    $query = "Consulta que cuenta el total de tareas reportadas por el usuario con el nombre de visualización en minúsculas más frecuente en Jira durante la semana pasada.
                Se unen los datos de las tablas 'app_user' y 'jiraissue' usando la clave de usuario.
                Se realiza otra unión con la tabla 'cwd_user' basada en el nombre de usuario en minúsculas.
                Se filtran las tareas resueltas entre la semana pasada y la fecha actual.
                Los resultados se agrupan por el nombre de visualización en minúsculas.
                Finalmente, se ordenan por el total de tareas en orden descendente y se selecciona solo la primera fila."; 

    $resultado = mysqli_query($conexion, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        $usuario_destacado = $fila['lower_display_name']; // Cambiado a lower_display_name
        $total_tareas = $fila['total_tareas'];

        $mensaje_personalizado = "🎉👏 Felicitaciones, <strong>" . strtoupper($usuario_destacado) .  "</strong> ! sos el que más tareas pudo completar, con un total de $total_tareas tareas cerradas. ¡Gran trabajo! 🚀🥳";

        $response_reporter = sendMessageToTelegram($mensaje_personalizado);

        if (!$response_reporter) {
            echo "Hubo un error al enviar el mensaje.";
        } else {
            echo "Mensaje enviado correctamente al usuario $usuario_destacado.";
        }
    } else {
        echo "No se encontraron resultados para la consulta.";
    }

///////////////////////////////////////    
//MENSAJE DE ACTIVIDAD EN EL TABLERO //
///////////////////////////////////////

    $query = "Consulta que cuenta el total de tareas no resueltas (es decir, donde la fecha de resolución es nula) para cada usuario en Jira.
                Se unen los datos de la tabla 'app_user' con 'jiraissue' utilizando la clave de usuario.
                Utiliza una unión izquierda para incluir todos los usuarios, incluso aquellos que no han reportado ninguna tarea.
                Filtra las tareas no resueltas y agrupa los resultados por el nombre de usuario en minúsculas.
                Finalmente, selecciona solo los usuarios que tengan menos de 3 tareas no resueltas."; 

    $resultado = mysqli_query($conexion, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $mensaje_personalizado = "🚫 No vemos actividad en los siguientes tableros:\n\n";

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $lower_user_name = $fila['lower_user_name'];
            $mensaje_personalizado .= "👤 @$lower_user_name\n";
        }
        
        $mensaje_personalizado .= "\n¿Esta todo bien?";
        
        $response_reporter = sendMessageToTelegram($mensaje_personalizado);

        if (!$response_reporter) {
            echo "Hubo un error al enviar el mensaje.";
        } else {
            echo "Mensaje enviado correctamente.";
        }
    } else {
        echo "No se encontraron usuarios con menos de 3 tareas completadas.";
    }

mysqli_close($conexion);
mysqli_close($conexion2);
?>