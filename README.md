Wanna Make an Outstanding Move?
-------------------------------------------------------
<p align="center">
  <img src="https://sfwfun.com/wp-content/uploads/2019/01/Outstanding-Move-memes-Maravillosa-Jugada-memes-reddit-Outstanding-Move-funny-memes-17.jpg" width="800" title="Outstanding Move logo">
</p>

<p align="center">
    <a href="https://github.com/turing228/outstanding-move/blob/master/LICENSE">
        <img src="https://img.shields.io/github/license/turing228/outstanding-move" title="outstanding-move is released under the MIT license." />
    </a>
    <a href="https://github.com/turing228/outstanding-move/graphs/contributors">
        <img src="https://img.shields.io/github/contributors/turing228/outstanding-move?color=orange" title="Contributors"/>
    </a>
    <a href="https://github.com/turing228/outstanding-move/releases">
        <img src="https://img.shields.io/github/v/release/turing228/outstanding-move" title="Release version"/>
    </a>
    <img src="https://img.shields.io/github/repo-size/turing228/outstanding-move" title="Repository size"/>
    <img src="https://img.shields.io/badge/build-passing-brightgreen" title="Build passing"/>
    <a href="https://github.com/turing228/outstanding-move/stargazers">
        <img src="https://img.shields.io/github/stars/turing228/outstanding-move?style=social" title="Stars"/>
    </a>
</p>

Can you do it??? Yes, **YOU CAN**! Install this chess in PHP, launch it, and try!

## Contents

