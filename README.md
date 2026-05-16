Step-by-Step Setup
1. Copy the project files
Copy the entire acebedo-optical-clinic folder to the new machine's htdocs (XAMPP) or any directory.

2. Create the MySQL database
Open phpMyAdmin (or MySQL CLI) and run:


CREATE DATABASE acebedo_optical_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
3. Configure the backend

cd backend
cp .env.example .env
Then edit .env and set these values:


APP_NAME="Acebedo Optical Clinic"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acebedo_optical_clinic
DB_USERNAME=root
DB_PASSWORD=           # leave blank if XAMPP default

FRONTEND_URL=http://localhost:5173
4. Install backend dependencies and set up the database

cd backend

composer install

php artisan key:generate

php artisan migrate

php artisan db:seed
The seeder creates:

3 default accounts: admin@acebedo.com, reception@acebedo.com, optometrist@acebedo.com — all with password password
Sample patients, products, appointments, prescriptions, and 90 days of sales history
5. Install frontend dependencies

cd frontend

npm install
6. Run the project
Open two terminals and run each command in one:

Terminal 1 — Backend:


cd backend
php artisan serve
Runs at http://localhost:8000

Terminal 2 — Frontend:


cd frontend
npm run dev
Runs at http://localhost:5173

Then open your browser at http://localhost:5173.

Default Login Accounts
Role	Email	Password
Admin	admin@acebedo.com	password
Receptionist	reception@acebedo.com	password
Optometrist	optometrist@acebedo.com	password
Inventory Staff	inventory@acebedo.com password
