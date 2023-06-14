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

let is_our_move = true; // флаг, сигнализирующий о том, что сейчас наш ход
let position = []; // расположение фигур на доске
let available_moves = {}; // допустимые ходы текущего игрока - человека

window.onload = function () {
    initGame();
}

function initGame() {
    createBoard();
    initPosition();
    showPosition();
}

function createBoard() {
    board = document.querySelector('.board');
    board.innerHTML = '';
    for (let i = 0; i < BoardSize**2; i += 1) {
        const cell = document.createElement('div');
        let is_white = (parseInt(i / BoardSize) + (i % BoardSize)) % 2 == 0;
        cell.classList.add('cell', (is_white ? 'white' : 'black'));
        board.appendChild(cell);
    }
}

function initPosition() {
    is_our_move = true;
    position = [
        6,  10, 8,  4,  2,  8,  10, 6,
        12, 12, 12, 12, 12, 12, 12, 12,
        0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,
        11, 11, 11, 11, 11, 11, 11, 11,
        5,  9,  7,  3,  1,  7,  9,  5
    ];
    available_moves = {
        48: [40, 32],
        49: [41, 33],
        50: [42, 34],
        51: [43, 35],
        52: [44, 36],
        53: [45, 37],
        54: [46, 38],
        55: [47, 39],
        57: [40, 42],
        62: [45, 47]
    };
}

function showPosition() {
    cells = document.querySelectorAll('.board .cell');
    for (let i = 0; i < BoardSize**2; i += 1) {
        let figure = position[i];
        if (figure == 0) {
            continue;
        }
        let image = FIGURES[figure];
        if (!image) {
            continue;
        }
        const figure_cell = document.createElement('div');
        const image_tag = document.createElement('img');
        image_tag.src = image;
        figure_cell.appendChild(image_tag);
        figure_cell.classList.add('figure');
        cells[i].appendChild(figure_cell);
    }
}