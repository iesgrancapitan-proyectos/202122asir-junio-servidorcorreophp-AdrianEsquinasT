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

if( isset($_POST['operacion']) AND htmlspecialchars($_POST['operacion']) == "cambiar_clave" ) {
    $login = htmlspecialchars($_POST['usuario']);
    $usuario = $login . "@email.iesgrancapitan.org";
    $clave_actual = htmlspecialchars($_POST['clave_actual']);
    $clave1 = htmlspecialchars($_POST['clave']);
    $clave2 = htmlspecialchars($_POST['clave2']);

    if ($clave1 != $clave2) {
        Error("Las claves no coinciden", "registro/cambiar_clave.php", "Volver a intentarlo");
    }
    
    $bd = AbrirConexionBD();

    // Primero comprobamos que la clave actual es correcta
    try {
        $sql = "SELECT crypt FROM usuarios WHERE id = ?";
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("s",$usuario);
        $sentencia_preparada->execute();
        $resultado = $sentencia_preparada->get_result();
        $registro = $resultado->fetch_assoc();

        if( !password_verify($clave_actual, $registro['crypt']) ) {
            Error("Error: La clave actual no es correcta", "registro/cambiar_clave.php", "Volver a intentarlo");
        }
    }catch( mysqli_sql_exception $myse ) {
        Error("Error de BD: " + $myse->getMessage(), "registro/cambiar_clave.php", "Volver a intentarlo");
    }

    // La clave anterior en este punto es correcta. Actualizamos a la nueva.
    $clave_cifrada = password_hash($clave1, PASSWORD_DEFAULT);
    try {
        $sql = "UPDATE usuarios SET crypt = ? WHERE id = ?";
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("ss", $clave_cifrada, $usuario);
        $sentencia_preparada->execute();
        if( $sentencia_preparada->affected_rows == 1 ) {

            // NO hay error. Generamos la respuesta
            EncabezadoHTML("Email.IESGranCapitan.org - Cambio de la clave");
            Rotulos();
?>
            <h4 class="titulo_centrado">¡¡Clave de usuario modificada!!</h4>               
            <div id="form_autenticacion">
                <p>El cambio de la clave  del usuario <?=$login?> se ha completado. 
                    Puede ir a <a href="http://email.iesgrancapitan.org">http://email.iesgrancapitan.org</a>
                    para acceder con <?=$usuario?> y autenticarse con su nueva clave.
                </p>
            </div>
<?PHP
            PieHTML();
        }
        else {
            Error("Error de BD: ", "registro/cambiar_clave.php", "Intentarlo de nuevo");            
        }
    }
    catch(mysqli_sql_exception $myse) {
        Error("Error de BD: " . $myse->getMessage(), "registro/cambiar_clave.php", "Intentarlo de nuevo");
    }
}
else {
    EncabezadoHTML("Email.IESGranCapitan.org - Cambio de la clave de usuario");
    Rotulos();
?>
    <h4 class="titulo_centrado">Cambio de la clave de usuario</h4>
    <form method="post" action="cambiar_clave.php" id="form_autenticacion">
        <input type="hidden" name="operacion" value="cambiar_clave">
        <div class="campo_formulario">
            <label>Usuario</label>
            <input type="text" name="usuario" id="usuario" placeholder="Introduce el usuario" required autofocus style="width:10em;"/>
            @email.iesgrancapitan.org
        </div>
        <div class="campo_formulario">
            <label>Contraseña actual</label>
            <input type="password" name="clave_actual" id="clave_actual" placeholder="Introduce la contraseña" required style="width:10em;"/>
        </div>
        <div class="campo_formulario">
            <label>Nueva contraseña </label>
            <input type="password" name="clave" id="clave" placeholder="Introduce la contraseña" required style="width:10em;"/>
        </div>
        <div class="campo_formulario">
            <label>Repite la contraseña </label>
            <input type="password" name="clave2" id="clave2" placeholder="Introduce la contraseña" required style="width:10em;"/>
        </div>
        <button id="cambiar_clave" type="submit">Cambiar la clave</button>
        <a class="boton" id="salir" href="http://email.iesgrancapitan.org">Volver</a>
    </form>
<?PHP
    PieHTML();
}