- [🚀 Quickstart](#-quickstart)
- [🍔 Screenshots](#-screenshots)
- [💣 О решении](#-о-решении)
- [🚄 Roadmap](#-roadmap)
- [🏆 Мотивация](#-мотивация)
- [📋 Technologies](#-technologies)
- [🍿 References](#-references)
- [👪 Contributors](#-contributors)
- [📄 License](#-license)

## 🚀 Quickstart

Clone this repository:

    git clone https://github.com/turing228/outstanding-move.git
    cd outstanding-move

If you have not installed XAMPP, [it is time to do it](https://www.apachefriends.org/download.html)

Start Apache and MySQL servers in XAMPP

Copy project to your XAMPP directory:

    # Windows
    cp outstanding-move C:\xampp\htdocs

Start sending commands:

    # Start a new game
    curl -L -X POST http://localhost/outstanding-move/api/game

    # Get current game status
    curl -L -X GET http://localhost/outstanding-move/api/game

    # Make a move a chess piece a2 to a3
    curl -L -X PUT http://localhost/outstanding-move/api/game -d from=a2 -d to=a3

    # Delete current game if it exists
    curl -L -X DELETE http://localhost/outstanding-move/api/game

Or run prepared scripts:

    # Windows
    
    # Fool mate simulation
    foolmate.bat
    
    # Fool mate extended simulation (a lot of incorrect requests)
    foolmatebig.bat

## 🍔 Screenshots

### Fool Mate (foolmate.bat)
<p align="center">
  <img src="/foolmate.png" width="800">
</p>

### Extended Fool Mate (foolmatebig.bat)
<p align="center"> 
  <img src="/foolmatebig.png" width="800">
</p>

## 💣 О решении

🏋️‍♂️ Моей главной задачей было сделать наиболее понятное, прозрачное, наглядное и быстрореализуемое масштабируемое решение. Я считаю, что справился с ней. Вот как:

#### Как я храню информацию об игре?

Это одна строчка в таблице базы данных со следующими полями:

* `string $color_now` — цвет, который сейчас ходит. Равен 'w', если белые, и 'B', если черные

* `string $board` — текущая ситуация на шахматной доске в виде строчки длины 64. Клетки доски пронумерованы как a1=0, b1=1, ..., a2=8, b2=9, ..., h8=63.
    В начале доска представляется так: "rkbqtbkrpppppppp********************************PPPPPPPPRKBQTBKR".
    Маленькие буквы — это 'w', белые. Большие — 'B', черные. 
    P=Pawn (пешка), B=Bishop (слон), K=Knight (конь), R=Rook (ладья), Q=Queen (ферзь), T=Tsar=King (король)

* `string $status` — 'is_on', если игра в процессе, 'white_won', если белые победили, 'black_won' — если черные, '' если игра не запущена

Для хранения `$color_now` и `$status` в базе данных я использую формат `ENUM` 🤟, хотя стратегии оптимизации говорят использовать `integer/boolean`. Я принял такое решение потому, что `ENUM` сразу раскрывает смысл своего значения и не приходится задумываться каждый раз "а что это означает?", а `int/boolean` в данном случае заметно не уменьшили бы используемую память и явно не упростили бы разработку 👌

#### Как я делаю шаг в игре?

Чтобы сделать шаг, пользователь должен прислать PUT запросом два параметра — `string $from` (откуда) и `string $to` (куда). Задаются они как шахматная клетка — например, `f5`, `a2` или `h8`.

Проверяю переданные PUT запросом аргументы `$from` и `$to` на корректность. Если по каким-то причинам такой ход невозможно сделать, то я сообщаю почему. В программе я выделяю аж 6 разных причин, чтобы пользователь однозначно понял, что он сделал не так (это все в методе `GameAPI::updateAction()` из файла [GameApi.php](https://github.com/turing228/outstanding-move/blob/master/gameapi.php)).

К тому моменту как дело доходит до проверки может ли фигура действительно сделать такой ход, мы получаем запас инвариантов и можем перейти к следующему этапу.

#### Что такое Piece.php?

Все шахматные фигуры по сути делают одно и то же: понимают кто они, делают шаги по шахматной доске. Нам же нужно знать просто — сможет ли фигура сделать какой-то шаг или нет, нас не интересует ее тип. Поэтому я завел абстрактный класс `Piece`, от которого наследуются классы для каждого типа фигуры.

Как проверять, что фигура может сделать какой-то шаг?

Все шахматные фигуры делятся на два типа:

1. Те, которым важно, чтобы на пути до конечной точки не было других фигур. Они как бы скользят по доске не отрываясь, причем могут скользить до конца доски. У них параметр `$sliding = true`

2. Все остальные, которые, прыгая, отрываются от доски и приземляются сразу в конечную точку. Они очень ограничены в своих ходах. У этих параметр `$sliding = false`

Соответственно, в зависимости от типа фигуры будем вызывать разные методы проверки возможности хода. 

У каждого типа фигуры есть наименьшая описывающая его ходы структура — массив пар перемещений по `x` и `y` — `$moves`. 

1. Если фигура не скользит, то мы проверяем все клетки, отличающиеся от `$from` на элемент из `$moves`, проверяя остаемся ли мы на доске. Если среди них есть `$to`, то ход можно сделать, учитывая инварианты

2. Если фигура скользит, то мы  идем из `$from` по-очереди в каждую из сторон, задаваемых элементами `$moves`, пока мы на доске. Если встречаем фигуру и эта клетка — не `$to`, значит, в эту сторону мы не "доскользим" до `$to`, не задевая другие фигуры. Если же `$to` и не было фигур между, то ход можно сделать, учитывая инварианты

Ну, на самом деле еще есть пешка. Но пешка — это п🤬🔞ц. С ней нужно по-особенному.

Вот и все. Если ход возможен, то он совершается. Если при этом съели короля, то соответствующий цвет победил. Таблица обновляется. Иначе выводится сообщение о том, что ход некорректный.

🧘 Просто. 💪 Легко. 🤙 Понятно.

## 🚄 Roadmap

| Версия | Описание фичи/действия | Дата |
|:--:|--|:--:|
| 0.1 | **Запустить впервые PHP проект** | ✔️29.06.2020 |
| | Нашел [туториал](https://www.codeproject.com/Articles/759094/Step-by-Step-PHP-Tutorials-for-Beginners-Creating). Скачал XAMPP, поднял сервера MySQL и Apache | ✔️29.06.2020 |
| | Проверил, что после добавления в xampp/htdocs проекта из интернета он доступен по localhost/project-name, а в базу данных действительно что-то записывается при клике на кнопки | ✔️29.06.2020 |
| | Добавил *[super fast php mysql database class](https://codeshack.io/super-fast-php-mysql-database-class/)* для обращения к базе данных (это Database.php), защищающий, например, от `sql-injects` | ✔️29.06.2020 |
| | Добавил [классы для API по гайду](https://klisl.com/php-api-rest.html) - общий и для таблицы с игрой, сделал соответствующий `index.php` и `.htaccess` для редиректа запросов | ✔️29.06.2020 |
| | Пофиксил баги, разобрался, начал отправлять запросы через curl с правильными параметрами с успешным исходом | ✔️29.06.2020 |
| 0.2 | **Реализовать саму игру в шахматы** | ✔️30.06.2020 |
| | Продумал архитектуру решения: как хранить партию, как делать ходы и проверять их корректность. Сравнил разные решения и выбрал наилучшее | ✔️29.10.2019 |
| | Написал первую версию | ✔️30.10.2019 |
| | Устранил ошибки при запросах | ✔️30.10.2019 |
| | Оказалось, что текущий код не ловит параметры PUT запросов (особенности PHP). Загуглил и [нашел решение](https://lornajane.net/posts/2008/accessing-incoming-put-data-from-php), подправил класс Api.php | ✔️30.10.2019 |
| | Начал проверять логику, обнаружил недочеты, понял, что проверять возможность хода используя исключительно номера клетки доски - плохо (когда смотрим потенциальные ходы + и - числа легко "выйти" за границы доски слева и справа и получить некорректную клетку), переписал Piece.php | ✔️30.10.2019 |
| | Отрефакторил Piece.php: вынес общие функции в зависимости от параметра `$sliding` (скользит или прыгает наша фигура) | ✔️30.10.2019 |
| | Потестил — все работает | ✔️30.10.2019 |
| 0.3 | **Подготовить к "релизу"** | ✔️30.06.2020 |
| | Отрефакторил весь проект, добавил комментарии в коде | ✔️30.10.2019 |
| | Написал этот `README.md` | ✔️30.10.2019 |

Вообще чувствуйте себя здесь как дома — если что-то придумали, то делайте пулл-реквест или пишите issue!

## 🏆 Мотивация/какое было задание

Задание суперское, мне понравилось. Прикольно было запустить сервер, поднять базу данных и как начать хреначить запросами и смотреть на её изменения в PHPMyAdmin. Всем советую делать что-то сложное и получать кайф от того, что оно работает. Этому заданию однозначно лайк! 👍

Вообще этот проект — отборочный этап на летнюю стажировку 2020 в команду бэкенд Ленты и Рекомендаций [ВКонтакте](https://vk.com).

Задача: написать бэкенд для проведения шахматной партии.

Что требуется:
* язык PHP,
* использовать ООП,
* хранить состояние партии (положение фигур на доске и очерёдность хода),
* проверять ход на соответствие правилам,
* определять конец игры,
* написать API:
* cделать ход,
* cтатус партии,
* начать новую партию,
* продумать какие типы ошибок могут быть

Что должно получится на выходе:
веб-сервис, который даёт возможность провести шахматную партию, используя обозначенные выше методы API.

Что НЕ надо делать:
* вычислять патовую ситуацию,
* визуализацию.

## 📋 Technologies

- [PHP 7.4.7](https://www.php.net/releases/7_4_7.php) — language for backend
- [Object-oriented programming paradigm](https://en.wikipedia.org/wiki/Object-oriented_programming) — program style
- [XAMPP](https://www.apachefriends.org/download.html) — to start servers Apache and MySQL database
- [MySQL](https://en.wikipedia.org/wiki/MySQL) — database. SQL dialect for database operations (read, write, update etc)
- [Rest API architectural style](https://en.wikipedia.org/wiki/Representational_state_transfer) — way to interact with the server

## 🍿 References

- [PHP Tutorial for Beginners](https://www.codeproject.com/Articles/759094/Step-by-Step-PHP-Tutorials-for-Beginners-Creating) 
- [Super Fast PHP MySQL Database Class](https://codeshack.io/super-fast-php-mysql-database-class/)
- [API Rest PHP Guide](https://klisl.com/php-api-rest.html)
- [Get Variables of PUT Request in PHP](https://lornajane.net/posts/2008/accessing-incoming-put-data-from-php)

 ## 👪 Contributors

You are welcome! If you have any idea — you must write us about it, implement it and make a pull request! Or write an issue to discuss problems and ideas.

Current contributors list:

<a href="https://github.com/turing228" title="Github profile of Nikita Lisovetin">
    <img src="https://github.com/turing228.png" width="40" height="40">
    Nikita Lisovetin, student of ITMO University, Department of Computer Technologies. Developer.
</a>
 
 ## 📄 License

Outstanding-Move is MIT licensed, as found in the [LICENSE][l] file.

[l]: https://github.com/turing228/outstanding-move/blob/master/LICENSE
