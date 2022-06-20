<?php 

include_once("../include/funciones.php");
include_once("../include/error.php");
IniciarSesion();

if( ! isset($_SESSION['usuario']) ) {
  Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

if (isset($_GET['id']) ) {
  $bd = AbrirConexionBD();
  $id = htmlspecialchars($_GET['id']);
  
  try {
    $sql = "SELECT id, name, perfil FROM usuarios WHERE id = ?";
    $sentencia_preparada = $bd->prepare($sql);
    $sentencia_preparada->bind_param("s", $id );
    $sentencia_preparada->execute();
    $resultado = $sentencia_preparada->get_result();
    if( $sentencia_preparada->affected_rows != 1 ) {
      throw new mysqli_sql_exception("Error al obtener el usuario");
    }
    else {
      $usuario = $resultado->fetch_assoc();
      $nombre = $usuario['name'];
      $perfil = $usuario['perfil'];
    }
  }
  catch( mysqli_sql_exception $myse ) {
    Error("Error BD: " . $myse->getMessage(), "codigo/gestion.php","Volver a la lista de usuarios");
  }  
}

EncabezadoHTML("Inicio de sesión");
Rotulos();
?>
<h4 class="titulo_centrado">Actualización de usuario</h4>
<form method="post" action="./gestion.php" id="form_autenticacion">
    <input type="hidden" name="operacion" value="modificar">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="campo_formulario">
      <label>Usuario</label>
      <input type="text" value=<?=$id?> readonly>
    </div>
    <div class="campo_formulario">
      <label>Nombre completo</label>
      <input type="text" name="nombre" id="nombre" value="<?=$nombre?>" autofocus required />
    </div>
    <div class="campo_formulario">
      <label>Administrador</label>
      <input type="checkbox" name="adm" id="adm" value="A" <?=$perfil == "A" ? "checked" : ""?>/>
    </div>
    <button id="actualizar_usuario" type="submit">Actualizar datos</button> 
    <a class="boton" id="salir" href="./gestion.php">Cancelar modificación</a>
</form>
</body>
</html>
<?PHP
PieHTML();
?>