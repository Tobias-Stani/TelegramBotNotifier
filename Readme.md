
# Task notifier telegram

Bot de telegram integrado con jira.

Este mismo contiene dos scripts.

dailyMessage:
Este mensaje se envía todos los días a los desarrolladores e incluye una lista de las tareas pendientes que tienen asignadas. El propósito de este mensaje es recordarles las tareas que deben completar durante el día.

weeklyMessage:
Este mensaje se envía una vez por semana y proporciona un resumen de las tareas completadas por cada desarrollador durante la semana anterior. Incluye un recuento de cuántas tareas completó cada desarrollador, y felicita al desarrollador que completó la mayor cantidad de tareas. Además, si hay pocas tareas completadas en general, el mensaje también informa a los desarrolladores y les pregunta si necesitan algún tipo de ayuda o si todo está bien.

Este diseño permite una comunicación efectiva con los desarrolladores, manteniéndolos informados sobre sus tareas pendientes y reconociendo su trabajo y esfuerzo al completarlas. También fomenta un ambiente de apoyo al ofrecer ayuda adicional si es necesario.




## Docker-compose


```bash
version: '3.1'
services:
  php-apache:
    build: Dockerfiles/php-apache/
    volumes: 
      - ./php/:/var/www/html
    ports:
      - $APACHE_PORT_NOTIFIER:8002:80

```



## Base de datos para las creedenciales

Importante, crear una base de datos con una tabla config que contenga los campos = botToken y chatId para almacenar las creedenciales del mismo.





  ## ¡Importante!

Utilizar php 7.4 y mysqli

Configurar el archivo DBconfig.php

En los archivos **`dailyMessage.php`** y **`weeklyMessage.php`**, asegúrate de actualizar los puertos y las consultas SQL para que correspondan a tu entorno específico.




## Configuracion de DBconfig.php

Utilizar credenciales para la conexion a jira (conexion 1) y a la base de datos con las creedenciales del bot de telegram (conexion 2)

```bash
<?php
//Base de datos jira
    define('DB_HOST', '#');
    define('DB_USER', '#');
    define('DB_PASS', '#');
    define('DB_NAME', '#');

//base de datos de configuracion y usuarios telegram
    define('DB_HOST1', '#');
    define('DB_NAME1', '#');
    define('DB_PORT1', '#');
    define('DB_USER1', '#');
    define('DB_PASS1', '#');
?>

```
    
## Iniciar el proyecto

En la carpeta del proyecto donde este el docker-compose ejecutar.

```bash
  sudo docker-compose up -d
```

