<?PHP
include_once("../include/funciones.php");
IniciarSesion();

// Recoger los datos del formulario e intentar abrir conexion con la BD
$login = htmlspecialchars($_POST['login']); 
$clave = htmlspecialchars($_POST['clave']);

if( !empty($login) AND !empty($clave) ) {
  if( LoginUsuario( $login, $clave ) ) {
    header("Location: /admin/codigo/gestion.php", FALSE, 303);
  }
}
?>