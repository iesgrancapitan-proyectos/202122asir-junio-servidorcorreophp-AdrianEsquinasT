
<?php
include_once("../include/funciones.php");
include_once("../include/error.php");
IniciarSesion();

if( ! isset($_SESSION['usuario']) ) {
  Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

$bd = AbrirConexionBD();

// Operación de borrado
if( isset($_POST['operacion']) ) {
  $operacion = htmlspecialchars($_POST['operacion']);
  if( $operacion == "borrar" ) {
    $id = htmlspecialchars($_POST['id']);
    $con_buzon = isset($_POST['con_buzon']) ? TRUE : FALSE;
    try {
      $sql = "DELETE FROM usuarios WHERE id = ?";
      $sentencia_preparada = $bd->prepare($sql);
      $bd->begin_transaction();
      $sentencia_preparada->bind_param("s", $id);
      $sentencia_preparada->execute();
      if( $sentencia_preparada->affected_rows == 1 ) {
        $resultado = BorrarBuzon($id, $con_buzon);
        if( $resultado != 0 ) {
          $bd->rollback();
          switch( $resultado ) {
            case 1: Error("Error al crear la copia de seguridad del archivo de buzones", "codigo/gestion.php", "Volver a la lista de usuarios");
            case 2: Error("Error no encuentra la línea", "codigo/gestion.php", "Volver a la lista de usuarios");
            case 3: Error("Error al borrar el buzón del archivo de buzones", "codigo/gestion.php", "Volver a la lista de usuarios");
	          case 4: Error("Error al generar la BD de buzones","codigo/gestion.php", "Volver a la lista de usuarios");
	          case 5: Error("Error al eliminar el buzón del usuario", "codigo/gestion.php", "Volver a la lista de usuarios");
          }
        }
        else {
          // Se confirma la transacción
          $bd->commit();    
        }
      }
    } 
    catch(mysqli_sql_exception $myse) {
      Error("Error de BD: " . $myse->getMessage(), "codigo/gestion.php", "Volver a la lista de usuarios");
    }
  }

  if( $operacion == "modificar" ) {
    $id = htmlspecialchars($_POST['id']);
    $nombre = htmlspecialchars($_POST['nombre']);
    $perfil = isset($_POST['adm']) ? htmlspecialchars($_POST['adm']) : "U";
    try {
      $sql = "UPDATE usuarios SET name = ?, perfil = ? WHERE id = ? ";
      $sentencia_preparada = $bd->prepare($sql);
      $sentencia_preparada->bind_param("sss", $nombre, $perfil, $id);
      $sentencia_preparada->execute();
    } 
    catch(mysqli_sql_exception $myse) {
      Error("Error de BD: " . $myse->getMessage(), "codigo/gestion.php", "Volver a la lista de usuarios");
    }
  }
  
  if ( $operacion == "reset_clave") {
    $id = htmlspecialchars($_POST['id']);
    $clave1 = htmlspecialchars($_POST['clave']);
    $clave2 = htmlspecialchars($_POST['clave2']);
    
    if ($clave1 != $clave2) {
        Error("Las claves no coinciden", "codigo/gestion.php", "Volver a la lista de usuarios");
    }

    $clave_cifrada = password_hash($clave1, PASSWORD_DEFAULT); 
    try {
        $sql = "UPDATE usuarios SET crypt = ? WHERE id = ?";
        $sentencia_preparada = $bd->prepare($sql);
        $sentencia_preparada->bind_param("ss",$clave_cifrada, $id);
        $sentencia_preparada->execute();
    } catch (mysqli_sql_exception $myse) {
        Error("Error de BD: " . $myse->getMessage(), "codigo/gestion.php", "Volver a la lista de usuarios");
    }
  }
}

// Listar los usuarios
try {
  $sql = "SELECT id, name, perfil FROM usuarios ";
  $sql.= "WHERE id != 'postmaster@email.iesgrancapitan.org' "; 
  $sql.= "AND   id != 'root@email.iesgrancapitan.org'";
  $sentencia_prep = $bd->prepare($sql);
  $sentencia_prep->execute();
  $usuarios = $sentencia_prep->get_result();
}
catch (mysqli_sql_exception $msqle ) {
  Error("Error de BD: " . $msqle->getMessage(), "index.php", "Abrir sesión");
}
EncabezadoHTML("Gestión de usuarios de correo");
Rotulos();
?>
<h4 class="titulo_centrado">Gestión de los buzones de correo</h4>
<table class="listado">
  <thead>
    <tr>
      <th style='width:30vw;'>Email</th>
      <th style='width:30vw;'>Usuario</th>
      <th style='width:10vw;'>Acciones</th>
      <th style='width:5vw;'>Perfil</th>
    </tr>
  </thead>
  <tbody>
<?PHP
    while( $usuario = $usuarios->fetch_assoc() ) {
      echo "<tr>\n";
      echo "<td style='width:30vw;'>" . $usuario['id'] . "</td>\n";
      echo "<td style='width:30vw;'>" . $usuario['name'] . "</td>\n";
      echo "<td style='width:10vw;'>";
      if( $usuario['id'] != "administrador@email.iesgrancapitan.org" ) {
        echo "<a class='acciones' href='borrar_usuario.php?id=$usuario[id]'>";
        echo "<img style='width: 24px;' src='../imagenes/usuario-.png'></a>";
      }
      echo "<a class='acciones' href='modificar_usuario.php?id=$usuario[id]'>";
      echo "<img style='width: 24px;' src='../imagenes/usuario.png'></a>";
      echo "<a class='acciones' href='modificar_contraseña.php?id=" . $usuario['id'] . "'>";
      echo "<img style='width: 24px;' src='../imagenes/clave.png'></a></td>\n";
      echo "<td style='width: 5vw;'>" . $usuario['perfil'] . "</td>\n";
      echo "</tr>\n";
    }
?> 
  </tbody>
</table>
<div>
  <a class="boton" id="nuevo_usuario" href='crear_usuario.php'>Nuevo usuario</a>
  <a class="boton" id="reconstruir_buzones" href='reconstruir_buzones.php'>Reconstruir archivo de buzones</a>
  <a class="boton" id="salir" href="/admin/index.php">Cerrar Sesion</a>
</div>
<?PHP 
  PieHTML();
?>
