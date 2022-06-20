<?PHP
function Error($mensaje_error, $punto_recuperacion, $texto_enlace) {
    EncabezadoHTML("Error en la aplicación");
    Rotulos();
?>
    <h4 class="titulo_centrado">Error de la aplicación</h4>
    <div id="form_autenticacion">
        <p><?=$mensaje_error?></p>
        <a href="/admin/<?=$punto_recuperacion?>"><?=$texto_enlace?></a>
    </div>
<?PHP
    PieHTML();
    exit();
}
?>