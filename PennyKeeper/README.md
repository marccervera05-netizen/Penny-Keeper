# PennyKeeper

Aplicació web de gestió de finances personals desenvolupada amb PHP, MariaDB, Bootstrap Icons i JavaScript vanilla. Permet registrar ingressos i despeses, gestionar plans d'estalvi, fer seguiment d'inversions i obtenir un resum financer mensual.

Projecte de portafoli del cicle DAW (Desenvolupament d'Aplicacions Web) — Institut Montilivi, Girona.

---

## Funcionalitats

- Registre i autenticació d'usuaris amb sessions segures
- Gestió d'ingressos i despeses mensuals amb categories personalitzades
- Filtre per mes i any amb navegació intuïtiva
- Ingressos i despeses recurrents mensuals
- Plans d'estalvi amb barra de progrés i historial d'aportacions
- Inversions amb aportacions periòdiques i actualització del valor de mercat
- Dashboard amb resum del mes: balanç net, despeses per categoria i últimes transaccions
- Disseny responsive (desktop i mòbil)
- Interfície en català

---

## Tecnologies

| Capa       | Tecnologia                        |
|------------|-----------------------------------|
| Backend    | PHP 8.0+ (MVC sense framework)    |
| Base dades | MariaDB 10.x amb PDO              |
| Frontend   | HTML5, CSS3, JavaScript ES2022    |
| Icones     | Bootstrap Icons 1.11              |
| Fonts      | Google Fonts (Inter + Playfair Display) |
| Servidor   | Apache (XAMPP local / hosting)    |

---

## Estructura del projecte

```
PENNYKEEPER/
├── backend/
│   ├── api/
│   │   └── controllers/
│   │       ├── AuthController.php
│   │       ├── TransactionController.php
│   │       ├── SavingController.php
│   │       ├── InvestmentController.php
│   │       └── SettingsController.php
│   ├── core/
│   │   ├── config.php
│   │   ├── db.php
│   │   └── auth.php
│   └── models/
│       ├── User.php
│       ├── Income.php
│       ├── Expense.php
│       ├── Category.php
│       ├── SavingPlan.php
│       └── Investment.php
└── frontend/
    ├── assets/
    │   ├── css/
    │   │   ├── main.css
    │   │   ├── dashboard.css
    │   │   ├── transactions.css
    │   │   ├── savings.css
    │   │   ├── invest.css
    │   │   └── settings.css
    │   └── js/
    │       ├── auth.js
    │       ├── dashboard.js
    │       ├── transactions.js
    │       ├── savings.js
    │       ├── invest.js
    │       └── settings.js
    ├── components/
    │   └── navbar.php
    ├── pages/
    │   ├── login.php
    │   ├── register.php
    │   ├── dashboard.php
    │   ├── incomes.php
    │   ├── expenses.php
    │   ├── savings.php
    │   ├── invest.php
    │   └── settings.php
    └── index.php
```

---

## Instal·lació local

### Requisits

- XAMPP (Apache + PHP 8.0+ + MariaDB)
- Navegador modern

### Passos

1. Clona el repositori dins de `htdocs`:

```bash
git clone https://github.com/el-teu-usuari/pennykeeper.git htdocs/Projectes/PENNYKEEPER
```

2. Importa la base de dades a phpMyAdmin:

```
Fitxer: pennykeeper_db.sql
```

3. Edita `backend/core/config.php` amb les teves credencials:

```php
define('APP_URL', 'http://localhost/Projectes/PENNYKEEPER/frontend');
define('DB_HOST', 'localhost');
define('DB_NAME', 'pennykeeper_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

4. Accedeix a l'aplicació:

```
http://localhost/Projectes/PENNYKEEPER/frontend/
```

---

## Desplegament en producció

1. Puja els fitxers al servidor via FTP o Git
2. Importa `pennykeeper_db.sql` al gestor de BD del hosting
3. Edita `backend/core/config.php`:

```php
define('APP_ENV', 'production');
define('APP_URL', 'https://el-teu-domini.com');
define('DB_HOST', 'localhost');
define('DB_NAME', 'nom_bd_hosting');
define('DB_USER', 'usuari_bd_hosting');
define('DB_PASS', 'contrasenya_bd_hosting');
```

---

## Paleta de colors

| Nom         | Hex       |
|-------------|-----------|
| Marró fosc  | `#714329` |
| Marró mig   | `#B08463` |
| Marró-gris  | `#B9937B` |
| Gris clar   | `#D0B9A7` |
| Gris fosc   | `#B5A192` |

---

## Autor

Marc — estudiant de DAW a Institut Montilivi, Girona  
Curs 2025–2026