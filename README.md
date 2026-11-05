# Заметки

Сервис для ведения заметок: веб-интерфейс и REST API.

## Где посмотреть

- Приложение: <https://notes.dynek.me>
- Документация API (Swagger UI): <https://notes.dynek.me/docs/>
- Исходный код: <https://github.com/trybachok/todos>
- Сборки и деплои: <https://github.com/trybachok/todos/actions>

## Возможности

- Список заметок: новые сверху, по 20 штук. Кнопка «Показать ещё» загружает следующие.
- Создание заметки: заголовок обязателен и занимает до 255 символов, текст — до 10 000 символов. Если поле заполнено неверно, подсказка появляется рядом с ним.
- Просмотр заметки с датами создания и последнего изменения.
- Редактирование заметки.
- Удаление заметки с подтверждением.

Интерфейс подстраивается под светлую и тёмную тему системы и под экран телефона.

Те же операции доступны через API:

| Метод    | Адрес             | Действие                                     |
| -------- | ----------------- | -------------------------------------------- |
| `GET`    | `/api/notes`      | список заметок, параметры `limit` и `offset` |
| `POST`   | `/api/notes`      | создать заметку                              |
| `GET`    | `/api/notes/{id}` | получить заметку                             |
| `PUT`    | `/api/notes/{id}` | изменить заметку                             |
| `DELETE` | `/api/notes/{id}` | удалить заметку                              |

## Запуск на своём компьютере

Нужен Docker с Docker Compose, например [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```sh
git clone https://github.com/trybachok/todos.git
cd todos
docker compose up -d
```

Docker соберёт приложение, запустит базу данных и установит Битрикс. Команда завершается, когда сервис готов к работе. Первый запуск занимает несколько минут. После запуска откройте:

- приложение: <http://localhost:8080>
- документацию API: <http://localhost:8080/docs/>

Чтобы сменить порт, скопируйте `.env.example` в `.env` и поменяйте `NOTES_HTTP_PORT`.

Остановить сервис: `docker compose down`, заметки сохранятся до следующего запуска. Удалить сервис вместе с заметками: `docker compose down -v`.

## Как устроено

Интерфейс — одностраничное приложение на React. Его отдаёт nginx, он же передаёт запросы `/api/` в PHP. API реализует модуль `app.notes` для 1С-Битрикс на D7: маршруты построены на Routing, таблица заметок описана сущностью ORM. Заметки хранятся в MySQL.

Контракт API описан в [`docs/openapi.yaml`](docs/openapi.yaml). По нему генерируются типы клиента в интерфейсе, тесты сверяют с ним ответы API, Swagger UI строит по нему документацию.

Стек:

- интерфейс: React 19, TypeScript, Vite, TanStack Query, openapi-fetch;
- API: PHP 8.3, 1С-Битрикс: Управление сайтом, редакция «Старт» (пробная версия), MySQL 8.4;
- окружение: Docker Compose, nginx;
- проверки: PHPUnit, PHPStan, PHP-CS-Fixer, Vitest, Testing Library, ESLint, Prettier;
- сборка и деплой: GitHub Actions.

## Сборка и деплой

Каждый push в `main` и каждый pull request запускает в GitHub Actions проверки:

- интерфейс: аудит зависимостей, проверка типов, ESLint и Prettier, тесты пользовательских сценариев;
- API: сервис собирается и запускается в Docker, затем выполняются аудит зависимостей, PHP-CS-Fixer, PHPStan и тесты PHPUnit. Тесты проверяют правила валидации, все методы API со сверкой по контракту, число и планы запросов к базе.

Когда проверки push в `main` проходят, GitHub Actions runner на сервере разворачивает новую версию:

1. забирает код коммита;
2. пересобирает и перезапускает контейнеры;
3. проверяет, что API отвечает;
4. удаляет старые образы.

Заметки хранятся в томе Docker и сохраняются при деплое. HTTPS для notes.dynek.me обеспечивает nginx на шлюзе с сертификатом Let's Encrypt.

Проверки можно запустить и локально. Для интерфейса нужен Node.js 24:

```sh
cd frontend
npm ci
npm run typecheck && npm run lint && npm test
```

Для API Docker сам поднимет сервис, если он ещё не запущен:

```sh
docker compose --profile test run --rm tests
docker compose --profile test run --rm tests vendor/bin/phpstan analyse
docker compose --profile test run --rm tests vendor/bin/php-cs-fixer check
```
