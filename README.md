# Cabaña Delfina · Página web y tarjeta de fidelidad

Sitio web del negocio (página principal) más una aplicación (PWA) para registrar
clientes, sumar sellos por cada compra y entregar un premio automático, avisando
por WhatsApp.

## Qué incluye

- **Página principal:** presentación del negocio, afiche, menú y precio, programa
  de fidelidad, horario, domicilios y contacto por WhatsApp. Se edita desde
  `Admin → Ajustes → Página principal` sin tocar código.
- **Clientes:** registro con celular + PIN, tarjeta digital de 10 sellos, QR personal,
  cupones, código de referido e historial.
- **Mostrador:** registro de clientes, búsqueda por celular / cédula / código,
  suma de sellos con valor de la venta, canje de cupones y escáner de QR.
- **Administración:** resumen de ventas, clientes (con exportación CSV), cupones,
  cola de mensajes de WhatsApp, ajustes del programa y gestión del equipo.
- **WhatsApp:** motor de notificaciones con dos modos:
  - **Enlace manual (`wa.me`):** gratis, abre WhatsApp con el mensaje listo y se
    marca como enviado. Funciona desde el primer día.
  - **API oficial de WhatsApp Cloud:** envío automático con token y phone_id.
- **Tareas automáticas (`cron.php`):** cupones por vencer, clientes inactivos y
  cumpleaños.

## Requisitos

- PHP 8.0 o superior con `pdo_sqlite` (local) o `pdo_mysql` (hosting).
- En local: XAMPP. La app ya está en `C:\xampp\htdocs\promo`.
- En producción: hosting con PHP + HTTPS.

## Instalación local

1. Abre `http://localhost/promo/install.php` en el navegador.
2. Pulsa **Instalar ahora**.
3. Entra a la app y cambia los PIN iniciales:
   - Administrador: PIN `1234`
   - Mostrador: PIN `1111`

Si Apache no está corriendo puedes usar el servidor integrado de PHP:

```powershell
C:\xampp\php\php.exe -S localhost:8000 -t C:\xampp\htdocs
# y abre http://localhost:8000/promo/
```

## Flujo recomendado en el local

1. El empleado entra a **Mostrador** con su PIN.
2. Registra al cliente una sola vez: nombre, celular (obligatorio), cédula y
   cumpleaños (opcionales).
3. En cada compra busca al cliente (celular, cédula o código), escribe el valor
   y pulsa **+1 sello**.
4. Al llegar a 10 sellos se genera un cupón con código y el mensaje de premio
   queda en la cola de WhatsApp.
5. En **Admin → Mensajes** se envía por WhatsApp y se marca como enviado, o se
   envía solo si la API está configurada.
6. Al canjear, el empleado escribe el código del cupón en **Canjear premio**.

> La clave del cliente es el **celular**, porque es el canal de WhatsApp.
> La **cédula** es un dato opcional de apoyo para evitar duplicados e identificarlo.

## WhatsApp

### Modo enlace (por defecto)

No requiere configuración. En `Admin → Mensajes` cada mensaje tiene un botón que
abre WhatsApp Web/app con el texto listo. Después se pulsa “Ya lo envié”.

### API oficial (automático)

1. Crea una app en [Meta for Developers](https://developers.facebook.com/) con el
   producto **WhatsApp**.
2. Obtén el **token permanente** y el **Phone ID** del número.
3. En `Admin → Ajustes → WhatsApp` pega ambos datos y elige el modo **API oficial**.
4. Las plantillas de mensajes promocionales deben estar aprobadas por Meta.

La app usa `graph.facebook.com/v20.0`. El costo por mensaje lo cobra Meta según su
tarifa vigente.

## Tareas programadas

Copia la URL que aparece en `Admin → Ajustes → Tareas automáticas` y prográmala
una vez al día:

- **Windows:** Programador de tareas → `powershell -Command "Invoke-WebRequest 'URL'"`
- **Hosting:** cron job diario apuntando a esa URL.

## Pasar a un hosting con MySQL

1. Crea la base de datos en el hosting.
2. Edita `config.php`:

```php
'db' => [
    'driver' => 'mysql',
    'host'   => 'localhost',
    'port'   => 3306,
    'name'   => 'tu_base',
    'user'   => 'tu_usuario',
    'pass'   => 'tu_clave',
],
'app_base' => '',          // si la app va en la raíz del dominio
'public_url' => 'https://tudominio.com',
```

3. Sube todos los archivos excepto `data/promo.sqlite` y `data/.installed`.
4. Abre `https://tudominio.com/install.php` y ejecuta el instalador.

## Seguridad

- PINs guardados con `password_hash`.
- Consultas preparadas (PDO) y escape de salida HTML.
- Tokens CSRF en todos los formularios.
- `data/`, `includes/` y `config.php` bloqueados por `.htaccess`.
- Cambia los PIN iniciales antes de usar la app con clientes reales.

## Estructura

```
promo/
├─ index.php            Página principal del negocio (landing)
├─ tarjeta.php          Registro e ingreso de clientes
├─ card.php             Tarjeta digital, QR y cupones
├─ staff/index.php      Mostrador: sellar, canjear, registrar
├─ admin/index.php      Panel de administración
├─ cron.php             Tareas automáticas
├─ install.php          Instalador y creación de la base de datos
├─ config.php           Configuración (base de datos, URL)
├─ includes/app.php     Núcleo: BD, sesión, fidelidad, WhatsApp
├─ assets/              Estilos, JS, PWA, icono y afiche
└─ data/                Base SQLite (no se sube al hosting)
```
