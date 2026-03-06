# BUG
* Al intentar borrar una carpeta con contenido, no hace nada. Debería dar un aviso de que no se puede borrar carpetas con contenido, o pedir confirmación para borrar la carpeta con todo su contenido.
* Si inicias sesión, subes un archivo, y cierras sesión. Si le das para atrás, `subir.html` no te redirige a `index.html`.
# ERROR
* La página de inicio de sesión y la de registro tienen diferentes `label` para la contraseña.
* No hay confirmación de contraseña en el registro.
# SECURITY
* Comprobar las cabeceras de los archivos `.png`, `.jpg`, `.mp3`, etc. para asegurarse de que son lo que dicen ser.
# FEATURE
* Mover ficheros y carpetas ya subidas
	* Mover gráficamente
* Verificar la nueva cuenta con un correo electrónico
* Recuperar contraseñas