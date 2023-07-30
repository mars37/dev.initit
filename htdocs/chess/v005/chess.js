const createRequest = (options = {}) => {
    const method = (options.method === undefined ? 'GET' : options.method.toUpperCase());
    let url = options.url;
    let formData;
    if (options.data) {
        if (options.method === 'GET') {
            url += url.indexOf('?') >= 0 ? '&' : '?';
            for (let key in options.data) {
                url += key + '=' + encodeURI(options.data[key])+ '&';
            }
            url = url.slice(0, -1);
        } else {
            formData = new FormData();
            for (let key in options.data) {
                formData.append(key, options.data[key]);
            }
        }
    }
    const xhr = new XMLHttpRequest();
    try {
        xhr.open(method, url);
        if (options.headers) {
            for (let key in options.headers) {
                xhr.setRequestHeader(key, options.headers[key]);
            }
        }
        xhr.responseType = 'json';
        if (options.callback) {
            xhr.addEventListener('readystatechange', function() {
                if (this.readyState == xhr.DONE) {
                    let response = this.response;
                    if (this.status == 200 || this.status == 201 || options.no_check_status) {
                        options.callback(response);
                    } else if (options.error_callback) {
                        options.error_callback(response);   
                    } else {
                        console.log(response);
                    }
                }
            });
        }
        xhr.send(formData);
    } catch (e) {
        console.log(e);
    }
    return xhr;
}

const BoardSize = 8;

const FIGURES = {
    1: 'img/king-white.svg', // белый король
    2: 'img/king-black.svg', // чёрный король
    3: 'img/queen-white.svg', // белый ферзь
    4: 'img/queen-black.svg', // чёрный ферзь
    5: 'img/rook-white.svg', // белая ладья
    6: 'img/rook-black.svg', // чёрная ладья
    7: 'img/bishop-white.svg', // белый слон
    8: 'img/bishop-black.svg', // чёрный слон
    9: 'img/knight-white.svg', // белый конь
    10: 'img/knight-black.svg', // чёрный конь
    11: 'img/pawn-white.svg', // белая пешка
    12: 'img/pawn-black.svg', // чёрная пешка
}

let cells;
let selected_cell_index = null;
let available_moves_for_selected_cell = [];
let prev_move_from = null;
let prev_move_to = null;
let is_our_move = true; // флаг, сигнализирующий о том, что сейчас наш ход
let position = []; // расположение фигур на доске
let available_moves = {}; // допустимые ходы текущего игрока - человека

window.onload = function () {
    initGame();
}

function initGame() {
    createBoard();
    initButtons();
    createRequest({
        method: 'GET',
        url: 'get_game_state.php',
        callback: function(response) {
            if (response) {
                setGameState(response);
                setBoardOriented(response.human_color);
                if (!response.is_human_move) {
                    sendWaitComputerMove();
                }
            }
        }
    });
}

function createBoard() {
    board = document.querySelector('.board');
    board.innerHTML = '';
    for (let i = 0; i < BoardSize**2; i += 1) {
        const cell = document.createElement('div');
        let is_white = (parseInt(i / BoardSize) + (i % BoardSize)) % 2 == 0;
        cell.classList.add('cell', (is_white ? 'white' : 'black'));
        cell.addEventListener('click', (event) => onCellClick(event));
        board.appendChild(cell);
    }
    cells = Array.from(document.querySelectorAll('.board .cell'));
}

function initButtons() {
    // берём кнопку начала новой игры "за белых", назначаем обработчик
    button = document.querySelector('.control .new_game.white');
    button.addEventListener('click', (event) => createNewGame(event, 'w'));

    // теперь аналогично для кнопки "за чёрных"
    button = document.querySelector('.control .new_game.black');
    button.addEventListener('click', (event) => createNewGame(event, 'b'));
}

function createNewGame(event, human_color) {
    event.preventDefault();
    if (selected_cell_index !== null) {
        deselectCell(selected_cell_index);
        selected_cell_index = null;
        available_moves_for_selected_cell = [];
    }

    createRequest({
        method: 'POST',
        url: 'create_new_game.php',
        data: {human_color: human_color},
        callback: function(response) {
            setGameState(response);
            setBoardOriented(human_color);
            if (human_color == 'b') {
                sendWaitComputerMove();
            }
        }
    });
}

function setFigureToCell(figure, cell_index) {
    let image = FIGURES[figure];
    if (!image) {
        return;
    }
    const figure_cell = document.createElement('div');
    const image_tag = document.createElement('img');
    image_tag.src = image;
    figure_cell.appendChild(image_tag);
    figure_cell.classList.add('figure');
    cells[cell_index].appendChild(figure_cell);
}

