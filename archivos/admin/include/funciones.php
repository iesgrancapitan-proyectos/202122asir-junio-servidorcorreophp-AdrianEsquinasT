<?PHP
include_once("error.php");

function EncabezadoHTML($titulo)
{ ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="/admin/css/formato.css">
    <title><?=$titulo?></title>
  </head>
  <body>
  <?PHP
}
function PieHTML()
{ ?>
  </body>

  </html>
<?PHP
}
function IniciarSesion()
{
  Header("X-Frame-Options:SAMEORIGIN");
  session_start();
  ini_set("open_basedir","/var/www/html/admin/tmp/");
  define("__DIRECTORIO_BASE__", "/var/www/html/admin/tmp");
  
  date_default_timezone_set('Europe/Madrid');
}

function AbrirConexionBD()
{
  $c = new mysqli("127.0.0.1", "courier", "courier", "courier");
  if (!$c->connect_errno) {
    $controlador = new mysqli_driver();
    $controlador->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    return $c;
  } else {
    echo $c->connect_error;
    return null;
  }
}

function LoginUsuario( $login, $clave ) { 
  $c = AbrirConexionBD();
  if( ! $c ) {
    Error("No se ha podido abrir conexión con la BD","index.php","Volver al inicio");
  }

  try {
    $sql = "SELECT id, name, clear, crypt, perfil FROM usuarios WHERE id = ?";
    $sentencia_prep = $c->prepare($sql);
    $login .= "@email.iesgrancapitan.org";
    $sentencia_prep->bind_param("s", $login);
    $sentencia_prep->execute();
    $resultado = $sentencia_prep->get_result();
  
    if( $usuario = $resultado->fetch_assoc() ) {
      $clavebd = $usuario['crypt'];
      if( $usuario['perfil'] == "A" ) {
        if( password_verify($clave, $clavebd) ) {
          // Variable sesión para verificar en el resto de páginas
          // que un usuario administrador ha abierto sesión.
          $_SESSION['usuario'] = 'OK';
          return TRUE;
        }
        else {
          Error("Error al abrir sesión. La clave no es correcta","index.php","Volver al inicio");
        }
      }
      else {
        Error("Error al abrir sesión. El usuario no es administrador","index.php","Volver al inicio");
      }      
    }
    else {
      Error("Error al abrir sesión. El usuario no existe","index.php","Volver al inicio");
    }
  }catch (mysqli_sql_exception $mse ) {
    Error("Error de BD: " . $mse->getMessage(), "index.php","Volver al inicio");
  }
}

function Rotulos() { ?>
    <h2 class="titulo_centrado">Email IESGranCapitan.org</h2>
    <h3 class="titulo_centrado">Administración de usuarios</h3>
<?PHP
}

/*
Función:      CrearBuzon()

Descripción:  Se crea mediante comandos del SO un buzón de usuario que consiste en:
              - Añadir el buzón en el archivo /etc/postfix/vmailbox
              - Generar el postmap del archivo /etc/postfix/vmailbox
              - Enviar un mensaje de bienvenida por el postmaster al nuevo usuario
                del buzón para que se cree su directorio en /var/mail/DOMINIO
*/
function CrearBuzon( $usuario, $login ) {

  // Creamos copias de seguridad de vmailbox por si fallara alguno
  // de los comandos siguientes
  if( !CrearCopiaFicheroBuzones() ) {
    return 1;
  }

  // Se añade el buzón en /etc/postfix/vmailbox 
  $comando_insertar_buzon = "sudo -u root sed -i -e '" . "$" . "a" . $usuario . "\t\t\temail.iesgrancapitan.org/$login/Maildir/' /etc/postfix/vmailbox";
  $array_resultado = array();
  $resultado = 0;
  exec($comando_insertar_buzon, $array_resultado, $resultado );
  if ($resultado != 0 ) {
    RecuperarCopiaFicheroBuzones();
    return 2;
  }

  // Se genera el postmap del archivo /etc/postfix/vmailbox
  $resultado = 0;
  unset($array_resultado);
  exec("sudo -u root /usr/sbin/postmap /etc/postfix/vmailbox", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    RecuperarCopiaFicheroBuzones();
    return 3;
  }
            
  // Enviamos un mensaje para crear el buzón.
  $to = $usuario;
  $subject = "Bienvenido al email del IES Gran Capitán";
  $mensaje = "Hola, {$login}\r\n";
  $mensaje.= "Te damos la bienvenida a nuestro email.\r\nSaludos";
  $cabeceras = "Content-type: text/html; charset=utf-8\r\n";
  $cabeceras.= "From: postmaster@email.iesgrancapitan.org\r\n";
  $cabeceras.= "Reply-To: postmaster@email.iesgrancapitan.org";
  if( !mail($to, $subject, $mensaje, $cabeceras ) ) {
    RecuperarCopiaFicheroBuzones();
    return 4;
  }
  
  // Todos los comandos se han ejecutado con éxito
  return 0;
}

