<?PHP

include_once "../include/funciones.php";
include_once "../include/error.php";
IniciarSesion();

if (!isset($_SESSION['usuario'])) {
    Error("La sesión ha expirado", "index.php", "Abrir sesión");
}

if (isset($_POST['operacion']) and htmlspecialchars($_POST['operacion']) == "reconstruir") {

    // Abrimos el archivo provisional de buzones en modo escritura.
    // El directorio está definido en la variable open_base_dir establecida
    // en la función IniciarSesion()
    $vmailbox = fopen(__DIRECTORIO_BASE__ . "/vmailbox.prov", "wt" );
    if( !$vmailbox ) {
        Error("No se ha podido abrir el archivo en escritura","codigo/gestion.php","Volver a la lista de usuarios");
    }
    

    // Listado de usuarios actuales en la BD
    $bd = AbrirConexionBD();
    $sql = "SELECT id, maildir FROM usuarios";
    $sentencia_prep = $bd->prepare($sql);
    $sentencia_prep->execute();
    $usuarios = $sentencia_prep->get_result();
    $usuarios_creados = Array();
    while( $usuario = $usuarios->fetch_assoc() ) {
        $buzon = $usuario['id'] . "\t\t" . str_replace("/var/mail/", "", $usuario['maildir']) . "\n";
        if( !fwrite($vmailbox, $buzon) ) {
            Error("No se ha podido escribir en el fichero de buzones", "codigo/gestion.php","Volver a la lista de usuarios"); 
        }
        $usuarios_creados[ $usuario['id'] ] = $usuario['maildir'];
    }
    fclose($vmailbox);

    // Se copia el archivo provisional 
    $resultado = 0;
    $array_resultado = array();
    exec("sudo -u root cp --no-preserve=all /var/www/html/admin/tmp/vmailbox.prov /etc/postfix/vmailbox", $array_resultado, $resultado );
    if( $resultado != 0 ) {
        Error("No se ha podido copiar el nuevo fichero de buzones", "codigo/gestion.php","Volver a la gestión de usuarios");
    }
  
    $resultado = 0;
    unset($array_resultado);
    exec("sudo -u root postmap /etc/postfix/vmailbox", $array_resultado, $resultado );
    if( $resultado != 0 ) {
        Error("No se ha podido generar la BD del nuevo fichero de buzones", "codigo/gestion.php","Volver a la gestión de usuarios");
    }

    EncabezadoHTML("Reconstrucción del archivo de buzones - Proceso completado");
    Rotulos();
?>
    <h4 class="titulo_centrado">Reconstrucción del archivo de buzones - Proceso completado</h4>
    
        <div class="aviso">
            <span class="negrita">Los siguientes usuarios se han añadido al archivo de buzones:</span>
            <table class="listado">
                <thead>
                    <tr>
                    <th style='width:39vw'>Email</th>
                    <th style='width:39vw'>Buzón</th>
                    </tr>
                </thead>
                <tbody>
<?PHP
                foreach( $usuarios_creados as $id => $buzon ) {
                    echo "<tr>";
                    echo "<td style='width:39vw;'>{$id}</td>\n";
                    echo "<td style='width:39vw;'>{$buzon}</td>\n";
                    echo "</tr>";
                }
?>
                </tbody>
            </table>
        </div>
        <a class="boton" id="salir" href="gestion.php">Volver a la gestión de usuarios</a>
    
<?PHP
    PieHTML();
}
else {
    EncabezadoHTML("Reconstrucción del archivo de buzones");
    Rotulos();
?>  
    <h4 class="titulo_centrado">Reconstrucción del archivo de buzones</h4>
    <form method="POST" action="reconstruir_buzones.php" id="form_autenticacion">
    <div class="aviso">
    <span class="negrita">¡Atención! El proceso que está a punto de empezar consiste en
        generar un archivo de buzones de usuario a partir de la información de la tabla
        de usuarios en la base de datos. Solo debería realizar este proceso en el caso
        de que este archivo estuviera corrupto y necesitara crear uno nuevo. El proceso
        no es reversible y el archivo previo se perderá.</span>
    </div>
    <input type="hidden" name="operacion" value="reconstruir">
    <input id="reconstruir_buzones" type="submit" value="Comienzo de la reconstrucción">
    <a class="boton" id="salir" href="gestion.php">Volver</a>
    </form>

<?PHP
    PieHTML();
}

?>