function showPosition() {
    for (let i = 0; i < BoardSize**2; i += 1) {
        let figure = position[i];
        if (figure == 0) {
            continue;
        }
        setFigureToCell(figure, i);
    }
}

function onCellClick(event) {
    if (!is_our_move) {
        return;
    }
    
    const index = cells.indexOf(event.currentTarget);
    if (selected_cell_index !== null) {
        deselectCell(selected_cell_index); // снимаем выделение с текущей выделенной клетки
    }
    if (index === selected_cell_index) {
        // кликнули по уже выделенной клетке
        selected_cell_index = null;
        available_moves_for_selected_cell = [];
        return;
    }
    if (index in available_moves) {
        // кликнули по клетке, с которой есть доступные ходы
        selected_cell_index = index;
        available_moves_for_selected_cell = available_moves[index];
        selectCell(index);
        return;
    }

    if (available_moves_for_selected_cell.includes(index)) {
        // кликнули по полю, куда можно переместиться с выделенного поля
        deselectPrevMoveCells();
        const cell_index_from = selected_cell_index;
        makeMove(cell_index_from, index);
        selected_cell_index = null;
        available_moves_for_selected_cell = [];
        is_our_move = false;
        setGameStatus('Ждите мой ответ');
        sendMoveToServer(cell_index_from, index);
        return;
    }
    selected_cell_index = null;
}

function selectCell(cell_index) {
    let cell = cells[cell_index];
    cell.classList.add('figure_selected');
    for (let i = 0; i < available_moves_for_selected_cell.length; i += 1) {
        cell = cells[available_moves_for_selected_cell[i]];
        cell.classList.add('available_for_move');
    }
}

function deselectCell(cell_index) {
    let cell = cells[cell_index];
    cell.classList.remove('figure_selected');
    for (let i = 0; i < available_moves_for_selected_cell.length; i += 1) {
        cell = cells[available_moves_for_selected_cell[i]];
        cell.classList.remove('available_for_move');
    }
}

function deselectPrevMoveCells() {
    const prev_cells = Array.from(document.querySelectorAll('.board .cell.prev_move'));
    for (let i=0; i < prev_cells.length; i++) {
        prev_cells[i].classList.remove('prev_move');
    }
}

function makeMove(cell_index_from, cell_index_to) {
    const cell_from = cells[cell_index_from];
    const figure = cell_from.querySelector('.figure');
    const cell_to = cells[cell_index_to];
    if (figure) {
        cell_to.textContent = '';
        cell_to.appendChild(figure);
    }
    position[cell_index_to] = position[cell_index_from];
    position[cell_index_from] = 0;
    prev_move_from = cell_index_from;
    prev_move_to = cell_index_to;
    cell_from.classList.add('prev_move');
    cell_to.classList.add('prev_move');
}

function sendMoveToServer(cell_index_from, cell_index_to) {
    createRequest({
        method: 'POST',
        url: 'make_move.php',
        data: {cell_index_from: cell_index_from, cell_index_to: cell_index_to},
        callback: function(response) {
            setGameState(response);
        }
    });
}

function setGameStatus(text_state) {
    document.querySelector('.game_status .status').textContent = text_state;
}

function setBoardOriented(human_color) {
    let board = document.querySelector('.board');
    if (human_color == 'b') {
        board.classList.add('reverse');
    } else {
        board.classList.remove('reverse');
    }
}

function setGameState(game_state) {
    if (!game_state) {
        return;
    }

    // снимаем выделение с полей "предыдущего хода"
    deselectPrevMoveCells();

    // обновляем позицию
    for (let i = 0; i < BoardSize**2; i += 1) {
        if (position[i] === game_state.position[i]) {
            continue;
        }
        cells[i].textContent = '';
        setFigureToCell(game_state.position[i], i);
        position[i] = game_state.position[i]
    }

    // обновляю допустимые ходы
    available_moves = game_state.available_moves;

    // Делаем выделение полей "предыдущего хода"
    prev_move_from = game_state.prev_move_from;
    prev_move_to = game_state.prev_move_to;
    if (prev_move_from !== null) {
        cells[prev_move_from].classList.add('prev_move');
    }
    if (prev_move_to != null) {
        cells[prev_move_to].classList.add('prev_move');
    }

    // показываем статус игры
    setGameStatus(game_state.text_state);

    // устанавливаем очередь хода
    is_our_move = game_state.is_human_move;
}

function sendWaitComputerMove() {

}
