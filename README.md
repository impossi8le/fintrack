<div align="center">

# FinTrack

**Учебный финансовый трекер: Laravel API с балансом и историей операций + Vue SPA на том же домене.**

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)

![License MIT](https://img.shields.io/badge/license-MIT-green)
</div>

> **Коротко.** Небольшой сервис учёта личных средств: пользователь регистрируется,
> получает баланс и историю операций. Демонстрирует три вещи, которые легко
> сделать неправильно: деньги в транзакции с блокировкой строки, идемпотентность
> повторных начислений по внешнему ID и разгрузку записи в очередь.

**Ссылки:** [API-маршруты](routes/api.php) · [миграции](database/migrations) · [Docker-окружение](docker-compose.yml)

---

## Проблема

Учёт денег на счёте — задача, где «почти правильно» равно «неправильно»:

- **Гонка при параллельных операциях.** Два запроса читают баланс, оба прибавляют
  сумму, одно обновление теряется. Итог — деньги из воздуха или из ниоткуда.
- **Повторные начисления.** Платёжный провайдер или ретрай клиента присылает ту же
  операцию второй раз — и баланс растёт дважды.
- **Плавающая точка.** `0.1 + 0.2` в `float` не равно `0.3`; на деньгах это
  накапливается в копейки, которые потом ищут вручную.
- **Синхронная запись под нагрузкой.** Каждая операция ждёт базы; список операций
  пересчитывается на каждый экран.

Этот проект — компактная иллюстрация того, как эти четыре проблемы закрываются
средствами Laravel, без внешних очередей и без платёжного шлюза.

## Что умеет

- **Регистрация и вход** по email/паролю с выдачей токена Sanctum.
- **Баланс пользователя** — отдельная сущность, создаётся вместе с аккаунтом.
- **Пополнение и списание** через API: `credit` / `debit`, с проверкой достаточности средств.
- **Асинхронная обработка операции** — запрос отвечает `202 queued`, работу делает
  очередь, а не HTTP-запрос.
- **История операций** с постраничной выдачей (20 записей на страницу).
- **Идемпотентность по `external_id`** — повтор с тем же внешним ID не создаёт вторую транзакцию.
- **Кеширование** баланса (1 час) и истории (60 секунд) с инвалидацией после операции.
- **SPA-интерфейс** на Vue 3: вход, регистрация, дашборд, история, профиль.

## Стек

| Слой | Технологии |
|---|---|
| Backend | PHP 8.2, Laravel 11, Laravel Sanctum |
| Frontend | Vue 3, Vue Router, Pinia, Vite 6, Tailwind CSS 3, Bootstrap 5, Axios |
| Данные | MySQL 8, Redis 7 (кеш и очереди) |
| Деньги | `bcmath` (`bcadd` / `bcsub`), транзакции БД с `lockForUpdate` |
| Очереди | Laravel Queue Worker в отдельном контейнере |
| Инфраструктура | Docker (PHP CLI + Node 20 + Composer), docker-compose, phpMyAdmin |
| Тесты | PHPUnit 11 — только стандартные заглушки Laravel |

## Как это устроено

```
Vue SPA (resources/js) ──axios──▶ routes/api.php ──▶ контроллеры
                                                      │
                       ┌──────────────────────────────┼─────────────────────┐
                       ▼                              ▼                     ▼
                BalanceService                  ProcessBalanceOperation   UserCacheService
             (DB::transaction + lock)          (Job в очереди)            (Redis, TTL)
                       │                              │
                       ▼                              ▼
                balances / transactions           обновление кеша
```

**Поток операции.** `POST /api/balance/operate` принимает тип, сумму, описание и
`external_id`, тут же сбрасывает кеш пользователя и ставит `ProcessBalanceOperation`
в очередь, отвечая `202`. Джоб вызывает `BalanceService::operate`, после чего
перечитывает пользователя, обновляет кеш и сбрасывает кеш истории транзакций.

**Где границы.** HTTP-слой ничего не знает о блокировках и арифметике; вся работа
с деньгами — в `BalanceService`. DTO (`BalanceOperationDTO`) переносит операцию
из контроллера в очередь неизменной. Кеш не является источником истины: он всегда
пересобирается из БД после записи.

**Схема данных.** `users` → `balances` (1:1, `amount` — строка с 2 знаками) и
`transactions` (`type`, `amount`, `description`, `external_id`, `user_id`).
Токены Sanctum — в `personal_access_tokens`.

## Инженерные находки

- **Баланс меняется только внутри транзакции с блокировкой строки.**
  `DB::transaction` + `Balance::lockForUpdate()->firstOrCreate(...)` гарантирует,
  что параллельные операции одного пользователя выстраиваются в очередь, а не
  перезаписывают друг друга. Строка баланса создаётся лениво, если её ещё нет.
- **Арифметика — в `bcmath`, а не в `float`.** Сумма приводится к строке с двумя
  знаками (`number_format(..., 2, '.', '')`), сложение и вычитание — `bcadd` /
  `bcsub` с scale 2. Копейки не «плывут».
- **Идемпотентность по `external_id`.** Если операция с таким внешним ID у
  пользователя уже есть, сервис возвращает существующую транзакцию, не меняя
  баланс. Это защита от повторной доставки одного и того же начисления.
- **Проверка средств — до записи.** Для `debit` сервис сравнивает сумму с текущим
  балансом внутри той же транзакции и бросает `InvalidArgumentException` при
  нехватке; частичного списания не бывает.
- **Запись разгружена в очередь, а не в HTTP-запрос.** Контроллер отвечает `202`
  сразу; джоб делает тяжёлую часть. Кеш сбрасывается **до** постановки в очередь
  (чтобы чтение не вернуло старое) и **после** операции (чтобы вернуло новое).
- **Два разных TTL под разную частоту изменений.** Данные пользователя — 1 час,
  история — 60 секунд, потому что история меняется после каждой операции, а профиль
  почти нет. Ключи собираются в одном месте (`UserCacheService::key`).

## API

Все маршруты — из `routes/api.php`. Защищённые требуют заголовок
`Authorization: Bearer <token>`.

| Метод | Маршрут | Что делает |
|---|---|---|
| `POST` | `/api/register` | Регистрация, создаёт аккаунт и пустой баланс, возвращает токен |
| `POST` | `/api/login` | Вход по email/паролю, возвращает токен Sanctum |
| `GET` | `/api/user` | Текущий пользователь |
| `GET` | `/api/balance` | Баланс (из кеша или из БД с прогревом кеша) |
| `POST` | `/api/balance/operate` | Операция `credit`/`debit`, отвечает `202 queued` |
| `GET` | `/api/transactions` | История операций, 20 на страницу |

Пример последовательности:

```bash
# регистрация и токен
curl -s -X POST http://localhost:8000/api/register \
  -H 'Accept: application/json' \
  -d 'name=Demo&email=demo@example.com&password=secret123'

# начисление 100.00
curl -s -X POST http://localhost:8000/api/balance/operate \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -d 'type=credit&amount=100&description=test&external_id=demo-1'

# баланс и история
curl -s http://localhost:8000/api/balance      -H "Authorization: Bearer $TOKEN"
curl -s http://localhost:8000/api/transactions -H "Authorization: Bearer $TOKEN"
```

Повтор того же запроса с `external_id=demo-1` вернёт уже созданную транзакцию,
а не начислит второй раз.

## Быстрый старт

В репозитории есть `Dockerfile` и `docker-compose.yml`; compose поднимает пять
сервисов: `app`, `queue`, `mysql`, `phpmyadmin`, `redis`.

```bash
git clone https://github.com/impossi8le/fintrack.git
cd fintrack

# .env.example в репозитории нет — создайте .env вручную.
# APP_KEY допишет key:generate из команды контейнера;
# DB_* и REDIS_* compose передаёт через environment.
printf 'APP_NAME=fintrack\nAPP_ENV=local\nAPP_DEBUG=true\n' > .env

docker compose up --build
```

Что произойдёт при старте контейнера `app` (по команде из `docker-compose.yml`):

```text
composer install
npm install
php artisan key:generate
php artisan migrate --force
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Адреса после запуска:

| Сервис | Адрес |
|---|---|
| Приложение | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | `localhost:3307` |
| Redis | `localhost:6379` |

Локальный запуск без Docker (если PHP, Composer, Node и MySQL/Redis уже есть):

```bash
composer install
cp .env.example .env        # файла в репозитории нет — см. примечание выше
php artisan key:generate
php artisan migrate
composer run dev            # serve + queue:listen + pail + vite
```

## Статус и ограничения

Проект учебный; ниже — честная картина, а не витрина.

- **Тестов фактически нет.** В `tests/` лежат только две стандартные заглушки
  Laravel (`ExampleTest` в `Feature` и `Unit`). Логика баланса, идемпотентности и
  очередей не покрыта.
- **CI нет.** Каталог `.github/` в репозитории отсутствует, workflow-файлов нет.
- **`docker-compose.yml` на месте и описывает пять сервисов**, но запуск в среде
  подготовки этого README не выполнялся — инструкция составлена по файлам.
- **`.env.example` отсутствует**, хотя composer-скрипт и README на него ссылаются.
  Переменные приходится создавать вручную.
- **Валидация запроса операции пустая.** У `BalanceOperateRequest` нет правил:
  тип, сумму и `external_id` проверяет уже сервис, а не HTTP-слой.
- **На `external_id` нет уникального индекса.** Идемпотентность держится на
  проверке внутри транзакции с блокировкой строки баланса, а не на ограничении БД.
- **Старый README обещал Nginx.** Фактически приложение запускается через
  `php artisan serve`; Nginx в compose нет.
- **Vite dev server на 5173 не поднимается автоматически** — compose делает
  `npm run build`, а не `npm run dev`. Порт проброшен, но обслуживать его нечему.
- **Лицензия — MIT** (`LICENSE`); `composer.json` всё ещё несёт MIT из скелета Laravel.
- **В корне лежит пустой файл `git`** — случайный артефакт, к сборке отношения не имеет.

## Документация

- `routes/api.php` — весь публичный контракт: регистрация, вход, баланс, история.
- `app/Services/BalanceService.php` — транзакция, блокировка, арифметика, идемпотентность.
- `app/Jobs/ProcessBalanceOperation.php` — асинхронная обработка и инвалидация кеша.
- `app/Services/UserCacheService.php` — ключи и TTL кеша.
- `database/migrations/` — схема: пользователи, балансы, транзакции, внешний ID.
- `docker-compose.yml`, `Dockerfile` — окружение: PHP 8.2, Node 20, MySQL 8, Redis 7.
- `resources/js/` — SPA: роутер, страницы, стор авторизации, типы API.
