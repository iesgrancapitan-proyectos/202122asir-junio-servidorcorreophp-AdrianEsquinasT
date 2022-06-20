<?php

include_once "../include/funciones.php";
include_once "../include/error.php";
IniciarSesion();

if (!isset($_SESSION['usuario'])) {
    Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

$bd = AbrirConexionBD();

if (isset($_GET['id'])) {
    $id = htmlspecialchars($_GET['id']);
    try {
        $sql = "SELECT id, name FROM usuarios WHERE id = ?";
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("s", $id);
        $sentencia_preparada->execute();
        $resultado = $sentencia_preparada->get_result();
        if ($sentencia_preparada->affected_rows != 1) {
            throw new mysqli_sql_exception("Error al obtener el usuario");
        } else {
            $usuario = $resultado->fetch_assoc();
            $id = $usuario['id'];
            $nombre = $usuario['name'];
        }
    } catch (mysqli_sql_exception $myse) {
        Error("Error BD: " . $myse->getMessage(), "/codigo/gestion.php", "Volver a la lista de usuarios");
    }
}


EncabezadoHTML("Inicio de sesión");
Rotulos();
?>
<h4 class="titulo_centrado">Actualizar la clave de usuario</h4>
<form method="post" action="gestion.php" id="form_autenticacion">
    <input type="hidden" name="operacion" value="reset_clave">
    <input type="hidden" name="id" value="<?=$id?>">

    <div class="campo_formulario">
        <label>Usuario</label>&nbsp;<?=$nombre?>
    </div>
    <div class="campo_formulario">
        <label>Nueva Contraseña</label>
        <input type="password" name="clave" id="clave" autofocus placeholder="Nueva contraseña" required />
    </div>
    <div class="campo_formulario">
        <label>Repite la contraseña </label>
        <input type="password" name="clave2" id="clave2" placeholder="Repite la contraseña" required />
    </div>
    <button id="cambiar_clave" type="submit">Cambiar clave</button> 
    <a class="boton" id="salir" href="./gestion.php">Volver</a>
</form>
<?PHP
PieHTML();
?>