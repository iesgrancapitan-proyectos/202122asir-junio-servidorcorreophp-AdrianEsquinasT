<?php 

include_once("../include/funciones.php");
include_once("../include/error.php");
IniciarSesion();

if( ! isset($_SESSION['usuario']) ) {
  Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

if (isset($_POST['operacion']) and htmlspecialchars($_POST['operacion']) == "insertar") {
    $clave1 = htmlspecialchars($_POST['clave']);
    $clave2 = htmlspecialchars($_POST['clave2']);

    if ($clave1 != $clave2) {
        Error("Las claves no coinciden", "codigo/crear_usuario.php", "Insertar un nuevo usuario");
    }

    $bd = AbrirConexionBD(); 
    $login = htmlspecialchars($_POST['usuario']);
    $usuario = htmlspecialchars($_POST['usuario']) . "@email.iesgrancapitan.org";
    $nombre = htmlspecialchars($_POST['nombre']);
    $administrador = isset($_POST['adm']) ? htmlspecialchars($_POST['adm']) : "U";
    $home = "/var/mail/email.iesgrancapitan.org/$login";
    $maildir = $home . "/Maildir/";
    $clave_cifrada = password_hash($clave1, PASSWORD_DEFAULT);
    
    try {
        $sql = "INSERT INTO usuarios (id, name, home, maildir, perfil, crypt ) ";
        $sql.= "VALUES (?,?,?,?,?,?)";
        $bd->begin_transaction();
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("ssssss", $usuario, $nombre, $home, $maildir, $administrador, $clave_cifrada);
        $sentencia_preparada->execute();
        if( $sentencia_preparada->affected_rows == 1 ) {
            $resultado = CrearBuzon( $usuario, $login );
            if( $resultado != 0 ) {
                $bd->rollback();
                switch( $resultado ) {
                    case 1: Error("Error al crear la copia de seguridad del archivo de buzones", "codigo/crear_usuario.php", "Crear otro usuario");
                    case 2: Error("Error al añadir el buzón al archivo de buzones", "codigo/crear_usuario.php", "Crear otro usuario");
                    case 3: Error("Error al generar la BD de buzones","codigo/crear_usuario.php", "Crear otro usuario");    
                    case 4: Error("Error al enviar el mensaje de bienvenida", "codigo/crear_usuario.php", "Crear otro usuario");    
                }
            }
            else {
                // NO hay error al crear el buzón
                // Se confirma la transacción
                $bd->commit();
            }
        }
        else {
            Error("Error de BD: ", "codigo/crear_usuario.php", "Crear otro usuario");            
        }
    } 
    catch(mysqli_sql_exception $myse) {
        Error("Error de BD: " . $myse->getMessage(), "codigo/crear_usuario.php", "Insertar un nuevo usuario");
    }
}

EncabezadoHTML("Creación de un nuevo buzón de usuario");
Rotulos();
?>
<h4 class="titulo_centrado">Nuevo buzón de usuario</h4>
<div id="form_autenticacion">
<form method="post" action="crear_usuario.php" name="autenicacion">
    <input type="hidden" name="operacion" value="insertar">
    <div class="campo_formulario">
        <label>Usuario</label>
        <input type="text" name="usuario" id="usuario" placeholder="Introduce el usuario" required autofocus style="width:10em;"/>
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
    <div class="campo_formulario">
        <label>Administrador</label>
        <input type="checkbox" name="adm" id="adm" value="A">
    </div>
    <button id="nuevo_usuario" type="submit">Crear usuario</button>
    <a class="boton" id="salir" href="gestion.php">Volver</a>
</form>
</div>
<?PHP
PieHTML();
?>