/*
Función:      BorrarBuzon()

Descripción:  Se elimina mediante comandos del SO un buzón de usuario que consiste en:
              - Quitar el buzón en el archivo /etc/postfix/vmailbox
	      - Generar el postmap del archivo /etc/postfix/vmailbox
	      
Parámetros:   - $usuario -> Id del usuario a borrar
	      - $con_buzon -> Lógico. Indica si se borra el directorio del buzón del usuario
*/

function BorrarBuzon($usuario, $con_buzon) {

  if( !CrearCopiaFicheroBuzones() ) {
    return 1;
  }

  // Buscamos la línea del archivo /etc/postfix/vmailbox que contiene
  // el usuario a borrar
  $comando_linea= "grep -n {$usuario} /etc/postfix/vmailbox | cut -f1 -d:";
  $array_resultado = array();
  $resultado = 0;
  $linea = exec($comando_linea, $array_resultado, $resultado );
  if ($resultado != 0 OR $linea == "0" ) {
    RecuperarCopiaFicheroBuzones();  
    return 2;
  }

  // Borramos la línea del archivo /etc/postfix/vmailbox encontrada antes
  $comando_borrar_buzon = "sudo -u root sed -i '{$linea}d' /etc/postfix/vmailbox";
  $array_resultado = array();
  $resultado = 0;
  exec($comando_borrar_buzon, $array_resultado, $resultado );
  if ($resultado != 0 ) {
    RecuperarCopiaFicheroBuzones();
    return 3;
  }

  // Generamos el postmap del archivo /etc/postfix/vmailbox
  $resultado = 0;
  unset($array_resultado);
  exec("sudo -u root postmap /etc/postfix/vmailbox", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    RecuperarCopiaFicheroBuzones(); 
    return 4;
  }

  // Borramos el directorio del buzón si se incluye
  if( $con_buzon ) {
    $resultado = 0;
    unset($array_resultado);
    $email = explode("@", $usuario);
    $login = $email[0];
    exec("sudo -u root rm -r /var/mail/email.iesgrancapitan.org/{$login}", $array_resultado, $resultado );
    if( $resultado != 0 ) {
      RecuperarCopiaFicheroBuzones();
      return 5;
    }
  }
  // Todo los comandos se han ejecutado con éxito
  return 0;
}

function CrearCopiaFicheroBuzones() {
  $resultado = 0;
  $array_resultado = array();
  exec("cp /etc/postfix/vmailbox /tmp/vmailbox.bak", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    return FALSE;
  }
  $resultado = 0;
  unset($array_resultado);
  exec("cp /etc/postfix/vmailbox.db /tmp/vmailbox.db.bak", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    return FALSE;
  }
  return TRUE;
}

function RecuperarCopiaFicheroBuzones() {
  $resultado = 0;
  $array_resultado = array();
  exec("sudo -u root cp /tmp/vmailbox.bak /etc/postfix/vmailbox", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    return FALSE;
  }
  $resultado = 0;
  unset($array_resultado);
  exec("sudo -u root cp /tmp/vmailbox.db.bak /etc/postfix/vmailbox.db", $array_resultado, $resultado );
  if( $resultado != 0 ) {
    return FALSE;
  }
  return TRUE;
}
?>
