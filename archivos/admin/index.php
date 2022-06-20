<?PHP
include_once("./include/funciones.php");
IniciarSesion();

unset($_SESSION['usuario']);

EncabezadoHTML("Inicio de sesión");
?>
<div id="form_autenticacion">
    <h2 class="titulo_centrado">Email IESGranCapitan.org</h2>
    <h3 class="titulo_centrado">Administración de usuarios</h3>
    <div id="logotipos">
    Powered by ... <br>
        <img src="imagenes/rc.webp">&nbsp;
        <img src="imagenes/postfix.png">
        <img style="width:150px;" src="imagenes/logo_courier.png">
    </div>
    <form method="post" action="codigo/login.php" name="autenticacion">
        <div class="campo_formulario">
            <label for="login">Usuario</label>
            <input type="text" id="login" name="login">
            @email.iesgrancapitan.org
        </div>
        <div class="campo_formulario">
            <label>Contraseña</label>
            <input type="password" name="clave" id="clave" required />
        </div>
        <div class="campo_formulario">
            <label></label>
            <button id="entrar" type="submit">Iniciar Sesión</button> 
        </div>
    </div>
    </form>
</div>
<?PHP
PieHTML();
?>