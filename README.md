# Cabaña Delfina · Página web y tarjeta de fidelidad

Sitio web del negocio (página principal) más una aplicación (PWA) para registrar
clientes, sumar sellos por cada compra y entregar un premio automático, avisando
por WhatsApp.

## Qué incluye

- **Páginas del sitio:** Inicio, Nuestro Menú, Cabaña Delfina, Envíos a Domicilio y
  Ubicación y Contacto, cada una con su propia dirección y pestaña activa. Los textos
  se editan desde `Admin → Ajustes` sin tocar código.
- **Menú con fotos:** cada plato tiene nombre, categoría, precio (texto libre, admite
  “Tu precio”), descripción, orden y foto. Los cortes se muestran con papas, ensalada
  y chimichurri. Se administra desde `Admin → Menú`, que permite agregar, editar,
  ocultar, eliminar y subir la imagen (JPG, PNG o WEBP hasta 4 MB).
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

## Páginas del sitio y menú

El sitio está dividido en páginas independientes, cada una con su propia dirección:

| Pestaña | Archivo | Contenido |
| --- | --- | --- |
| Inicio | `index.php` | Portada, plato estrella, vista rápida del menú, tarjeta de sellos y resumen de la cabaña |
| Nuestro Menú | `menu.php` | Platos agrupados por categoría, con foto, precio y botón de pedido |
| Cabaña Delfina | `cabana.php` | Espacio tranquilo en Alpachaca, características y reservas |
| Envíos a Domicilio | `envios.php` | Costo de domicilio **$1.00 por pedido**, ejemplos y pasos para pedir |
| Ubicación y Contacto | `contacto.php` | Dirección, horario, WhatsApp, mapa y preguntas frecuentes |
| Mi tarjeta | `tarjeta.php` / `card.php` | Registro, ingreso y tarjeta de sellos del cliente |

La barra superior marca con fondo la pestaña activa. Los datos compartidos del sitio
(WhatsApp, precios, horario, textos) se calculan en `includes/site.php`.

Los platos viven en la tabla `menu_items`. Desde **Admin → Menú** puedes:

- Agregar un plato nuevo con su foto.
- Editar nombre, categoría, precio, descripción y orden.
- Ocultarlo del sitio sin borrarlo (útil si un corte no hay ese día).
- Eliminarlo definitivamente.

Las imágenes se guardan en `assets/menu/`. Si un plato no tiene foto, la tarjeta
muestra un ícono. El costo de envío y los textos de la portada se cambian en
**Admin → Ajustes**.

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
├─ index.php            Inicio del negocio
├─ menu.php            Menú completo con fotos y precios
├─ cabana.php          Cabaña Delfina y reservas
├─ envios.php          Envíos a domicilio
├─ contacto.php        Ubicación, contacto y preguntas frecuentes
├─ tarjeta.php          Registro e ingreso de clientes
├─ card.php             Tarjeta digital, QR y cupones
├─ staff/index.php      Mostrador: sellar, canjear, registrar
├─ admin/index.php      Panel de administración
├─ cron.php             Tareas automáticas
├─ install.php          Instalador y creación de la base de datos
├─ config.php           Configuración (base de datos, URL)
├─ includes/app.php     Núcleo: BD, sesión, fidelidad, WhatsApp
├─ includes/site.php    Datos compartidos de las páginas públicas
├─ assets/              Estilos, JS, PWA e icono
│  └─ menu/             Fotos de los platos (editables desde Admin → Menú)
└─ data/                Base SQLite (no se sube al hosting)
```
