# Aplicació SIGI

<p>Aplicació Web per a la gestió de l'inventari HW</p>
<p>Programació PHP 8 + Laravel 11</p>

## Login usuaris

- Login LDAP amb <b>autenticació en Active Directory</b>.
- Control de perfils d'usuari mitjançant el <b>sistema APN</b> definit en el SAD de Sopra. 
- Gestió d'usuaris amb dos perfils diferents: administrador i operador. Rols definits per al sistema APN.
- Menú amb accés a les seccions controlat segons el rol de l'usuari definit en el SAD.
- Alta i gestió d'usuaris i permisos des de la pròpia aplicació: <b>Menú Administració -> Usuaris</b>

## Bases de dades

- Utilitza la base de dades <b>INETPRE</b> per a treure les dades del personal actiu en À Punt (Srvsqlpre.cvmc.es). Taules datospers i versiones.
- La base de dades que gestiona l'aplicació és <b>SIGI_JMS</b>. Aquesta base de dades haurem de canviar-la per una base de dades nova per al nostre projecte.

## Formularis CRUD i camps de cerca

- La secció <b>Explotació > Elements</b> permet buscar per 18 camps diferents dins la taula d'elements maquinari (taula <b>dbo.cathard</b> de la base de dades SIGI_JMS).
- Inclou un CRUD per a llistar, modificar, eliminar o crear nous elements en la taula.
- Buscador amb funció autocompletar implementada amb JavaScript per a consultar la base de dades i oferir resultats mentre escrivim.

## Configurar l'Aplicació

Configuració de l'app des d'un únic arxiu: <b>.env</b>

APP_SADNAME: Correspon al sistema definit en la base de dades del SAD.

APP_ROL_ADMINISTRADOR i APP_ROL_OPERADOR: Són els noms dels rols definits en el SAD per al sistema.

DB_DATABASE: Base de dades que volem gestionar des de l'aplicació. Serà la base de dades del nostre projecte.

DB_DATABASE_RRHH: Base de dades en la que consultem les dades personals dels usuaris.

DB_DATABASE_SAD: Base de dades del sistema SAD en Pre.


Per a crear una nova aplicació amb aquest model:

- Crear un nou sistema en el SAD per a l'aplicació. Canviar el nom en el arxiu <b>.env</b> (APP_SADNAME).

- Crear dos rols nous per la sistema en el SAD. Posar en el arxiu .env el nom dels rols que hem creat (APP_ROL_ADMINISTRADOR i APP_ROL_OPERADOR).

- Crear una nova base de dades per a l'aplicació. Canviar el nom de la base de dades en l'arxiu .env (DB_DATABASE).


## Models, controladors i vistes

Haurem de canviar els models, el controlador i les vistes del CRUD Elements per a que facen referència a les taules i les columnes de la nova base de dades:

- Models/Element.php: Model amb els elements que es llisten i es modifiquen des del CRUD. Hem de canviar-lo pel model i la taula que volem modificar des de la nostra aplicació.
- Models/ElementFormulari.php: Model que s'utilitza per a fer cerques i mostrar llistats. No correspon a cap taula de la base de dades, correspon als camps del formulari que es fan servir en la cerca.

- Controllers/Explotacio/ElementsController.php: És el controlador on estan totes les consultes a la base de dades d'elements. Cada funció torna una vista amb les dades de la consulta.

- resources/views/explotacio: En aquesta carpeta estan totes les vistes que utilitza el controlador anterior. Des d'ací es maqueta la pantalla que torna les dades per columnes.



## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
