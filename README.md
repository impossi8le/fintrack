📦 Запуск через Docker
    В проекте есть готовая конфигурация Dockerfile и docker-compose.yml, которая поднимает весь стек приложения одной командой. Docker сам установит и настроит:
        
        PHP 8.2 с необходимыми расширениями
        
        Node.js 20 для сборки фронтенда
        
        Composer для установки PHP‑зависимостей
        
        MySQL 8 — база данных
        
        Redis — кеш и очереди
        
        phpMyAdmin — веб‑интерфейс для работы с БД
        
        Laravel Queue Worker — обработка фоновых задач
        
        Nginx / встроенный сервер Laravel — для обслуживания приложения

🚀 Быстрый старт
    Установите Docker Desktop (если ещё не установлен).
    
    Клонируйте проект:
        
        bash
        git clone https://github.com/impossi8le/fintrack.git
        cd fintrack
        Запустите сборку и старт контейнеров:
        
        bash
        docker compose up --build
        
    При первом запуске Docker автоматически:
        
        соберёт образ с PHP, Node.js и Composer;
        
        установит зависимости Laravel (composer install);
        
        установит зависимости фронтенда (npm install);
        
        сгенерирует ключ приложения (php artisan key:generate);
        
        применит миграции (php artisan migrate --force);
        
        соберёт фронтенд (npm run build);
        
        запустит Laravel на http://localhost:8000.

📦 Запуск очередей в Docker
    В конфигурации docker-compose.yml есть отдельный сервис queue, который при старте контейнеров автоматически запускает обработчик очередей Laravel:

🌐 Доступ к сервисам
    Приложение: http://localhost:8000
    
    Vite Dev Server (горячая перезагрузка фронта): http://localhost:5173
    
    phpMyAdmin: http://localhost:8081 (логин: root, пароль: root)
    
    MySQL: порт 3307 на хосте (root / root)
    
    Redis: порт 6379 на хосте
