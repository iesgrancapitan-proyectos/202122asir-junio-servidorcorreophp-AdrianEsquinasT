<?PHP 
include_once("../include/funciones.php");
include_once("../include/error.php");
IniciarSesion();

// Solo se admite un registro de usuario desde el dominio email.iesgrancapitan.org
if( !isset($_SERVER['HTTP_REFERER']) ) {
    exit();
}

$origen = $_SERVER['HTTP_REFERER'];
if( strpos($origen, "http://email.iesgrancapitan.org/") != 0 ) {
    exit();
}

if( isset($_POST['operacion']) AND htmlspecialchars($_POST['operacion']) == "registro_usuario" ) {
    $login = htmlspecialchars($_POST['usuario']);
    $usuario = $login . "@email.iesgrancapitan.org";
    $nombre = htmlspecialchars($_POST['nombre']);
    $home = "/var/mail/email.iesgrancapitan.org/$login";
    $maildir = $home . "/Maildir/";
    $clave1 = htmlspecialchars($_POST['clave']);
    $clave2 = htmlspecialchars($_POST['clave2']);
    $perfil = "U";

    if ($clave1 != $clave2) {
        Error("Las claves no coinciden", "registro/registro_usuario.php", "Volver a intentarlo");
    }
    
    $clave_cifrada = password_hash($clave1, PASSWORD_DEFAULT);
    $bd = AbrirConexionBD();

    try {
        $sql = "INSERT INTO usuarios (id, name, crypt, home, maildir, perfil) ";
        $sql.= "VALUES (?,?,?,?,?,?)";
        $bd->begin_transaction();
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("ssssss", $usuario, $nombre, $clave_cifrada, $home, $maildir,$perfil);
        $sentencia_preparada->execute();
        if( $sentencia_preparada->affected_rows == 1 ) {
            $resultado = CrearBuzon( $usuario, $login );
            if( $resultado != 0 ) {
                $bd->rollback();
                switch( $resultado ) {
                    case 1: 
                        Error("Error al añadir el buzón al archivo de buzones", "registro/registro_usuario.php", "Intentarlo de nuevo");
                    case 2:
                        Error("Error al generar la BD de buzones","registro/registro_usuario.php", "Intentarlo de nuevo");    
                    case 3:
                        Error("Error al enviar el mensaje de bienvenida", "registro/registro_usuario.php", "Intentarlo de nuevo");    
                }
            }
            else {    
                // NO hay error. Se confirma la transacción y generamos la respuesta
                $bd->commit();
                EncabezadoHTML("Email.IESGranCapitan.org - Registro de usuario");
                Rotulos();
?>
                <h4 class="titulo_centrado">¡¡Registro de usuario completado!!</h4>               
                <div id="form_autenticacion">
                    <p>El registro del usuario <?=$login?> se ha completado. 
                        Puede ir a <a href="http://email.iesgrancapitan.org">http://email.iesgrancapitan.org</a>
                        para acceder con <?=$usuario?> y autenticarse con su contraseña.
                    </p>
                </div>
<?PHP
                PieHTML();
            }
        }
        else {
            Error("Error de BD: ", "registro/registro_usuario.php", "Intentarlo de nuevo");            
        }
    }
    catch(mysqli_sql_exception $myse) {
        Error("Error de BD: " . $myse->getMessage(), "registro/registro_usuario.php", "Intentarlo de nuevo");
    }
}
else {
    EncabezadoHTML("Email.IESGranCapitan.org - Registro de usuario");
    Rotulos();
?>
    <h4 class="titulo_centrado">Registro de usuario</h4>
    <form method="post" action="registro_usuario.php" id="form_autenticacion">
        <input type="hidden" name="operacion" value="registro_usuario">
        <div class="campo_formulario">
            <label>Usuario</label>
            <input type="text" name="usuario" id="usuario" placeholder="Introduce el usuario" required autofocus style="width:10em;"/>
            @email.iesgrancapitan.org
        </div>
        <div class="campo_formulario">
            <label>Nombre completo</label>
            <input type="text" name="nombre" id="nombre" placeholder="Introduce el nombre completo" required style="width:20em;"/>
        </div>
        <div class="campo_formulario">
            <label>Contraseña</label>
            <input type="password" name="clave" id="clave" placeholder="Introduce la contraseña" required style="width:10em;"/>
        </div>
        <div class="campo_formulario">
            <label>Repite la contraseña </label>
            <input type="password" name="clave2" id="clave2" placeholder="Introduce la contraseña" required style="width:10em;"/>
        </div>
        <button id="nuevo_usuario" type="submit">Crear usuario</button>
        <a class="boton" id="salir" href="http://email.iesgrancapitan.org">Volver</a>
    </form>
<?PHP
    PieHTML();
}
