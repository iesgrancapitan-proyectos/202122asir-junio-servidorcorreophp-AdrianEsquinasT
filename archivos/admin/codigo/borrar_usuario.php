<?PHP

include_once "../include/funciones.php";
include_once "../include/error.php";
IniciarSesion();

if (!isset($_SESSION['usuario'])) {
    Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

$bd = AbrirConexionBD();

// Presentar e lnombre del usuaro
// Calcular cuanto ocupa su buzón -> du -sh ((campo maildir de la tabla))
// Preguntas si está seguro de borrar. Un formulario con un botón Borrar y otro Cancelar
$id = htmlspecialchars($_GET['id']);
try {
  $sql = "SELECT id, name, maildir FROM usuarios WHERE id = ?";
  $sentencia_preparada = $bd->prepare($sql);
  $sentencia_preparada->bind_param("s", $id);
  $sentencia_preparada->execute();
  $resultado = $sentencia_preparada->get_result();
  if( $sentencia_preparada->affected_rows != 1 ) {
    throw new mysqli_sql_exception("Error al obtener el usuario para borrar");
  }
}
catch( mysqli_sql_exception $myse ) {
  Error("Error BD: " . $myse->getMessage(), "codigo/gestion.php","Volver a la lista de usuarios");
}

// Presentamos los datos
$usuario = $resultado->fetch_assoc();
$tamano = "sudo -u root du -sh " . $usuario['maildir'] ;
$array_resultado = array();
$resultado = 0;
$calcular_tamano = exec($tamano, $array_resultado, $resultado);
if( $resultado != 0 ) {
  $mostrar_tamano = "";
}
else {
  $mostrar_tamano = explode("\t", $calcular_tamano);
  
}

EncabezadoHTML("Gestión de usuarios de correo");
Rotulos();
?>
<h4 class="titulo_centrado">Eliminación de usuario</h4>
<div id="form_autenticacion">
  <form method="POST" action="./gestion.php">
    <input type="hidden" name="operacion" value="borrar">
    <input type="hidden" name="id" value="<?=$usuario['id']?>">
    <div class="campo_formulario">
      <label>Email</label>
      <input type="text" readonly value="<?=$usuario['id']?>" size="<?=strlen($usuario['id'])-5?>">
    </div>
    <div class="campo_formulario">
      <label>Ruta del buzón</label>
      <input type="text" readonly value="<?=$usuario['maildir']?>" size="<?=strlen($usuario['maildir'])-5?>">
    </div>
    <div class="campo_formulario">
      <label>Tamaño del buzón</label>
      <input type="text" readonly value="<?=isset($mostrar_tamano[0]) ? $mostrar_tamano[0] : ""?>" size="7">
    </div>
    <div class="campo_formulario">
      <label>Borrar el buzón</label>
      <input type="checkbox" name="con_buzon" value="1">
    </div> 
    <div class="aviso">
    <span class="negrita">¡Atención, importante! Al borrar el usuario puede conservar el buzón y 
      recuperar los mensajes previos a su borrado si crea un nuevo usuario con el mismo nombre. 
      Si activa la casilla BORRAR EL BUZÓN, estos mensajes se perderan.</span>
    </div>
    <button id="eliminar_usuario" type="submit">Borrar</button>
    <a class="boton" id="salir" href="gestion.php">Cancelar</a>
  </form>
</div>
<?php
PieHTML();
?>